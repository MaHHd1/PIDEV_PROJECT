<?php

namespace App\Controller;

use App\Entity\Etudiant;
use App\Entity\Quiz;
use App\Form\QuizType;
use App\Repository\QuizRepository;
use App\Service\QuizService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\AuthChecker;
#[Route('/quiz')]
class QuizController extends AbstractController
{
    private QuizService $quizService;
    private QuizRepository $quizRepository;
    private EntityManagerInterface $entityManager;
    private AuthChecker $authChecker;
    public function __construct(
        QuizService $quizService,
        QuizRepository $quizRepository,
        EntityManagerInterface $entityManager,
        AuthChecker $authChecker
    ) {
        $this->quizService = $quizService;
        $this->quizRepository = $quizRepository;
        $this->entityManager = $entityManager;
        $this->authChecker = $authChecker;
    }

    // ===========================
    // TEACHER ROUTES
    // ===========================

    /**
     * Afficher le formulaire de création de quiz
     */
 // ===========================
// TEACHER ROUTES
// ===========================

/**
 * Afficher le formulaire de création de quiz
 */
#[Route('/nouveau', name: 'quiz_new', methods: ['GET', 'POST'])]
public function new(Request $request, AuthChecker $authChecker): Response
{
    // ========== AUTHENTICATION & AUTHORIZATION ==========
    if (!$authChecker->isLoggedIn()) {
        $this->addFlash('error', 'Veuillez vous connecter pour créer un quiz.');
        return $this->redirectToRoute('app_login');
    }
    
    if (!$authChecker->isEnseignant()) {
        $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux enseignants.');
        return $this->redirectToRoute('app_home');
    }
    
    $enseignant = $authChecker->getCurrentUser();
    
    $quiz = new Quiz();
    $form = $this->createForm(QuizType::class, $quiz);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        try {
            // Use the actual teacher's ID
            $quiz->setIdCreateur($enseignant->getId());
            $quiz->setDateCreation(new \DateTime());

            $this->entityManager->persist($quiz);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le quiz a été créé avec succès !');

            return $this->redirectToRoute('teacher_question_new', ['idQuiz' => $quiz->getId()]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la création du quiz : ' . $e->getMessage());
        }
    }

    return $this->render('enseignant/quiz/new.html.twig', [
        'quiz' => $quiz,
        'form' => $form,
        'enseignant' => $enseignant,
        'current_user' => $enseignant,
        'is_etudiant' => false,
        'is_admin' => false,
        'is_enseignant' => true,
    ]);
}

/**
 * Liste tous les quiz (teacher)
 */
#[Route('/teacher/quiz', name: 'teacher_quiz_index', methods: ['GET'])]
public function teacherIndex(Request $request, AuthChecker $authChecker): Response
{
    // ========== AUTHENTICATION & AUTHORIZATION ==========
    if (!$authChecker->isLoggedIn()) {
        $this->addFlash('error', 'Veuillez vous connecter pour accéder à vos quiz.');
        return $this->redirectToRoute('app_login');
    }
    
    if (!$authChecker->isEnseignant()) {
        $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux enseignants.');
        return $this->redirectToRoute('app_home');
    }
    
    $enseignant = $authChecker->getCurrentUser();

    // Récupération et validation des paramètres
    $search = $request->query->get('search', '');
    $type = $request->query->get('type', '');
    $difficulte = $request->query->get('difficulte', '');
    $sort = $request->query->get('sort', 'recent');

    // Validation du tri
    $allowedSorts = ['recent', 'titre', 'titre_desc', 'difficulte_asc', 'difficulte_desc'];
    if (!in_array($sort, $allowedSorts)) {
        $sort = 'recent';
    }

    try {
        $queryBuilder = $this->quizRepository->createQueryBuilder('q');

        // Filtrer par créateur (enseignant connecté)
        $queryBuilder->andWhere('q.idCreateur = :userId')
            ->setParameter('userId', $enseignant->getId());

        // Recherche textuelle avec protection contre les injections
        if (!empty($search)) {
            $search = trim($search);
            $queryBuilder->andWhere(
                $queryBuilder->expr()->orX(
                    'q.titre LIKE :search',
                    'q.description LIKE :search',
                    'q.typeQuiz LIKE :search',
                    'q.instructions LIKE :search'
                )
            )->setParameter('search', '%' . $search . '%');
        }

        // Filtre par type avec validation
        if (!empty($type)) {
            $allowedTypes = ['evaluation', 'entrainement', 'revision', 'diagnostique'];
            if (in_array($type, $allowedTypes)) {
                $queryBuilder->andWhere('q.typeQuiz = :type')
                    ->setParameter('type', $type);
            }
        }

        // Filtre par difficulté avec validation
        if (!empty($difficulte)) {
            switch ($difficulte) {
                case 'facile':
                    $queryBuilder->andWhere('q.difficulteMoyenne >= 1 AND q.difficulteMoyenne <= 3');
                    break;
                case 'moyen':
                    $queryBuilder->andWhere('q.difficulteMoyenne > 3 AND q.difficulteMoyenne <= 6');
                    break;
                case 'difficile':
                    $queryBuilder->andWhere('q.difficulteMoyenne > 6 AND q.difficulteMoyenne <= 10');
                    break;
            }
        }

        // Tri des résultats
        switch ($sort) {
            case 'titre':
                $queryBuilder->orderBy('q.titre', 'ASC');
                break;
            case 'titre_desc':
                $queryBuilder->orderBy('q.titre', 'DESC');
                break;
            case 'difficulte_asc':
                $queryBuilder->orderBy('q.difficulteMoyenne', 'ASC')
                    ->addOrderBy('q.titre', 'ASC');
                break;
            case 'difficulte_desc':
                $queryBuilder->orderBy('q.difficulteMoyenne', 'DESC')
                    ->addOrderBy('q.titre', 'ASC');
                break;
            case 'recent':
            default:
                $queryBuilder->orderBy('q.dateCreation', 'DESC');
        }

        $quizzes = $queryBuilder->getQuery()->getResult();

    } catch (\Exception $e) {
        $this->addFlash('error', 'Erreur lors de la récupération des quiz : ' . $e->getMessage());
        $quizzes = [];
    }

    return $this->render('enseignant/quiz/index.html.twig', [
        'quizzes' => $quizzes,
        'search' => $search,
        'enseignant' => $enseignant,
        'current_user' => $enseignant,
        'is_etudiant' => false,
        'is_admin' => false,
        'is_enseignant' => true,
    ]);
}

/**
 * Modifier un quiz
 */
#[Route('/{id<\d+>}/modifier', name: 'quiz_edit', methods: ['GET', 'POST'])]
public function edit(Request $request, int $id, AuthChecker $authChecker): Response
{
    // ========== AUTHENTICATION & AUTHORIZATION ==========
    if (!$authChecker->isLoggedIn()) {
        $this->addFlash('error', 'Veuillez vous connecter pour modifier un quiz.');
        return $this->redirectToRoute('app_login');
    }
    
    if (!$authChecker->isEnseignant()) {
        $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux enseignants.');
        return $this->redirectToRoute('app_home');
    }
    
    $enseignant = $authChecker->getCurrentUser();

    // Validation de l'ID
    if ($id <= 0) {
        $this->addFlash('error', 'ID de quiz invalide');
        return $this->redirectToRoute('teacher_quiz_index');
    }

    $quiz = $this->quizRepository->find($id);

    if (!$quiz) {
        $this->addFlash('error', 'Quiz non trouvé');
        return $this->redirectToRoute('teacher_quiz_index');
    }

    // Vérifier que l'utilisateur est le créateur du quiz
    if ($quiz->getIdCreateur() !== $enseignant->getId()) {
        $this->addFlash('error', 'Vous n\'êtes pas autorisé à modifier ce quiz');
        return $this->redirectToRoute('teacher_quiz_index');
    }

    $form = $this->createForm(QuizType::class, $quiz);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        try {
            $this->entityManager->flush();
            $this->addFlash('success', 'Le quiz a été modifié avec succès !');

            return $this->redirectToRoute('teacher_quiz_index');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la modification : ' . $e->getMessage());
        }
    }

