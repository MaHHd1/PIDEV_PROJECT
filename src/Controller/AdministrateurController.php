<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Repository\EvenementRepository;
use App\Repository\ParticipationEvenementRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class AdministrateurController extends AbstractController
{
    #[Route('/dashboard', name: 'app_admin_dashboard')]
    public function dashboard(
        UtilisateurRepository $utilisateurRepo,
        EvenementRepository $evenementRepo,
        ParticipationEvenementRepository $participationRepo
    ): Response
    {
        // Statistiques principales
        $stats = [
            // Utilisateurs
            'total_users'     => (int) $utilisateurRepo->count([]),
            'etudiants'       => (int) $utilisateurRepo->countByRole('ROLE_ETUDIANT'),
            'enseignants'     => (int) $utilisateurRepo->countByRole('ROLE_ENSEIGNANT'),
            'administrateurs' => (int) $utilisateurRepo->countByRole('ROLE_ADMIN'),

            // Événements
            'total_evenements'   => (int) $evenementRepo->count([]),
            'evenements_a_venir' => (int) $evenementRepo->countByStatut(Evenement::STATUT_PLANIFIE), // ← utilise la constante de l'entité si tu en as

            // Participations
            'total_participations' => (int) $participationRepo->count([]),
        ];

        // Listes récentes (5 derniers)
        try {
            $recentEtudiants   = $utilisateurRepo->findRecentByRole('ROLE_ETUDIANT', 5)   ?? [];
            $recentEnseignants = $utilisateurRepo->findRecentByRole('ROLE_ENSEIGNANT', 5) ?? [];
            $recentAdmins      = $utilisateurRepo->findRecentByRole('ROLE_ADMIN', 5)      ?? [];

            $recentEvenements     = $evenementRepo->findRecent(5)     ?? [];
            $recentParticipations = $participationRepo->findRecent(5) ?? [];
        } catch (\Exception $e) {
            // En cas d'erreur (ex: méthode non implémentée ou base vide), fallback
            $recentEtudiants = $recentEnseignants = $recentAdmins = $recentEvenements = $recentParticipations = [];
            $this->addFlash('warning', 'Certaines statistiques récentes n’ont pas pu être chargées.');
        }

        return $this->render('admin/dashboard.html.twig', [
            'stats'                => $stats,
            'recentEtudiants'      => $recentEtudiants,
            'recentEnseignants'    => $recentEnseignants,
            'recentAdmins'         => $recentAdmins,
            'recentEvenements'     => $recentEvenements,
            'recentParticipations' => $recentParticipations,
        ]);
    }
}