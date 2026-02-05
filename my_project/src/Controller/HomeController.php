<?php

namespace App\Controller;

use App\Repository\EtudiantRepository;
use App\Repository\EnseignantRepository;
use App\Repository\AdministrateurRepository;
use App\Service\AuthChecker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        EtudiantRepository $etudiantRepo,
        EnseignantRepository $enseignantRepo,
        AdministrateurRepository $adminRepo,
        Request $request  // Add Request parameter
    ): Response
    {
        $session = $request->getSession();

        // You can still check if logged in, but this template will handle both cases
        $stats = [
            'etudiants' => count($etudiantRepo->findAll()),
            'enseignants' => count($enseignantRepo->findAll()),
            'administrateurs' => count($adminRepo->findAll()),
        ];

        return $this->render('home/index.html.twig', [
            'stats' => $stats,
        ]);
    }
}