    return $this->render('enseignant/quiz/edit.html.twig', [
        'quiz' => $quiz,
        'form' => $form,
        'enseignant' => $enseignant,
        'current_user' => $enseignant,
        'is_etudiant' => false,
        'is_admin' => false,
        'is_enseignant' => true,
    ]);
}

/**
 * Supprimer un quiz
 */
#[Route('/{id<\d+>}/supprimer', name: 'quiz_delete', methods: ['POST'])]
public function delete(Request $request, int $id, AuthChecker $authChecker): Response
{
    // ========== AUTHENTICATION & AUTHORIZATION ==========
    if (!$authChecker->isLoggedIn()) {
        $this->addFlash('error', 'Veuillez vous connecter pour supprimer un quiz.');
        return $this->redirectToRoute('app_login');
    }
    
    if (!$authChecker->isEnseignant()) {
        $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux enseignants.');
        return $this->redirectToRoute('app_home');
    }
    
    $enseignant = $authChecker->getCurrentUser();

    // Validation de l'ID
    if ($id <= 0) {
        $this->addFlash('error', 'ID de quiz invalide');
        return $this->redirectToRoute('teacher_quiz_index');
    }

    $quiz = $this->quizRepository->find($id);

    if (!$quiz) {
        $this->addFlash('error', 'Quiz non trouvé');
        return $this->redirectToRoute('teacher_quiz_index');
    }

    // Vérifier que l'utilisateur est le créateur du quiz
    if ($quiz->getIdCreateur() !== $enseignant->getId()) {
        $this->addFlash('error', 'Vous n\'êtes pas autorisé à supprimer ce quiz');
        return $this->redirectToRoute('teacher_quiz_index');
    }

    // Validation du token CSRF
    $token = $request->request->get('_token');
    if (!$this->isCsrfTokenValid('delete' . $quiz->getId(), $token)) {
        $this->addFlash('error', 'Token CSRF invalide');
        return $this->redirectToRoute('teacher_quiz_index');
    }

    try {
        $this->quizService->deleteQuiz($quiz);
        $this->addFlash('success', 'Le quiz a été supprimé avec succès !');
    } catch (\Exception $e) {
        $this->addFlash('error', 'Erreur lors de la suppression : ' . $e->getMessage());
    }

    return $this->redirectToRoute('teacher_quiz_index');
}

