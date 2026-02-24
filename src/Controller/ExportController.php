<?php

namespace App\Controller;

use App\Repository\QuizRepository;
use App\Service\AuthChecker;
use Knp\Snappy\Pdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/export')]
class ExportController extends AbstractController
{
    public function __construct(
        private Pdf $pdf,
        private QuizRepository $quizRepository,
        private AuthChecker $authChecker
    ) {}

    // ===========================
    // ENSEIGNANT
    // ===========================

    /**
     * Export PDF résultats d'un quiz — ENSEIGNANT
     * Affiche les questions + réponses correctes du quiz
     */
    #[Route('/enseignant/quiz/{id}/resultats-pdf', name: 'export_teacher_quiz_results_pdf', methods: ['GET'])]
    public function teacherQuizResultsPdf(int $id): Response
    {
        if (!$this->authChecker->isLoggedIn() || !$this->authChecker->isEnseignant()) {
            throw $this->createAccessDeniedException();
        }

        $enseignant = $this->authChecker->getCurrentUser();
        $quiz = $this->quizRepository->find($id);

        if (!$quiz || $quiz->getIdCreateur() !== $enseignant->getId()) {
            throw $this->createNotFoundException('Quiz non trouvé');
        }

        $html = $this->renderView('export/teacher_quiz_results.html.twig', [
            'quiz'       => $quiz,
            'enseignant' => $enseignant,
            'generated'  => new \DateTime(),
        ]);

        return new Response(
            $this->pdf->getOutputFromHtml($html),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="quiz-%d-resultats.pdf"', $id),
            ]
        );
    }

    // ===========================
    // ETUDIANT
    // ===========================

    /**
     * Export PDF résultats — ETUDIANT
     * Lit les résultats stockés en session après soumission du quiz
     */
    #[Route('/etudiant/quiz/mes-resultats-pdf', name: 'export_student_results_pdf', methods: ['GET'])]
    public function studentResultsPdf(Request $request): Response
    {
        if (!$this->authChecker->isLoggedIn() || !$this->authChecker->isEtudiant()) {
            throw $this->createAccessDeniedException();
        }

        $student = $this->authChecker->getCurrentUser();
        $session = $request->getSession();
        $data    = $session->get('last_quiz_results');

        if (!$data) {
            $this->addFlash('error', 'Aucun résultat disponible. Veuillez d\'abord soumettre un quiz.');
            return $this->redirectToRoute('quiz_index');
        }

        $html = $this->renderView('export/student_results.html.twig', [
            'data'    => $data,
            'student' => $student,
        ]);

        return new Response(
            $this->pdf->getOutputFromHtml($html),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => sprintf(
                    'attachment; filename="mes-resultats-%s.pdf"',
                    date('Y-m-d')
                ),
            ]
        );
    }
}