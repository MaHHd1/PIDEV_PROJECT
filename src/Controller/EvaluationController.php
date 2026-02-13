<?php
// src/Controller/EvaluationController.php
namespace App\Controller;

use App\Entity\Evaluation;
use App\Form\EvaluationType;
use App\Repository\EvaluationRepository;
use App\Service\AuthChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/evaluation')]
class EvaluationController extends AbstractController
{
    #[Route('/', name: 'app_evaluation_index', methods: ['GET'])]
    public function index(
        Request $request, 
        EvaluationRepository $evaluationRepository,
        AuthChecker $authChecker
    ): Response
    {
        // ========== AUTHENTICATION & AUTHORIZATION ==========
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }
        
        // CORRECTION : Vérifiez que l'utilisateur est bien un Enseignant
        $user = $authChecker->getCurrentUser();
        
        // Vérifiez la classe de l'utilisateur
        if (!$user instanceof \App\Entity\Enseignant) {
            $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux enseignants.');
            return $this->redirectToRoute('app_home');
        }
        
        // Cast en Enseignant
        $enseignant = $user;
        
        // Récupération des paramètres de recherche et tri
        $search = $request->query->get('search', '');
        $sortBy = $request->query->get('sort', 'dateCreation');
        $order = $request->query->get('order', 'DESC');
        
        // Recherche et tri - filter by current teacher
        $evaluations = $evaluationRepository->findBySearchAndSort($search, $sortBy, $order, $enseignant->getId());
        
        return $this->render('evaluation/index.html.twig', [
            'evaluations' => $evaluations,
            'search' => $search,
            'sortBy' => $sortBy,
            'order' => $order,
            'enseignant' => $enseignant, // Passé au template
        ]);
    }

    #[Route('/new', name: 'app_evaluation_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        AuthChecker $authChecker
    ): Response
    {
        // ========== AUTHENTICATION & AUTHORIZATION ==========
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }
        
        // CORRECTION : Vérifiez que l'utilisateur est bien un Enseignant
        $user = $authChecker->getCurrentUser();
        
        if (!$user instanceof \App\Entity\Enseignant) {
            $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux enseignants.');
            return $this->redirectToRoute('app_home');
        }
        
        // Cast en Enseignant
        $enseignant = $user;
        
        $evaluation = new Evaluation();
        
        // Set the teacher as the creator
        $evaluation->setIdEnseignant($enseignant->getId());
        $evaluation->setDateCreation(new \DateTime());
        
        $form = $this->createForm(EvaluationType::class, $evaluation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Ensure teacher ID is set
            if (!$evaluation->getIdEnseignant()) {
                $evaluation->setIdEnseignant($enseignant->getId());
            }
            
            // Set default status if not set
            if (!$evaluation->getStatut()) {
                $evaluation->setStatut('ouverte');
            }
            
            $entityManager->persist($evaluation);
            $entityManager->flush();

            $this->addFlash('success', 'Évaluation créée avec succès !');
            return $this->redirectToRoute('app_evaluation_index');
        }

        return $this->render('evaluation/new.html.twig', [
            'evaluation' => $evaluation,
            'form' => $form->createView(),
            'enseignant' => $enseignant, // Passé au template
        ]);
    }

 #[Route('/{id}/show', name: 'app_evaluation_show', methods: ['GET'])]
public function show(
    int $id,
    AuthChecker $authChecker,
    EvaluationRepository $evaluationRepository
): Response
{
    if (!$authChecker->isLoggedIn()) {
        return $this->redirectToRoute('app_login');
    }
    
    $user = $authChecker->getCurrentUser();
    
    if (!$user instanceof \App\Entity\Enseignant) {
        $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux enseignants.');
        return $this->redirectToRoute('app_home');
    }
    
    $enseignant = $user;
    $evaluation = $evaluationRepository->find($id);
    
    if (!$evaluation) {
        $this->addFlash('error', 'Évaluation non trouvée.');
        return $this->redirectToRoute('app_evaluation_index');
    }
    
    // ✅ CORRECTION : Conversion en string
    if ($evaluation->getIdEnseignant() !== (string)$enseignant->getId()) {
        $this->addFlash('error', 'Accès non autorisé. Cette évaluation ne vous appartient pas.');
        return $this->redirectToRoute('app_evaluation_index');
    }
    
    return $this->render('evaluation/show.html.twig', [
        'evaluation' => $evaluation,
        'enseignant' => $enseignant,
    ]);
}

    #[Route('/{id}/edit', name: 'app_evaluation_edit', methods: ['GET', 'POST'])]
public function edit(
    Request $request, 
    int $id,
    EntityManagerInterface $entityManager,
    AuthChecker $authChecker,
    EvaluationRepository $evaluationRepository
): Response
{
    if (!$authChecker->isLoggedIn()) {
        return $this->redirectToRoute('app_login');
    }
    
    $user = $authChecker->getCurrentUser();
    
    if (!$user instanceof \App\Entity\Enseignant) {
        $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux enseignants.');
        return $this->redirectToRoute('app_home');
    }
    
    $enseignant = $user;
    $evaluation = $evaluationRepository->find($id);
    
    if (!$evaluation) {
        $this->addFlash('error', 'Évaluation non trouvée.');
        return $this->redirectToRoute('app_evaluation_index');
    }
    
    // ✅ CORRECTION : Conversion en string
    if ($evaluation->getIdEnseignant() !== (string)$enseignant->getId()) {
        $this->addFlash('error', 'Accès non autorisé. Cette évaluation ne vous appartient pas.');
        return $this->redirectToRoute('app_evaluation_index');
    }
    
    $form = $this->createForm(EvaluationType::class, $evaluation);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->flush();
        $this->addFlash('success', 'Évaluation modifiée avec succès !');
        return $this->redirectToRoute('app_evaluation_index');
    }

    return $this->render('evaluation/edit.html.twig', [
        'evaluation' => $evaluation,
        'form' => $form->createView(),
        'enseignant' => $enseignant,
    ]);
}
    #[Route('/{id}/delete', name: 'app_evaluation_delete', methods: ['POST'])]
public function delete(
    Request $request, 
    int $id,
    EntityManagerInterface $entityManager,
    AuthChecker $authChecker,
    EvaluationRepository $evaluationRepository
): Response
{
    if (!$authChecker->isLoggedIn()) {
        return $this->redirectToRoute('app_login');
    }
    
    $user = $authChecker->getCurrentUser();
    
    if (!$user instanceof \App\Entity\Enseignant) {
        $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux enseignants.');
        return $this->redirectToRoute('app_home');
    }
    
    $enseignant = $user;
    $evaluation = $evaluationRepository->find($id);
    
    if (!$evaluation) {
        $this->addFlash('error', 'Évaluation non trouvée.');
        return $this->redirectToRoute('app_evaluation_index');
    }
    
    // ✅ CORRECTION : Conversion en string
    if ($evaluation->getIdEnseignant() !== (string)$enseignant->getId()) {
        $this->addFlash('error', 'Accès non autorisé. Cette évaluation ne vous appartient pas.');
        return $this->redirectToRoute('app_evaluation_index');
    }
    
    if ($this->isCsrfTokenValid('delete'.$evaluation->getId(), $request->request->get('_token'))) {
        $entityManager->remove($evaluation);
        $entityManager->flush();
        $this->addFlash('success', 'Évaluation supprimée avec succès !');
    }

    return $this->redirectToRoute('app_evaluation_index');
}
}