/**
 * Dupliquer un quiz
 */
#[Route('/{id<\d+>}/dupliquer', name: 'quiz_duplicate', methods: ['POST'])]
public function duplicate(Request $request, int $id, AuthChecker $authChecker): Response
{
    // ========== AUTHENTICATION & AUTHORIZATION ==========
    if (!$authChecker->isLoggedIn()) {
        $this->addFlash('error', 'Veuillez vous connecter pour dupliquer un quiz.');
        return $this->redirectToRoute('app_login');
    }
    
    if (!$authChecker->isEnseignant()) {
        $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux enseignants.');
        return $this->redirectToRoute('app_home');
    }
    
    $enseignant = $authChecker->getCurrentUser();

    // Validation de l'ID
    if ($id <= 0) {
        $this->addFlash('error', 'ID de quiz invalide');
        return $this->redirectToRoute('teacher_quiz_index');
    }

    $quiz = $this->quizRepository->find($id);

    if (!$quiz) {
        $this->addFlash('error', 'Quiz non trouvé');
        return $this->redirectToRoute('teacher_quiz_index');
    }

    // Vérifier que l'utilisateur est le créateur du quiz
    if ($quiz->getIdCreateur() !== $enseignant->getId()) {
        $this->addFlash('error', 'Vous n\'êtes pas autorisé à dupliquer ce quiz');
        return $this->redirectToRoute('teacher_quiz_index');
    }

    // Validation du token CSRF
    $token = $request->request->get('_token');
    if (!$this->isCsrfTokenValid('duplicate' . $quiz->getId(), $token)) {
        $this->addFlash('error', 'Token CSRF invalide');
        return $this->redirectToRoute('teacher_quiz_index');
    }

    try {
        $newQuiz = $this->quizService->duplicateQuiz($quiz, $enseignant->getId());
        $this->addFlash('success', 'Le quiz a été dupliqué avec succès !');
    } catch (\Exception $e) {
        $this->addFlash('error', 'Erreur lors de la duplication : ' . $e->getMessage());
    }

    return $this->redirectToRoute('teacher_quiz_index');
}

/**
 * Gérer les questions d'un quiz
 */
