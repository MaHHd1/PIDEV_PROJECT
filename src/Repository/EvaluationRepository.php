<?php
// src/Repository/EvaluationRepository.php
namespace App\Repository;

use App\Entity\Evaluation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EvaluationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evaluation::class);
    }

    public function findBySearchAndSort(string $search, string $sortBy = 'dateCreation', string $order = 'DESC'): array
    {
        $qb = $this->createQueryBuilder('e');

        if (!empty($search)) {
            $qb->where('e.titre LIKE :search')
               ->orWhere('e.typeEvaluation LIKE :search')
               ->orWhere('e.statut LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Validation du champ de tri
        $allowedSortFields = ['titre', 'typeEvaluation', 'dateCreation', 'dateLimite', 'noteMax', 'statut'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'dateCreation';
        }

        // Validation de l'ordre
        $order = strtoupper($order);
        if (!in_array($order, ['ASC', 'DESC'])) {
            $order = 'DESC';
        }

        $qb->orderBy('e.' . $sortBy, $order);

        return $qb->getQuery()->getResult();
    }
}