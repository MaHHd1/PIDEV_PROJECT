<?php

namespace App\Controller;

use App\Entity\Etudiant;
use App\Form\EtudiantType;
use App\Repository\EtudiantRepository;
use App\Service\AuthChecker;
use App\Service\SearchService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/etudiant')]
class EtudiantController extends AbstractController
{
    #[Route('/dashboard', name: 'app_etudiant_dashboard', methods: ['GET'])]
    public function dashboard(AuthChecker $authChecker): Response
    {
        // Vérifier si l'utilisateur est connecté
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        // Vérifier si l'utilisateur est un étudiant
        if (!$authChecker->isEtudiant()) {
            $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux étudiants.');
            return $this->redirectToRoute('app_home');
        }

        $student = $authChecker->getCurrentUser();

        return $this->render('etudiant/dashboard.html.twig', [
            'student' => $student,
        ]);
    }

    #[Route('/', name: 'app_etudiant_index', methods: ['GET', 'POST'])]
    public function index(
        EtudiantRepository $etudiantRepository,
        AuthChecker $authChecker,
        Request $request,
        SearchService $searchService
    ): Response
    {
        // Vérifier si l'utilisateur est connecté
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        // Si l'utilisateur est un étudiant, le rediriger vers son dashboard
        if ($authChecker->isEtudiant()) {
            return $this->redirectToRoute('app_etudiant_dashboard');
        }

        // Seuls les administrateurs peuvent voir la liste des étudiants
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
        $filterOptions = $searchService->getDistinctValues('etudiant', $etudiantRepository);

        // Perform search if any criteria, otherwise get all
        $etudiants = !empty(array_filter($criteria))
            ? $searchService->searchEtudiants($criteria, $etudiantRepository)
            : $etudiantRepository->findAll();

        return $this->render('etudiant/index.html.twig', [
            'etudiants' => $etudiants,
            'criteria' => $criteria,
            'niveaux' => $filterOptions['niveaux'] ?? [],
            'specialisations' => $filterOptions['specialisations'] ?? [],
        ]);
    }

    #[Route('/new', name: 'app_etudiant_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        AuthChecker $authChecker
    ): Response
    {
        // Vérifier si l'utilisateur est connecté
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        // Seuls les administrateurs peuvent créer des étudiants
        if (!$authChecker->isAdmin()) {
            $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux administrateurs.');
            return $this->redirectToRoute('app_home');
        }

        $etudiant = new Etudiant();
        $form = $this->createForm(EtudiantType::class, $etudiant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('motDePasse')->getData();
            $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);
            $etudiant->setMotDePasse($hashedPassword);

            $entityManager->persist($etudiant);
            $entityManager->flush();

            $this->addFlash('success', 'Étudiant créé avec succès!');

            return $this->redirectToRoute('app_etudiant_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/etudiant_new.html.twig', [
            'etudiant' => $etudiant,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_etudiant_show', methods: ['GET'])]
    public function show(
        Etudiant $etudiant,
        AuthChecker $authChecker
    ): Response
    {
        // Vérifier si l'utilisateur est connecté
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        // Seuls les administrateurs peuvent voir les détails des étudiants
        if (!$authChecker->isAdmin()) {
            $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux administrateurs.');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('etudiant/show.html.twig', [
            'etudiant' => $etudiant,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_etudiant_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Etudiant $etudiant,
        EntityManagerInterface $entityManager,
        AuthChecker $authChecker
    ): Response
    {
        // Vérifier si l'utilisateur est connecté
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        // Seuls les administrateurs peuvent modifier les étudiants
        if (!$authChecker->isAdmin()) {
            $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux administrateurs.');
            return $this->redirectToRoute('app_home');
        }

        $form = $this->createForm(EtudiantType::class, $etudiant, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->has('motDePasse') && $form->get('motDePasse')->getData()) {
                $plainPassword = $form->get('motDePasse')->getData();
                $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);
                $etudiant->setMotDePasse($hashedPassword);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Étudiant modifié avec succès!');

            return $this->redirectToRoute('app_etudiant_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/etudiant_edit.html.twig', [
            'etudiant' => $etudiant,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_etudiant_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Etudiant $etudiant,
        EntityManagerInterface $entityManager,
        AuthChecker $authChecker
    ): Response
    {
        // Vérifier si l'utilisateur est connecté
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        // Seuls les administrateurs peuvent supprimer des étudiants
        if (!$authChecker->isAdmin()) {
            $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux administrateurs.');
            return $this->redirectToRoute('app_home');
        }

        if ($this->isCsrfTokenValid('delete'.$etudiant->getId(), $request->request->get('_token'))) {
            $entityManager->remove($etudiant);
            $entityManager->flush();

            $this->addFlash('success', 'Étudiant supprimé avec succès!');
        }

        return $this->redirectToRoute('app_etudiant_index', [], Response::HTTP_SEE_OTHER);
    }
}