#[Route('/{id<\d+>}/questions', name: 'quiz_manage_questions', methods: ['GET'])]
public function manageQuestions(int $id, AuthChecker $authChecker): Response
{
    // ========== AUTHENTICATION & AUTHORIZATION ==========
    if (!$authChecker->isLoggedIn()) {
        $this->addFlash('error', 'Veuillez vous connecter pour gérer les questions.');
        return $this->redirectToRoute('app_login');
    }
    
    if (!$authChecker->isEnseignant()) {
        $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux enseignants.');
        return $this->redirectToRoute('app_home');
    }
    
    $enseignant = $authChecker->getCurrentUser();

    // Validation de l'ID
    if ($id <= 0) {
        $this->addFlash('error', 'ID de quiz invalide');
        return $this->redirectToRoute('teacher_quiz_index');
    }

    $quiz = $this->quizRepository->find($id);

    if (!$quiz) {
        $this->addFlash('error', 'Quiz non trouvé');
        return $this->redirectToRoute('teacher_quiz_index');
    }

    // Vérifier que l'utilisateur est le créateur du quiz
    if ($quiz->getIdCreateur() !== $enseignant->getId()) {
        $this->addFlash('error', 'Vous n\'êtes pas autorisé à gérer les questions de ce quiz');
        return $this->redirectToRoute('teacher_quiz_index');
    }

    return $this->render('enseignant/quiz/manage_questions.html.twig', [
        'quiz' => $quiz,
        'enseignant' => $enseignant,
        'current_user' => $enseignant,
        'is_etudiant' => false,
        'is_admin' => false,
        'is_enseignant' => true,
    ]);
}

    // ===========================
    // STUDENT ROUTES
    // ===========================

    #[Route('/test', name: 'quiz_test', methods: ['GET'])]
    public function test(): Response
    {
        return $this->render('etudiant/quiz/test.html.twig');
    }

    /**
     * Liste des quiz disponibles pour les étudiants (avec filtres avancés)
     */
  /**
 * Liste des quiz disponibles pour les étudiants (avec filtres avancés)
 */
