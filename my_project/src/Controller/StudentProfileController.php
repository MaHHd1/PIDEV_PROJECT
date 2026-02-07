<?php

namespace App\Controller;

use App\Entity\Etudiant;
use App\Form\EtudiantType;
use App\Service\AuthChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/student')]
class StudentProfileController extends AbstractController
{
    #[Route('/profile/edit', name: 'app_student_edit_profile')]
    public function editProfile(
        Request $request,
        AuthChecker $authChecker,
        EntityManagerInterface $entityManager
    ): Response
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

        // Récupérer l'étudiant connecté
        $student = $authChecker->getCurrentUser();

        if (!$student instanceof Etudiant) {
            $this->addFlash('error', 'Utilisateur non trouvé.');
            return $this->redirectToRoute('app_home');
        }

        // Créer le formulaire avec l'option is_edit
        $form = $this->createForm(EtudiantType::class, $student, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Mettre à jour le mot de passe seulement si fourni
            if ($form->has('motDePasse') && $form->get('motDePasse')->getData()) {
                $plainPassword = $form->get('motDePasse')->getData();
                $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);
                $student->setMotDePasse($hashedPassword);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Profil mis à jour avec succès !');

            return $this->redirectToRoute('app_student_profile');
        }

        return $this->render('etudiant/edit_profile.html.twig', [
            'student' => $student,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/profile', name: 'app_student_profile')]
    public function profile(AuthChecker $authChecker): Response
    {
        // Vérifier si l'utilisateur est connecté
        if (!$authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }

        // Vérifier si l'utilisateur est un étudiant
        if (!$authChecker->isEtudiant()) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('app_home');
        }

        $student = $authChecker->getCurrentUser();

        return $this->render('etudiant/profile.html.twig', [
            'student' => $student,
        ]);
    }

    #[Route('/dashboard', name: 'app_student_dashboard')]
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
}