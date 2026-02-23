<?php

namespace App\Controller;

use App\Entity\Quiz;
use App\Entity\Question;
use App\Entity\Reponse;
use App\Service\QuizGeneratorService;
use App\Service\AuthChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/quiz/ai')]
class QuizAIController extends AbstractController
{
    public function __construct(
        private QuizGeneratorService $generator,
        private EntityManagerInterface $entityManager,
        private AuthChecker $authChecker
    ) {}

   #[Route('/generate', name: 'quiz_ai_generate', methods: ['GET', 'POST'])]
public function generate(Request $request): Response
{
    if (!$this->authChecker->isLoggedIn()) {
        return $this->redirectToRoute('app_login');
    }

    $user      = $this->authChecker->getCurrentUser();
    $isProf    = $this->authChecker->isEnseignant();
    $quiz      = null;
    $savedQuiz = null;
    $error     = null;

    if ($request->isMethod('POST')) {
        try {
            $mode        = $request->request->get('mode');
            $nbQuestions = (int) $request->request->get('nb_questions', 5);

            if ($mode === 'subject') {
                $quiz = $this->generator->generateFromSubject(
                    $request->request->get('subject'),
                    $nbQuestions,
                    $request->request->get('level', 'intermédiaire')
                );
            } elseif ($mode === 'document') {
                $file = $request->files->get('document');
                if ($file) {
                    $quiz = $this->generator->generateFromDocument(
                        file_get_contents($file->getPathname()),
                        $nbQuestions
                    );
                }
            }

            if ($isProf && $quiz) {
                $savedQuiz = $this->saveQuizToDatabase($quiz, $user->getId());
            }

        } catch (\Exception $e) {
            $error = 'Erreur lors de la génération : ' . $e->getMessage();
        }
    }

    // ✅ Template selon le rôle
    $template = $isProf
        ? 'enseignant/quiz/ai_generate.html.twig'
        : 'etudiant/quiz/ai_generate.html.twig';

    return $this->render($template, [
    'quiz'          => $quiz,
    'savedQuiz'     => $savedQuiz,
    'isProf'        => $isProf,
    'error'         => $error,
    'current_user'  => $user,
    'enseignant'    => $user,   // ✅ Ajoutez cette ligne
    'student'       => $user,   // ✅ Pour le template étudiant
    'is_etudiant'   => !$isProf,
    'is_enseignant' => $isProf,
    'is_admin'      => false,
]);
}

    private function saveQuizToDatabase(array $quizData, int $userId): Quiz
{
    $quiz = new Quiz();
    $quiz->setTitre($quizData['title']);
    $quiz->setDescription('Quiz généré par IA (Groq)');
    $quiz->setTypeQuiz('entrainement');
    $quiz->setIdCreateur($userId);
    $quiz->setDateCreation(new \DateTime());

    $this->entityManager->persist($quiz);

    foreach ($quizData['questions'] as $index => $qData) {
        $question = new Question();
        $question->setTexte($qData['question']);
        $question->setTitre('Question ' . ($index + 1));
        $question->setTypeQuestion('choix_multiple');
        $question->setPoints(1);
        $question->setOrdreAffichage($index + 1);
        $question->setDateCreation(new \DateTime());
        $question->setExplicationReponse($qData['explanation'] ?? null);
        $question->setQuiz($quiz);

        $this->entityManager->persist($question);

        foreach ($qData['options'] as $i => $optionText) {
            $letter = $optionText[0]; // "A", "B", "C", "D"

            $reponse = new Reponse();
            $reponse->setTexteReponse($optionText);
            $reponse->setEstCorrecte($letter === $qData['correct']);
            $reponse->setOrdreAffichage($i + 1);
            $reponse->setQuestion($question);

            $this->entityManager->persist($reponse);
        }
    }

    $this->entityManager->flush();

    return $quiz;
}
}