#[Route('/', name: 'quiz_index', methods: ['GET'])]
public function index(Request $request, AuthChecker $authChecker): Response
{
    // ========== AUTHENTICATION & AUTHORIZATION ==========
    // Check if user is logged in
    if (!$authChecker->isLoggedIn()) {
        $this->addFlash('error', 'Veuillez vous connecter pour accéder aux quiz.');
        return $this->redirectToRoute('app_login');
    }
    
    // Check if user is a student
    if (!$authChecker->isEtudiant()) {
        $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux étudiants.');
        return $this->redirectToRoute('app_home');
    }
    
    // Get the current user (should be an Etudiant)
    $user = $authChecker->getCurrentUser();
    
    // Verify it's actually an Etudiant
    if (!$user instanceof \App\Entity\Etudiant) {
        $this->addFlash('error', 'Type d\'utilisateur incorrect.');
        return $this->redirectToRoute('app_home');
    }
    
    // Cast to Etudiant for type safety
    $student = $user;

    // ========== QUIZ FILTERING LOGIC ==========
    // Récupération et validation des paramètres
    $search = $request->query->get('search', '');
    $type = $request->query->get('type', '');
    $difficulte = $request->query->get('difficulte', '');
    $sort = $request->query->get('sort', 'recent');

    // Validation du tri
    $allowedSorts = ['recent', 'titre', 'titre_desc', 'difficulte_asc', 'difficulte_desc'];
    if (!in_array($sort, $allowedSorts)) {
        $sort = 'recent';
    }

    try {
        $queryBuilder = $this->quizRepository->createQueryBuilder('q');

        $accessData = $this->buildStudentQuizAccessData($student);
        $enrolledCourseIds = $accessData['course_ids'];
        $linkedQuizIds = $accessData['linked_quiz_ids'];

        if ($enrolledCourseIds === [] && $linkedQuizIds === []) {
            $quizzes = [];

            return $this->render('etudiant/quiz/index.html.twig', [
                'quizzes' => $quizzes,
                'search' => $search,
                'student' => $student,
                'current_user' => $student,
                'is_etudiant' => true,
                'is_admin' => false,
                'is_enseignant' => false,
            ]);
        }

        if ($enrolledCourseIds !== [] && $linkedQuizIds !== []) {
            $queryBuilder
                ->andWhere('q.idCours IN (:courseIds) OR q.id IN (:linkedQuizIds)')
                ->setParameter('courseIds', $enrolledCourseIds)
                ->setParameter('linkedQuizIds', $linkedQuizIds);
        } elseif ($enrolledCourseIds !== []) {
            $queryBuilder
                ->andWhere('q.idCours IN (:courseIds)')
                ->setParameter('courseIds', $enrolledCourseIds);
        } else {
            $queryBuilder
                ->andWhere('q.id IN (:linkedQuizIds)')
                ->setParameter('linkedQuizIds', $linkedQuizIds);
        }

        // Recherche textuelle avec protection
        if (!empty($search)) {
            $search = trim($search);
            if (strlen($search) > 100) { // Limite de longueur
                $search = substr($search, 0, 100);
            }
            
            $queryBuilder->andWhere(
                $queryBuilder->expr()->orX(
                    'q.titre LIKE :search',
                    'q.description LIKE :search',
                    'q.typeQuiz LIKE :search',
                    'q.instructions LIKE :search'
                )
            )->setParameter('search', '%' . $search . '%');
        }

        // Filtre par type avec validation
        if (!empty($type)) {
            $allowedTypes = ['evaluation', 'entrainement', 'revision', 'diagnostique'];
            if (in_array($type, $allowedTypes)) {
                $queryBuilder->andWhere('q.typeQuiz = :type')
                    ->setParameter('type', $type);
            }
        }

        // Filtre par difficulté avec validation
        if (!empty($difficulte)) {
            switch ($difficulte) {
                case 'facile':
                    $queryBuilder->andWhere('q.difficulteMoyenne >= 1 AND q.difficulteMoyenne <= 3');
                    break;
                case 'moyen':
                    $queryBuilder->andWhere('q.difficulteMoyenne > 3 AND q.difficulteMoyenne <= 6');
                    break;
                case 'difficile':
                    $queryBuilder->andWhere('q.difficulteMoyenne > 6 AND q.difficulteMoyenne <= 10');
                    break;
            }
        }

        // Tri des résultats
        switch ($sort) {
            case 'titre':
                $queryBuilder->orderBy('q.titre', 'ASC');
                break;
            case 'titre_desc':
                $queryBuilder->orderBy('q.titre', 'DESC');
                break;
            case 'difficulte_asc':
                $queryBuilder->orderBy('q.difficulteMoyenne', 'ASC')
                    ->addOrderBy('q.titre', 'ASC');
                break;
            case 'difficulte_desc':
                $queryBuilder->orderBy('q.difficulteMoyenne', 'DESC')
                    ->addOrderBy('q.titre', 'ASC');
                break;
            case 'recent':
            default:
                $queryBuilder->orderBy('q.dateCreation', 'DESC');
        }

        $quizzes = $queryBuilder->getQuery()->getResult();

    } catch (\Exception $e) {
        $this->addFlash('error', 'Erreur lors de la récupération des quiz');
        $quizzes = [];
    }

    // ========== RETURN WITH ALL REQUIRED VARIABLES ==========
    return $this->render('etudiant/quiz/index.html.twig', [
        'quizzes' => $quizzes,
        'search' => $search,
        'student' => $student,
        'current_user' => $student,
        'is_etudiant' => true,
        'is_admin' => false,
        'is_enseignant' => false,
    ]);
}
/**
 * Afficher les détails d'un quiz
 */
#[Route('/{id<\d+>}', name: 'quiz_show', methods: ['GET'])]
public function show(int $id, AuthChecker $authChecker): Response
{
    // ========== AUTHENTICATION & AUTHORIZATION ==========
    if (!$authChecker->isLoggedIn()) {
        $this->addFlash('error', 'Veuillez vous connecter pour voir ce quiz.');
        return $this->redirectToRoute('app_login');
    }
    
    if (!$authChecker->isEtudiant()) {
        $this->addFlash('error', 'Accès non autorisé.');
        return $this->redirectToRoute('app_home');
    }
    
    $student = $authChecker->getCurrentUser();
    if (!$student instanceof Etudiant) {
        $this->addFlash('error', 'Type d\'utilisateur incorrect.');
        return $this->redirectToRoute('app_home');
    }
    
    // Validation de l'ID
    if ($id <= 0) {
        $this->addFlash('error', 'ID de quiz invalide');
        return $this->redirectToRoute('quiz_index');
    }

    try {
        $quiz = $this->quizRepository->find($id);

        if (!$quiz) {
            $this->addFlash('error', 'Quiz non trouvé');
            return $this->redirectToRoute('quiz_index');
        }

        // Vérifier la disponibilité du quiz
        if (!$this->studentCanAccessQuiz($student, $quiz)) {
            $this->addFlash('error', 'Ce quiz ne fait pas partie de vos cours inscrits.');
            return $this->redirectToRoute('quiz_index');
        }

        // Récupérer les statistiques
        $stats = $this->quizService->getQuizStatistics($id);

    } catch (\Exception $e) {
        $this->addFlash('error', 'Erreur lors du chargement du quiz');
        return $this->redirectToRoute('quiz_index');
    }

    return $this->render('etudiant/quiz/show.html.twig', [
        'quiz' => $quiz,
        'stats' => $stats,
        'student' => $student,
        'current_user' => $student,
        'is_etudiant' => true,
        'is_admin' => false,
        'is_enseignant' => false,
    ]);
}

   /**
 * Passer le quiz
 */
