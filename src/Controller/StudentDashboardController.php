<?php

namespace App\Controller;

use App\Entity\Etudiant;
use App\Form\ChangePasswordType;
use App\Service\AuthChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/etudiant')]
class StudentDashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_etudiant_dashboard', methods: ['GET'])]
    public function dashboard(AuthChecker $authChecker): Response
    {
        // Check if user is logged in
        if (!$authChecker->isLoggedIn()) {
            $this->addFlash('error', 'Veuillez vous connecter pour accéder au dashboard.');
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

        return $this->render('etudiant/dashboard.html.twig', [
            'student' => $student,
            'current_user' => $student,
            'is_etudiant' => true,
            'is_admin' => false,
            'is_enseignant' => false,
        ]);
    }

    #[Route('/change-password', name: 'app_etudiant_change_password', methods: ['GET', 'POST'])]
    public function studentChangePassword(
        Request $request,
        AuthChecker $authChecker,
        EntityManagerInterface $entityManager
    ): Response
    {
        // Vérifier si l'utilisateur est connecté
        if (!$authChecker->isLoggedIn()) {
            $this->addFlash('error', 'Veuillez vous connecter pour changer votre mot de passe.');
            return $this->redirectToRoute('app_login');
        }

        // Vérifier si l'utilisateur est un étudiant
        if (!$authChecker->isEtudiant()) {
            $this->addFlash('error', 'Accès non autorisé. Cette section est réservée aux étudiants.');
            return $this->redirectToRoute('app_home');
        }

        $student = $authChecker->getCurrentUser();

        if (!$student instanceof Etudiant) {
            $this->addFlash('error', 'Utilisateur non trouvé.');
            return $this->redirectToRoute('app_home');
        }

        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // Get the form data correctly - use getData() which returns the array
                $formData = $form->getData();

                // Access the form data directly from the form fields
                $currentPassword = $form->get('currentPassword')->getData();
                $newPassword = $form->get('newPassword')->getData();

                // Vérifier le mot de passe actuel
                if (!password_verify($currentPassword, $student->getMotDePasse())) {
                    $this->addFlash('error', 'Le mot de passe actuel est incorrect.');
                } else {
                    // Hasher le nouveau mot de passe
                    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
                    $student->setMotDePasse($hashedPassword);

                    $entityManager->flush();
                    $this->addFlash('success', 'Votre mot de passe a été changé avec succès !');

                    return $this->redirectToRoute('app_etudiant_profile');
                }
            } else {
                // Form has validation errors
                // Collect all errors for flash message
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }

                if (!empty($errors)) {
                    $this->addFlash('error', implode(' ', $errors));
                } else {
                    $this->addFlash('error', 'Veuillez corriger les erreurs dans le formulaire. Les données saisies ne sont pas valides.');
                }
            }
        }

        return $this->render('etudiant/change_password.html.twig', [
            'form' => $form->createView(),
            'student' => $student,
            'current_user' => $student,
            'is_etudiant' => true,
            'is_admin' => false,
            'is_enseignant' => false,
        ]);
    }

    #[Route('/courses', name: 'app_etudiant_courses', methods: ['GET'])]
    public function courses(AuthChecker $authChecker): Response
    {
        // Check if user is logged in
        if (!$authChecker->isLoggedIn()) {
            $this->addFlash('error', 'Veuillez vous connecter pour accéder à vos cours.');
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
        return $this->render('etudiant/courses.html.twig', [
            'student' => $student,
            'current_user' => $student,
            'is_etudiant' => true,
            'is_admin' => false,
            'is_enseignant' => false,
        ]);
    }

    #[Route('/grades', name: 'app_etudiant_grades', methods: ['GET'])]
    public function grades(AuthChecker $authChecker): Response
    {
        // Check if user is logged in
        if (!$authChecker->isLoggedIn()) {
            $this->addFlash('error', 'Veuillez vous connecter pour accéder à vos notes.');
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
        return $this->render('etudiant/grades.html.twig', [
            'student' => $student,
            'current_user' => $student,
            'is_etudiant' => true,
            'is_admin' => false,
            'is_enseignant' => false,
        ]);
    }
}
