<?php

namespace App\Controller;

use App\Entity\Enseignant;
use App\Form\EnseignantType;
use App\Repository\EnseignantRepository;
use App\Service\AuthChecker;
use App\Service\SearchService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/enseignant')]
class EnseignantController extends AbstractController
{
    #[Route('/dashboard', name: 'app_enseignant_dashboard', methods: ['GET'])]
    public function dashboard(AuthChecker $authChecker): Response
    {
        // Authentication check
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        // Check if user is a teacher
        if (!$authChecker->isEnseignant()) {
            $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux enseignants.');
            return $this->redirectToRoute('app_home');
        }

        $enseignant = $authChecker->getCurrentUser();

        return $this->render('enseignant/dashboard.html.twig', [
            'enseignant' => $enseignant,
        ]);
    }

    #[Route('/', name: 'app_enseignant_index', methods: ['GET', 'POST'])]
    public function index(
        EnseignantRepository $enseignantRepository,
        AuthChecker $authChecker,
        Request $request,
        SearchService $searchService
    ): Response
    {
        // Authentication check
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        // If user is a teacher, redirect to dashboard
        if ($authChecker->isEnseignant()) {
            return $this->redirectToRoute('app_enseignant_dashboard');
        }

        // Only admins can see the teacher list
        if (!$authChecker->isAdmin()) {
            $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux administrateurs.');
            return $this->redirectToRoute('app_home');
        }

        // Get search criteria from request
        $criteria = [];
        if ($request->getMethod() === 'POST') {
            $criteria = $request->request->all();
        } else {
            $criteria = $request->query->all();
        }

        // Get filter options
        $filterOptions = $searchService->getDistinctValues('enseignant', $enseignantRepository);

        // Perform search if any criteria, otherwise get with pagination
        if (!empty(array_filter($criteria))) {
            $enseignants = $searchService->searchEnseignants($criteria, $enseignantRepository);
        } else {
            // Use pagination for performance
            $limit = 20;
            $page = max(1, (int) $request->query->get('page', 1));
            $offset = ($page - 1) * $limit;
            $enseignants = $enseignantRepository->findBy([], ['dateCreation' => 'DESC'], $limit, $offset);
        }

        // Get total count for pagination info
        $totalEnseignants = $enseignantRepository->count([]);

        // Since we want all users in one list, redirect to utilisateurs page
        return $this->redirectToRoute('app_administrateur_utilisateurs');
    }

    #[Route('/new', name: 'app_enseignant_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, AuthChecker $authChecker): Response
    {
        // Authentication check
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        $enseignant = new Enseignant();
        $form = $this->createForm(EnseignantType::class, $enseignant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash password
            $plainPassword = $form->get('motDePasse')->getData();
            $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);
            $enseignant->setMotDePasse($hashedPassword);

            $entityManager->persist($enseignant);
            $entityManager->flush();

            $this->addFlash('success', 'Enseignant créé avec succès!');

            return $this->redirectToRoute('app_enseignant_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/enseignant_new.html.twig', [
            'enseignant' => $enseignant,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_enseignant_show', methods: ['GET'])]
    public function show(Enseignant $enseignant, AuthChecker $authChecker): Response
    {
        // Authentication check
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('enseignant/show.html.twig', [
            'enseignant' => $enseignant,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_enseignant_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Enseignant $enseignant, EntityManagerInterface $entityManager, AuthChecker $authChecker): Response
    {
        // Authentication check
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(EnseignantType::class, $enseignant, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Update password only if provided
            if ($form->has('motDePasse') && $form->get('motDePasse')->getData()) {
                $plainPassword = $form->get('motDePasse')->getData();
                $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);
                $enseignant->setMotDePasse($hashedPassword);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Enseignant modifié avec succès!');

            return $this->redirectToRoute('app_enseignant_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/enseignant_edit.html.twig', [
            'enseignant' => $enseignant,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_enseignant_delete', methods: ['POST'])]
    public function delete(Request $request, Enseignant $enseignant, EntityManagerInterface $entityManager, AuthChecker $authChecker): Response
    {
        // Authentication check
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        if ($this->isCsrfTokenValid('delete'.$enseignant->getId(), $request->request->get('_token'))) {
            $entityManager->remove($enseignant);
            $entityManager->flush();

            $this->addFlash('success', 'Enseignant supprimé avec succès!');
        }

        return $this->redirectToRoute('app_enseignant_index', [], Response::HTTP_SEE_OTHER);
    }
}