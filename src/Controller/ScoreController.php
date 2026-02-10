<?php

namespace App\Controller;

use App\Entity\Score;
use App\Form\ScoreType;
use App\Repository\ScoreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/score')]
class ScoreController extends AbstractController
{
    #[Route('/', name: 'app_score_index', methods: ['GET'])]
    public function index(Request $request, ScoreRepository $scoreRepository): Response
    {
        $search = $request->query->get('search', '');
        $sortBy = $request->query->get('sort', 'dateCorrection');
        $order = $request->query->get('order', 'DESC');

        $scores = $scoreRepository->findBySearchAndSort($search, $sortBy, $order);

        return $this->render('score/index.html.twig', [
            'scores' => $scores,
            'search' => $search,
            'sortBy' => $sortBy,
            'order' => $order,
        ]);
    }

    #[Route('/new', name: 'app_score_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $score = new Score();
        $form = $this->createForm(ScoreType::class, $score);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $soumission = $score->getSoumission();

            // 🔐 SÉCURITÉ ABSOLUE : 1 soumission = 1 score
            if ($soumission->getScore() !== null) {
                $this->addFlash(
                    'danger',
                    'Cette soumission a déjà été corrigée. Impossible d’ajouter un second score.'
                );

                return $this->redirectToRoute('app_score_index');
            }

            // 🔗 liaison bidirectionnelle
            $score->setStatutCorrection('corrige');
            $soumission->setScore($score);

            $entityManager->persist($score);
            $entityManager->flush();

            $this->addFlash('success', 'Score ajouté avec succès');
            return $this->redirectToRoute('app_score_index');
        }

        return $this->render('score/new.html.twig', [
            'score' => $score,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_score_show', methods: ['GET'])]
    public function show(Score $score): Response
    {
        return $this->render('score/show.html.twig', [
            'score' => $score,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_score_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Score $score, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ScoreType::class, $score);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Optionnel : empêcher le changement de soumission
            if ($score->getSoumission()->getScore() !== $score) {
                $this->addFlash(
                    'danger',
                    'Impossible de modifier la soumission associée à ce score.'
                );
                return $this->redirectToRoute('app_score_index');
            }

            $entityManager->flush();

            $this->addFlash('success', 'Score modifié avec succès');
            return $this->redirectToRoute('app_score_index');
        }

        return $this->render('score/edit.html.twig', [
            'score' => $score,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_score_delete', methods: ['POST'])]
    public function delete(Request $request, Score $score, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $score->getId(), $request->request->get('_token'))) {

            // 🔗 casser la relation OneToOne
            if ($score->getSoumission()) {
                $score->getSoumission()->setScore(null);
            }

            $entityManager->remove($score);
            $entityManager->flush();

            $this->addFlash('success', 'Score supprimé avec succès');
        }

        return $this->redirectToRoute('app_score_index');
    }
}
