<?php

namespace App\Repository;

use App\Entity\CommentaireForum;
use App\Entity\ForumDiscussion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CommentaireForumRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommentaireForum::class);
    }

    public function byForumVisible(ForumDiscussion $forum): array
    {
        // on cache "supprime"
        return $this->createQueryBuilder('c')
            ->andWhere('c.forum = :f')
            ->setParameter('f', $forum)
            ->andWhere('c.statut != :sup')
            ->setParameter('sup', 'supprime')
            ->orderBy('c.datePublication', 'ASC')
            ->getQuery()->getResult();
    }
}
