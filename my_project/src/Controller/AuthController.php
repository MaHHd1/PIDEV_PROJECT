<?php

namespace App\Controller;

use App\Service\AuthChecker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(Request $request, AuthChecker $authChecker): Response
    {
        // If already logged in, go to home
        if ($authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_home');
        }

        $error = null;

        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $password = $request->request->get('password');

            $user = $authChecker->login($email, $password);

            if ($user) {
                $this->addFlash('success', 'Connexion réussie !');
                return $this->redirectToRoute('app_home');
            } else {
                $error = 'Email ou mot de passe incorrect.';
            }
        }

        return $this->render('auth/login.html.twig', [
            'error' => $error,
            'email' => $request->request->get('email', '')
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(AuthChecker $authChecker): Response
    {
        $authChecker->logout();
        $this->addFlash('success', 'Vous avez été déconnecté.');
        return $this->redirectToRoute('app_login');
    }

    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function forgotPassword(Request $request, AuthChecker $authChecker): Response
    {
        $message = null;
        $error = null;

        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');

            $result = $authChecker->createPasswordReset($email);

            if ($result) {
                // Don't show the link anymore - it's sent by email
                $message = [
                    'type' => 'success',
                    'text' => 'Un email avec les instructions de réinitialisation a été envoyé à votre adresse.',
                    'note' => 'Vérifiez votre boîte de réception (et vos spams).'
                ];
            } else {
                // For security, show the same message whether email exists or not
                $message = [
                    'type' => 'info',
                    'text' => 'Si votre email est enregistré, vous recevrez un lien de réinitialisation.'
                ];
            }
        }

        return $this->render('auth/forgot_password.html.twig', [
            'message' => $message,
            'error' => $error
        ]);
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function resetPassword(Request $request, AuthChecker $authChecker, string $token): Response
    {
        $user = $authChecker->isValidResetToken($token);

        if (!$user) {
            $this->addFlash('error', 'Lien invalide ou expiré.');
            return $this->redirectToRoute('app_forgot_password');
        }

        $error = null;

        if ($request->isMethod('POST')) {
            $password = $request->request->get('password');
            $confirmPassword = $request->request->get('confirm_password');

            if ($password !== $confirmPassword) {
                $error = 'Les mots de passe ne correspondent pas.';
            } elseif (strlen($password) < 8) {
                $error = 'Le mot de passe doit avoir au moins 8 caractères.';
            } else {
                $success = $authChecker->resetPasswordWithToken($token, $password);

                if ($success) {
                    $this->addFlash('success', 'Mot de passe changé. Connectez-vous.');
                    return $this->redirectToRoute('app_login');
                } else {
                    $error = 'Erreur. Réessayez.';
                }
            }
        }

        return $this->render('auth/reset_password.html.twig', [
            'token' => $token,
            'error' => $error,
            'user' => $user
        ]);
    }

    #[Route('/change-password', name: 'app_change_password')]
    public function changePassword(Request $request, AuthChecker $authChecker): Response
    {
        // Must be logged in
        if (!$authChecker->isLoggedIn()) {
            $this->addFlash('error', 'Connectez-vous d\'abord.');
            return $this->redirectToRoute('app_login');
        }

        $user = $authChecker->getCurrentUser();
        $error = null;

        if ($request->isMethod('POST')) {
            $currentPassword = $request->request->get('current_password');
            $newPassword = $request->request->get('new_password');
            $confirmPassword = $request->request->get('confirm_password');

            if ($newPassword !== $confirmPassword) {
                $error = 'Les nouveaux mots de passe ne correspondent pas.';
            } elseif (strlen($newPassword) < 8) {
                $error = '8 caractères minimum.';
            } else {
                $success = $authChecker->changePassword($user, $currentPassword, $newPassword);

                if ($success) {
                    $this->addFlash('success', 'Mot de passe changé.');
                    return $this->redirectToRoute('app_home');
                } else {
                    $error = 'Mot de passe actuel incorrect.';
                }
            }
        }

        return $this->render('auth/change_password.html.twig', [
            'error' => $error,
            'user' => $user
        ]);
    }

    // DEBUG ROUTE - REMOVE THIS IN PRODUCTION
    #[Route('/test-reset-token', name: 'app_test_reset_token')]
    public function testResetToken(AuthChecker $authChecker): Response
    {
        // Test with a known email
        $email = 'sophie.martin@email.com';

        $result = $authChecker->createPasswordReset($email);

        if ($result) {
            return new Response(
                '<h3>Token créé avec succès !</h3>' .
                '<p><strong>Email:</strong> ' . $email . '</p>' .
                '<p><strong>Token:</strong> ' . $result['token'] . '</p>' .
                '<p><strong>Expire le:</strong> ' . $result['expires_at']->format('Y-m-d H:i:s') . '</p>' .
                '<p><strong>ID Utilisateur:</strong> ' . $result['user']->getId() . '</p>' .
                '<p><strong>Nom:</strong> ' . $result['user']->getNomComplet() . '</p>' .
                '<hr>' .
                '<p><a href="/reset-password/' . $result['token'] . '" class="btn btn-primary">Test reset link</a></p>'
            );
        }

        return new Response('<div class="alert alert-danger">Utilisateur non trouvé ou erreur de création de token</div>');
    }
}