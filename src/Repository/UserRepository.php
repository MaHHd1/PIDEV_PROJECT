<?php

namespace App\Repository;

use App\Entity\Utilisateur; // ← je préfère Utilisateur pour cohérence francophone
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<Utilisateur>
 *
 * @method Utilisateur|null find($id, $lockMode = null, $lockVersion = null)
 * @method Utilisateur|null findOneBy(array $criteria, array $orderBy = null)
 * @method Utilisateur[]    findAll()
 * @method Utilisateur[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UtilisateurRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Utilisateur::class);
    }

    /**
     * Upgrade (rehash) le mot de passe de l'utilisateur automatiquement au fil du temps
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof Utilisateur) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    // ────────────────────────────────────────────────
    // Méthodes utiles pour le dashboard admin
    // ────────────────────────────────────────────────

    /**
     * Compte le nombre d'utilisateurs ayant un rôle spécifique
     */
    public function countByRole(string $role): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('JSON_CONTAINS(u.roles, :roleJson)')
            ->setParameter('roleJson', json_encode($role))
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère les X derniers utilisateurs ayant un rôle spécifique
     */
    public function findRecentByRole(string $role, int $limit = 5): array
    {
        return $this->createQueryBuilder('u')
            ->where('JSON_CONTAINS(u.roles, :roleJson)')
            ->setParameter('roleJson', json_encode($role))
            ->orderBy('u.id', 'DESC') // ou u.createdAt si tu as ce champ
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre total d'utilisateurs (raccourci pratique)
     */
    public function countTotal(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}