<?php
// src/Repository/SoumissionRepository.php
namespace App\Repository;

use App\Entity\Soumission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SoumissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Soumission::class);
    }

    public function findBySearchAndSort(string $search, string $sortBy = 'dateSoumission', string $order = 'DESC'): array
    {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.evaluation', 'e');

        if (!empty($search)) {
            $qb->where('s.idEtudiant LIKE :search')
               ->orWhere('e.titre LIKE :search')
               ->orWhere('s.statut LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        $allowedSortFields = ['idEtudiant', 'dateSoumission', 'statut'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'dateSoumission';
        }

        $order = strtoupper($order);
        if (!in_array($order, ['ASC', 'DESC'])) {
            $order = 'DESC';
        }

        $qb->orderBy('s.' . $sortBy, $order);

        return $qb->getQuery()->getResult();
    }
}