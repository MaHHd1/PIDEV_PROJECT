<?php

namespace App\Controller;

use App\Form\LoginType;
use App\Form\ForgotPasswordType;
use App\Form\ResetPasswordType;
use App\Form\ChangePasswordType;
use App\Service\AuthChecker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractController
{
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    #[Route('/login', name: 'app_login')]
    public function login(Request $request, AuthChecker $authChecker): Response
    {
        // If already logged in, go to home
        if ($authChecker->isLoggedIn()) {
            $this->addFlash('info', 'Vous êtes déjà connecté.');
            return $this->redirectToRoute('app_home');
        }

        $form = $this->createForm(LoginType::class);
        $form->handleRequest($request);

        // Get any authentication errors from the session
        $error = null;
        $session = $this->requestStack->getSession();
        $lastError = $session->get('_security.last_error');
        if ($lastError) {
            $error = $lastError;
            $session->remove('_security.last_error');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $email = $data['email'];
            $password = $data['password'];

            $user = $authChecker->login($email, $password);

            if ($user) {
                $this->addFlash('success', 'Connexion réussie ! Bienvenue ' . $user->getPrenom() . '!');
                return $this->redirectToRoute('app_home');
            } else {
                // Set error for template
                $error = 'Email ou mot de passe incorrect.';
            }
        } elseif ($form->isSubmitted()) {
            // Form has validation errors
            $error = 'Veuillez vérifier les informations saisies.';
        }

        return $this->render('auth/login.html.twig', [
            'form' => $form->createView(),
            'error' => $error
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
        // If already logged in, no need for password reset
        if ($authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_home');
        }

        $form = $this->createForm(ForgotPasswordType::class);
        $form->handleRequest($request);

        $message = null;
        $error = null;

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $data = $form->getData();
                $email = $data['email'];

                $result = $authChecker->createPasswordReset($email);

                if ($result) {
                    $message = [
                        'type' => 'success',
                        'text' => 'Un email avec les instructions de réinitialisation a été envoyé à votre adresse.',
                        'note' => 'Vérifiez votre boîte de réception (et vos spams).'
                    ];
                } else {
                    $message = [
                        'type' => 'info',
                        'text' => 'Si votre email est enregistré, vous recevrez un lien de réinitialisation.'
                    ];
                }
            } else {
                $error = 'Veuillez vérifier les informations saisies.';
            }
        }

        return $this->render('auth/forgot_password.html.twig', [
            'form' => $form->createView(),
            'message' => $message,
            'error' => $error
        ]);
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function resetPassword(Request $request, AuthChecker $authChecker, string $token): Response
    {
        // If already logged in, redirect to change password page
        if ($authChecker->isLoggedIn()) {
            return $this->redirectToRoute('app_change_password');
        }

        $user = $authChecker->isValidResetToken($token);

        if (!$user) {
            $this->addFlash('error', 'Lien invalide ou expiré.');
            return $this->redirectToRoute('app_forgot_password');
        }

        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        $error = null;

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $password = $data['password'];

            $success = $authChecker->resetPasswordWithToken($token, $password);

            if ($success) {
                $this->addFlash('success', 'Mot de passe changé avec succès. Connectez-vous.');
                return $this->redirectToRoute('app_login');
            } else {
                $error = 'Erreur lors de la réinitialisation. Réessayez.';
            }
        }

        return $this->render('auth/reset_password.html.twig', [
            'form' => $form->createView(),
            'token' => $token,
            'user' => $user,
            'error' => $error
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

        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        $error = null;

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $currentPassword = $data['currentPassword'];
            $newPassword = $data['newPassword'];

            $success = $authChecker->changePassword($user, $currentPassword, $newPassword);

            if ($success) {
                $this->addFlash('success', 'Mot de passe changé avec succès.');
                return $this->redirectToRoute('app_home');
            } else {
                $error = 'Mot de passe actuel incorrect.';
            }
        }

        return $this->render('auth/change_password.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'error' => $error
        ]);
    }

    // Temporary route for signup - redirects to login with message
    #[Route('/signup', name: 'app_signup')]
    public function signup(): Response
    {
        $this->addFlash('info', 'Pour créer un compte, contactez l\'administration.');
        return $this->redirectToRoute('app_login');
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