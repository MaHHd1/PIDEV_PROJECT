<?php

namespace App\Repository;

use App\Entity\Quiz;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Quiz>
 */
class QuizRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Quiz::class);
    }

    public function searchQuiz(string $search): array
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.titre LIKE :search OR q.description LIKE :search')
            ->setParameter('search', '%'.$search.'%')
            ->orderBy('q.dateCreation', 'DESC')  // ✅ Corrigé : camelCase pour Quiz
            ->getQuery()
            ->getResult();
    }

    public function findQuizComplet(int $id): ?Quiz
    {
        return $this->createQueryBuilder('q')
            ->leftJoin('q.questions', 'question')
            ->addSelect('question')
            ->leftJoin('question.reponses', 'reponse')
            ->addSelect('reponse')
            ->andWhere('q.id = :id')
            ->setParameter('id', $id)
            ->orderBy('question.ordre_affichage', 'ASC')  // ✅ Corrigé : underscore pour Question
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByCreateur(int $idCreateur): array
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.idCreateur = :idCreateur')  // ✅ Corrigé : camelCase pour Quiz
            ->setParameter('idCreateur', $idCreateur)
            ->orderBy('q.dateCreation', 'DESC')  // ✅ Corrigé : camelCase pour Quiz
            ->getQuery()
            ->getResult();
    }

    public function findAvailableQuizzes(): array
    {
        $now = new \DateTime();
        
        return $this->createQueryBuilder('q')
            ->andWhere('q.dateDebutDisponibilite IS NULL OR q.dateDebutDisponibilite <= :now')  // ✅ Corrigé
            ->andWhere('q.dateFinDisponibilite IS NULL OR q.dateFinDisponibilite >= :now')  // ✅ Corrigé
            ->setParameter('now', $now)
            ->orderBy('q.dateCreation', 'DESC')  // ✅ Corrigé
            ->getQuery()
            ->getResult();
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('q')
            ->select('COUNT(q.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}