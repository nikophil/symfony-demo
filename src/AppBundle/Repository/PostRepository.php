<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Repository;

use AppBundle\Entity\Post;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;

/**
 * This custom Doctrine repository contains some methods which are useful when
 * querying for blog post information.
 *
 * See http://symfony.com/doc/current/book/doctrine.html#custom-repository-classes
 *
 * @author Ryan Weaver <weaverryan@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class PostRepository extends EntityRepository
{
    /**
     * @param int $page
     *
     * @return Pagerfanta
     */
    public function findLatest($page = 1)
    {
        $query = $this->getEntityManager()
            ->createQuery('
                SELECT p, t
                FROM AppBundle:Post p
                LEFT JOIN p.tags t
                WHERE p.publishedAt <= :now
                ORDER BY p.publishedAt DESC
            ')
            ->setParameter('now', new \DateTime())
        ;

        return $this->createPaginator($query, $page);
    }

    private function createPaginator(Query $query, $page)
    {
        $paginator = new Pagerfanta(new DoctrineORMAdapter($query));
        $paginator->setMaxPerPage(Post::NUM_ITEMS);
        $paginator->setCurrentPage($page);

        return $paginator;
    }

    public function getPostsWithPreparedQuery()
    {
        $stmt = $this->getEntityManager()
            ->getConnection()
            ->prepare('
                SELECT p.*
                FROM symfony_demo_post p
                ORDER BY p.publishedAt DESC
            ');

        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getPostsWithNativeQuery($id)
    {
        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(Post::class, 'p');

        return $this->getEntityManager()
            ->createNativeQuery('
                SELECT p.*
                FROM symfony_demo_post p
                WHERE p.id < :id
                ORDER BY p.publishedAt DESC
            ', $rsm)
            ->setParameter('id', $id)
            ->getResult();
    }


    public function getPostsWithRawSQL($id)
    {
        return $this->getEntityManager()
            ->getConnection()
            ->executeQuery('
                SELECT p.*
                FROM symfony_demo_post p
                WHERE p.id < '.$id.'
                ORDER BY p.publishedAt DESC
            ')  // ouch...
            ->fetchAll();
    }

    public function getPostsWithRawDQL($id)
    {
        return $this->getEntityManager()
            ->createQuery('
                SELECT p
                FROM AppBundle:Post p
                WHERE p.id < :id
                ORDER BY p.publishedAt DESC
            ')
            ->setParameter('id', $id)
            ->getResult()
        ;
    }

    public function getPostsAsListWithCustomHydrator($id)
    {
        return $this->createQueryBuilder('p')
            ->select('p.id, p.title')
            ->where('p.id < :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult('KeyValueListHydrator');
    }

    public function getPostAsPartialObjects($id)
    {
        return $this->createQueryBuilder('p')
            ->select('partial p.{id, title}')
            ->where('p.id < :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult();
    }

    public function getOnlyPostTitles($id)
    {
        return $this->createQueryBuilder('p')
            ->select('p.title')
            ->where('p.id < :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult();
    }

    public function getPostsAsObjects($id)
    {
        return $this->createQueryBuilder('p')
            ->where('p.id < :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult();
    }
}
