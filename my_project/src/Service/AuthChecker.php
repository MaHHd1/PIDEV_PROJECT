<?php

namespace App\Service;

use App\Entity\Etudiant;
use App\Entity\Enseignant;
use App\Entity\Administrateur;
use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class AuthChecker
{
    private $requestStack;
    private UtilisateurRepository $userRepository;
    private EntityManagerInterface $entityManager;
    private MailerInterface $mailer;

    public function __construct(
        RequestStack $requestStack,
        UtilisateurRepository $userRepository,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer
    ) {
        $this->requestStack = $requestStack;
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
    }

    // Helper method to get session
    private function getSession()
    {
        return $this->requestStack->getSession();
    }

    // Check if user is logged in
    public function isLoggedIn(): bool
    {
        return $this->getSession()->get('logged_in', false) === true;
    }

    // Try to login with email/password
    public function login(string $email, string $password): ?Utilisateur
    {
        $user = $this->userRepository->findByEmail($email);

        if (!$user) {
            return null;
        }

        // Verify password
        if (!password_verify($password, $user->getMotDePasse())) {
            return null;
        }

        // Update last login
        $user->setLastLogin(new \DateTime());
        $this->entityManager->flush();

        // Store in session
        $session = $this->getSession();
        $session->set('user_id', $user->getId());
        $session->set('user_email', $user->getEmail());
        $session->set('user_type', $user->getType());
        $session->set('user_name', $user->getNomComplet());
        $session->set('logged_in', true);

        return $user;
    }

    // Logout user
    public function logout(): void
    {
        $this->getSession()->clear();
    }

    // Get current user - CORRIGÉ pour retourner l'entité spécifique
    public function getCurrentUser(): ?object
    {
        if (!$this->isLoggedIn()) {
            return null;
        }

        $userId = $this->getSession()->get('user_id');
        $userType = $this->getSession()->get('user_type');

        // Retourner l'entité spécifique selon le type
        switch ($userType) {
            case 'etudiant':
                return $this->entityManager->getRepository(Etudiant::class)->find($userId);
            case 'enseignant':
                return $this->entityManager->getRepository(Enseignant::class)->find($userId);
            case 'administrateur':
                return $this->entityManager->getRepository(Administrateur::class)->find($userId);
            default:
                return $this->userRepository->find($userId);
        }
    }

    // AJOUTEZ CES MÉTHODES :
    public function isEtudiant(): bool
    {
        $user = $this->getCurrentUser();
        return $user instanceof Etudiant;
    }

    public function isEnseignant(): bool
    {
        $user = $this->getCurrentUser();
        return $user instanceof Enseignant;
    }

    public function isAdmin(): bool
    {
        if (!$this->isLoggedIn()) {
            return false;
        }

        $user = $this->getCurrentUser();
        return $user instanceof Administrateur;
    }

    public function getUserType(): ?string
    {
        $user = $this->getCurrentUser();
        return $user ? $user->getType() : null;
    }

    // Get user name for display
    public function getUserName(): ?string
    {
        return $this->getSession()->get('user_name');
    }

    // Create password reset token WITH EMAIL
    public function createPasswordReset(string $email): ?array
    {
        $user = $this->userRepository->findByEmail($email);

        if (!$user) {
            // For security, don't reveal if email exists
            return null;
        }

        // Generate unique token
        $token = bin2hex(random_bytes(16));

        // Token expires in 1 hour
        $expiresAt = new \DateTime('+1 hour');

        // Save to database
        $user->setResetToken($token);
        $user->setResetTokenExpiresAt($expiresAt);

        $this->entityManager->flush();

        // SEND EMAIL
        $this->sendResetEmail($user, $token, $expiresAt);

        return [
            'user' => $user,
            'token' => $token,
            'expires_at' => $expiresAt
        ];
    }

    private function sendResetEmail(Utilisateur $user, string $token, \DateTime $expiresAt): void
    {
        // Use your application URL - you might want to get this from configuration
        $appUrl = $_ENV['APP_URL'] ?? 'http://localhost:8000';
        $resetLink = $appUrl . '/reset-password/' . $token;

        $email = (new Email())
            ->from('no-reply@yourdomain.com')
            ->to($user->getEmail())
            ->subject('Réinitialisation de votre mot de passe')
            ->html($this->getEmailTemplate($user, $resetLink, $expiresAt));

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            // Log the error but don't throw it - user should still get feedback
            error_log('Failed to send reset email: ' . $e->getMessage());
        }
    }

    private function getEmailTemplate(Utilisateur $user, string $resetLink, \DateTime $expiresAt): string
    {
        return '
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .button { 
                        background-color: #007bff; 
                        color: white; 
                        padding: 10px 20px; 
                        text-decoration: none; 
                        border-radius: 5px; 
                        display: inline-block; 
                    }
                </style>
            </head>
            <body>
                <div class="container">
                    <h2>Réinitialisation de mot de passe</h2>
                    <p>Bonjour ' . htmlspecialchars($user->getNomComplet()) . ',</p>
                    <p>Vous avez demandé à réinitialiser votre mot de passe.</p>
                    <p>Cliquez sur le lien ci-dessous pour procéder :</p>
                    <p>
                        <a href="' . $resetLink . '" class="button">Réinitialiser mon mot de passe</a>
                    </p>
                    <p>Ce lien expirera le : ' . $expiresAt->format('d/m/Y à H:i') . '</p>
                    <p>Si vous n\'avez pas demandé cette réinitialisation, veuillez ignorer cet email.</p>
                    <hr>
                    <p><small>Ceci est un email automatique, merci de ne pas y répondre.</small></p>
                </div>
            </body>
            </html>
        ';
    }

    // Check if reset token is valid - OPTIMIZED VERSION
    public function isValidResetToken(string $token): ?Utilisateur
    {
        // Use the repository method instead of loading all users
        return $this->userRepository->findValidResetToken($token);
    }

    // Reset password with token
    public function resetPasswordWithToken(string $token, string $newPassword): bool
    {
        $user = $this->isValidResetToken($token);

        if (!$user) {
            return false;
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $user->setMotDePasse($hashedPassword);
        $user->setResetToken(null);
        $user->setResetTokenExpiresAt(null);

        $this->entityManager->flush();

        return true;
    }

    // Change password (when already logged in)
    public function changePassword(Utilisateur $user, string $currentPassword, string $newPassword): bool
    {
        if (!password_verify($currentPassword, $user->getMotDePasse())) {
            return false;
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $user->setMotDePasse($hashedPassword);

        $this->entityManager->flush();

        return true;
    }
}