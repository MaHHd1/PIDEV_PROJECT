<?php
// src/Repository/ScoreRepository.php
namespace App\Repository;

use App\Entity\Score;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ScoreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Score::class);
    }

    public function findBySearchAndSort(string $search, string $sortBy = 'dateCorrection', string $order = 'DESC'): array
    {
        $qb = $this->createQueryBuilder('sc')
            ->leftJoin('sc.soumission', 's')
            ->leftJoin('s.evaluation', 'e');

        if (!empty($search)) {
            $qb->where('s.idEtudiant LIKE :search')
               ->orWhere('e.titre LIKE :search')
               ->orWhere('sc.statutCorrection LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        $allowedSortFields = ['note', 'dateCorrection', 'statutCorrection'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'dateCorrection';
        }

        $order = strtoupper($order);
        if (!in_array($order, ['ASC', 'DESC'])) {
            $order = 'DESC';
        }

        $qb->orderBy('sc.' . $sortBy, $order);

        return $qb->getQuery()->getResult();
    }
}