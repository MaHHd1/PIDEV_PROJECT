<?php

namespace App\Controller;

use App\Entity\Etudiant;
use App\Service\AuthChecker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/student')]
class StudentDashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_student_dashboard')]
    public function dashboard(AuthChecker $authChecker): Response
    {
        // Check if user is logged in
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        // Récupérer l'utilisateur connecté
        $user = $authChecker->getCurrentUser();

        // Check if user is a student
        if (!$user instanceof Etudiant) {
            $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux étudiants.');
            return $this->redirectToRoute('app_home');
        }

        $student = $user;

        return $this->render('student/dashboard.html.twig', [
            'student' => $student,
        ]);
    }

    #[Route('/courses', name: 'app_student_courses')]
    public function courses(AuthChecker $authChecker): Response
    {
        // Check if user is logged in
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        // Récupérer l'utilisateur connecté
        $user = $authChecker->getCurrentUser();

        // Check if user is a student
        if (!$user instanceof Etudiant) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('app_home');
        }

        $student = $user;

        // For now, show a placeholder
        return $this->render('student/courses.html.twig', [
            'student' => $student,
        ]);
    }

    #[Route('/grades', name: 'app_student_grades')]
    public function grades(AuthChecker $authChecker): Response
    {
        // Check if user is logged in
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        // Récupérer l'utilisateur connecté
        $user = $authChecker->getCurrentUser();

        // Check if user is a student
        if (!$user instanceof Etudiant) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('app_home');
        }

        $student = $user;

        // For now, show a placeholder
        return $this->render('student/grades.html.twig', [
            'student' => $student,
        ]);
    }
}