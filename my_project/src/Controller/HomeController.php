<?php

namespace App\Controller;

use App\Repository\EtudiantRepository;
use App\Repository\EnseignantRepository;
use App\Repository\AdministrateurRepository;
use App\Service\AuthChecker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        EtudiantRepository $etudiantRepo,
        EnseignantRepository $enseignantRepo,
        AdministrateurRepository $adminRepo,
        AuthChecker $authChecker
    ): Response
    {
        // Use count() instead of count(findAll()) for performance
        $stats = [
            'etudiants' => $etudiantRepo->count([]),
            'enseignants' => $enseignantRepo->count([]),
            'administrateurs' => $adminRepo->count([]),
        ];

        // Pass user info to template
        $user = $authChecker->getCurrentUser();

        // Check user type using AuthChecker
        $isAdmin = $authChecker->isAdmin();
        $isEnseignant = $authChecker->isEnseignant();
        $isEtudiant = $authChecker->isEtudiant();

        return $this->render('home/index.html.twig', [
            'stats' => $stats,
            'current_user' => $user,  // This should be null when not logged in
            'is_admin' => $isAdmin,
            'is_enseignant' => $isEnseignant,
            'is_etudiant' => $isEtudiant,
        ]);
    }
}