#[Route('/{id<\d+>}/passer', name: 'quiz_show_complet', methods: ['GET'])]
public function showComplet(int $id, AuthChecker $authChecker): Response
{
    // ========== AUTHENTICATION & AUTHORIZATION ==========
    if (!$authChecker->isLoggedIn()) {
        $this->addFlash('error', 'Veuillez vous connecter pour passer ce quiz.');
        return $this->redirectToRoute('app_login');
    }
    
    if (!$authChecker->isEtudiant()) {
        $this->addFlash('error', 'Accès non autorisé.');
        return $this->redirectToRoute('app_home');
    }
    
    $student = $authChecker->getCurrentUser();
    if (!$student instanceof Etudiant) {
        $this->addFlash('error', 'Type d\'utilisateur incorrect.');
        return $this->redirectToRoute('app_home');
    }
    
    // Validation de l'ID
    if ($id <= 0) {
        $this->addFlash('error', 'ID de quiz invalide');
        return $this->redirectToRoute('quiz_index');
    }

    try {
        $quiz = $this->quizRepository->findQuizComplet($id);

        if (!$quiz) {
            $this->addFlash('error', 'Quiz non trouvé');
            return $this->redirectToRoute('quiz_index');
        }

        // Vérifier que le quiz contient des questions
        if (!$this->studentCanAccessQuiz($student, $quiz)) {
            $this->addFlash('error', 'Ce quiz ne fait pas partie de vos cours inscrits.');
            return $this->redirectToRoute('quiz_index');
        }

        if ($quiz->getQuestions()->count() === 0) {
            $this->addFlash('warning', 'Ce quiz ne contient aucune question');
            return $this->redirectToRoute('quiz_show', ['id' => $id]);
        }

        // Vérifier la disponibilité
    } catch (\Exception $e) {
        $this->addFlash('error', 'Erreur lors du chargement du quiz');
        return $this->redirectToRoute('quiz_index');
    }

    return $this->render('etudiant/quiz/take.html.twig', [
        'quiz' => $quiz,
        'student' => $student,
        'current_user' => $student,
        'is_etudiant' => true,
        'is_admin' => false,
        'is_enseignant' => false,
    ]);
}

    /**
     * Soumettre les réponses du quiz
     */
    /**
 * Soumettre les réponses du quiz
 */
