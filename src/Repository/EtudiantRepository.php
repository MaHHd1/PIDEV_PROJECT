<?php

namespace App\Repository;

use App\Entity\Etudiant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EtudiantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Etudiant::class);
    }

    public function findByMatricule(string $matricule): ?Etudiant
    {
        return $this->findOneBy(['matricule' => $matricule]);
    }

    public function findActiveStudents(): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.statut = :statut')
            ->setParameter('statut', 'actif')
            ->orderBy('e.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByNiveauAndSpecialisation(string $niveau, string $specialisation): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.niveauEtude = :niveau')
            ->andWhere('e.specialisation = :specialisation')
            ->setParameter('niveau', $niveau)
            ->setParameter('specialisation', $specialisation)
            ->orderBy('e.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countByStatut(): array
    {
        return $this->createQueryBuilder('e')
            ->select('e.statut, COUNT(e.id) as count')
            ->groupBy('e.statut')
            ->getQuery()
            ->getResult();
    }
}