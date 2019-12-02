<?php

namespace App\Repository;

use App\Entity\PostRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method PostRequest|null find($id, $lockMode = null, $lockVersion = null)
 * @method PostRequest|null findOneBy(array $criteria, array $orderBy = null)
 * @method PostRequest[]    findAll()
 * @method PostRequest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PostRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PostRequest::class);
    }

    // /**
    //  * @return PostRequest[] Returns an array of PostRequest objects
    //  */
    
    public function findByPostId($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.postid = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByUserId($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.userid = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
    

    /*
    public function findOneBySomeField($value): ?PostRequest
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