#[Route('/{id<\d+>}/soumettre', name: 'quiz_submit', methods: ['POST'])]
public function submit(Request $request, int $id, AuthChecker $authChecker): Response
{
    // ========== AUTHENTICATION & AUTHORIZATION ==========
    if (!$authChecker->isLoggedIn()) {
        $this->addFlash('error', 'Veuillez vous connecter pour soumettre un quiz.');
        return $this->redirectToRoute('app_login');
    }
    
    if (!$authChecker->isEtudiant()) {
        $this->addFlash('error', 'Accès non autorisé.');
        return $this->redirectToRoute('app_home');
    }
    
    $student = $authChecker->getCurrentUser();
    if (!$student instanceof Etudiant) {
        $this->addFlash('error', 'Type utilisateur incorrect.');
        return $this->redirectToRoute('app_home');
    }
    
    // Validation de l'ID
    if ($id <= 0) {
        $this->addFlash('error', 'ID de quiz invalide');
        return $this->redirectToRoute('quiz_index');
    }

    try {
        $quiz = $this->quizRepository->findQuizComplet($id);

        if (!$quiz) {
            $this->addFlash('error', 'Quiz non trouvé');
            return $this->redirectToRoute('quiz_index');
        }

        // Récupérer et valider les réponses
        if (!$this->studentCanAccessQuiz($student, $quiz)) {
            $this->addFlash('error', 'Ce quiz ne fait pas partie de vos cours inscrits.');
            return $this->redirectToRoute('quiz_index');
        }

        $studentAnswers = $request->request->all('answers');

        if (empty($studentAnswers)) {
            $this->addFlash('warning', 'Aucune réponse n\'a été soumise');
            return $this->redirectToRoute('quiz_show_complet', ['id' => $id]);
        }

        // Validation: vérifier que les IDs de questions et réponses sont des entiers
        foreach ($studentAnswers as $questionId => $answerId) {
            if (!is_numeric($questionId) || !is_numeric($answerId)) {
                $this->addFlash('error', 'Données invalides soumises');
                return $this->redirectToRoute('quiz_show_complet', ['id' => $id]);
            }
        }

        // Calculer le score et préparer les résultats
        $results = [];
        $totalPoints = 0;
        $earnedPoints = 0;

        foreach ($quiz->getQuestions() as $question) {
            $questionId = $question->getId();
            $studentAnswerId = $studentAnswers[$questionId] ?? null;

            $correctAnswer = null;
            $studentAnswer = null;
            $isCorrect = false;

            foreach ($question->getReponses() as $reponse) {
                if ($reponse->getEstCorrecte()) {
                    $correctAnswer = $reponse;
                }
                if ($reponse->getId() == $studentAnswerId) {
                    $studentAnswer = $reponse;
                }
            }

            $questionPoints = $question->getPoints() ?? 1;
            $totalPoints += $questionPoints;

            if ($studentAnswer && $studentAnswer->getEstCorrecte()) {
                $isCorrect = true;
                $earnedPoints += $questionPoints;
            }

            $results[] = [
                'question' => $question,
                'studentAnswer' => $studentAnswer,
                'correctAnswer' => $correctAnswer,
                'isCorrect' => $isCorrect,
                'points' => $questionPoints,
            ];
        }

        $score = $totalPoints > 0 ? ($earnedPoints / $totalPoints) * 100 : 0;

        // TODO: Sauvegarder la tentative en base de données

    } catch (\Exception $e) {
        $this->addFlash('error', 'Erreur lors de la soumission du quiz');
        return $this->redirectToRoute('quiz_show_complet', ['id' => $id]);
    }

    return $this->render('etudiant/quiz/results.html.twig', [
        'quiz' => $quiz,
        'results' => $results,
        'totalPoints' => $totalPoints,
        'earnedPoints' => $earnedPoints,
        'score' => round($score, 2),
        'student' => $student,
        'current_user' => $student,
        'is_etudiant' => true,
        'is_admin' => false,
        'is_enseignant' => false,
    ]);
}

private function buildStudentQuizAccessData(Etudiant $student): array
{
    $enrolledCourses = $student->getCoursInscrits()->toArray();
    $courseIds = array_values(array_unique(array_map(
        static fn ($course): int => (int) $course->getId(),
        $enrolledCourses
    )));

    $linkedQuizIds = [];
    foreach ($enrolledCourses as $course) {
        foreach ($course->getContenus() as $contenu) {
            if (!$contenu->isEstPublic()) {
                continue;
            }

            $resources = $contenu->getRessourcesForDisplay();
            $quizId = $resources['quiz_id'] ?? null;
            if (is_numeric($quizId) && (int) $quizId > 0) {
                $linkedQuizIds[] = (int) $quizId;
            }
        }
    }

    return [
        'course_ids' => $courseIds,
        'linked_quiz_ids' => array_values(array_unique($linkedQuizIds)),
    ];
}

private function studentCanAccessQuiz(Etudiant $student, Quiz $quiz): bool
{
    $accessData = $this->buildStudentQuizAccessData($student);
    $quizId = (int) ($quiz->getId() ?? 0);
    $courseId = $quiz->getIdCours();

    if ($quizId > 0 && in_array($quizId, $accessData['linked_quiz_ids'], true)) {
        return true;
    }

    return $courseId !== null && in_array((int) $courseId, $accessData['course_ids'], true);
}
}
