<?php

namespace App\Service;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class AuthChecker
{
    private $requestStack;
    private UtilisateurRepository $userRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        RequestStack $requestStack,
        UtilisateurRepository $userRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->requestStack = $requestStack;
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
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

    // Get current user
    public function getCurrentUser(): ?Utilisateur
    {
        if (!$this->isLoggedIn()) {
            return null;
        }

        $userId = $this->getSession()->get('user_id');
        return $this->userRepository->find($userId);
    }

    // Get user name for display
    public function getUserName(): ?string
    {
        return $this->getSession()->get('user_name');
    }

    // Create password reset token
    public function createPasswordReset(string $email): ?array
    {
        $user = $this->userRepository->findByEmail($email);

        if (!$user) {
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

        return [
            'user' => $user,
            'token' => $token,
            'expires_at' => $expiresAt
        ];
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