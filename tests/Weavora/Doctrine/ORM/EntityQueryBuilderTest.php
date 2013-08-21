<?php

namespace Weavora\Doctrine\ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;
use Weavora\Doctrine\ORM\EntityQueryBuilder;
use Weavora\TestCase;

class EntityQueryBuilderTest extends TestCase
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var EntityRepository
     */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->em = $this->mockEntityManager();
        $this->repository = $this->mockRepository();
    }

    /**
     * @param string $entityClassName
     * @return EntityRepository
     */
    protected function mockRepository($entityClassName = 'Weavora\Entity\Post')
    {
        $repository = \Mockery::mock('Doctrine\ORM\EntityRepository');
        $repository->shouldReceive('getClassName')->andReturn($entityClassName);
        return $repository;
    }

    /**
     * @return \Mockery\Mock|EntityManager
     */
    protected function mockEntityManager()
    {
        $em = \Mockery::mock('\Doctrine\ORM\EntityManager')->shouldIgnoreMissing();
        $em->shouldReceive('getExpressionBuilder')->andReturn(new Expr());
        return $em;
    }


    public function testAppendParameters()
    {
        $queryBuilder = new EntityQueryBuilder($this->em, $this->repository);

        // params should be initially empty
        $this->assertEqualsQueryParameters(array(), $queryBuilder->getParameters());

        // append new param
        $queryBuilder->appendParameters(array(
            'param1' => 'value1'
        ));
        $this->assertEqualsQueryParameters(array(
            'param1' => 'value1'
        ), $queryBuilder->getParameters());

        // append second param should not override exists
        $queryBuilder->appendParameters(array(
            'param2' => 'value2'
        ));
        $this->assertEqualsQueryParameters(array(
            'param1' => 'value1',
            'param2' => 'value2'
        ), $queryBuilder->getParameters());

        // append param could change values for already added params
        $queryBuilder->appendParameters(array(
            'param1' => 'value1_changed'
        ));
        $this->assertEqualsQueryParameters(array(
            'param1' => 'value1_changed',
            'param2' => 'value2'
        ), $queryBuilder->getParameters());

        // there are still possible to override all params and append new
        $queryBuilder->setParameters(array(
            'param1' => 'value1',
            'param2' => 'value2',
        ));
        $queryBuilder->appendParameters(array(
            'param3' => 'value3'
        ));
        $this->assertEqualsQueryParameters(array(
            'param1' => 'value1',
            'param2' => 'value2',
            'param3' => 'value3'
        ), $queryBuilder->getParameters());
    }

    public function testGetSetRepository()
    {
        $postRepository = $this->mockRepository('Entity\Post');
        $queryBuilder = new EntityQueryBuilder($this->em, $postRepository);
        $this->assertEquals($postRepository, $queryBuilder->getRepository());

        $commentRepository = $this->mockRepository('Entity\Comment');
        $queryBuilder->setRepository($commentRepository);
        $this->assertEquals($commentRepository, $queryBuilder->getRepository());
    }

    public function testGetEntityAlias()
    {
        $queryBuilder = new EntityQueryBuilder($this->em, $this->mockRepository('Entity\Post'));
        $this->assertEquals('Post', $queryBuilder->getEntityAlias());

        $queryBuilder = new EntityQueryBuilder($this->em, $this->mockRepository('Post'));
        $this->assertEquals('Post', $queryBuilder->getEntityAlias());

        $queryBuilder = new EntityQueryBuilder($this->em, $this->mockRepository('Entity_Post'));
        $this->assertEquals('Entity_Post', $queryBuilder->getEntityAlias());

        $queryBuilder = new EntityQueryBuilder($this->em, $this->mockRepository('Very\Long\Class\Name\Space\EntityClassName'));
        $this->assertEquals('EntityClassName', $queryBuilder->getEntityAlias());
    }

    public function testFilterByStatement()
    {
        $queryBuilder = new EntityQueryBuilder($this->em, $this->mockRepository('Entity\Post'));

        $queryBuilder->filterByStatement('Post.title = :title', array('title' => 'Post 1'));
        $this->assertEquals('SELECT Post FROM Entity\Post Post WHERE Post.title = :title', $queryBuilder->getDQL());
        $this->assertEqualsQueryParameters(array(
            'title' => 'Post 1'
        ), $queryBuilder->getParameters());

        $queryBuilder->filterByStatement('Post.is_active = 1 OR Post.is_external = 1');
        $this->assertEquals('SELECT Post FROM Entity\Post Post WHERE Post.title = :title AND (Post.is_active = 1 OR Post.is_external = 1)', $queryBuilder->getDQL());
        $this->assertEqualsQueryParameters(array(
            'title' => 'Post 1'
        ), $queryBuilder->getParameters());
    }

    public function testFilterByColumn()
    {
        $queryBuilder = new EntityQueryBuilder($this->em, $this->mockRepository('Entity\Post'));

        // EQUALS filter
        $queryBuilder->filterByColumn('Post.title', 'Post 1');
        $this->assertEquals('SELECT Post FROM Entity\Post Post WHERE Post.title = :p1', $queryBuilder->getDQL());
        $this->assertEqualsQueryParameters(array(
            'p1' => 'Post 1'
        ), $queryBuilder->getParameters());

        // IS NULL filter
        $queryBuilder->filterByColumn('Post.authorId', null);
        $this->assertEquals('SELECT Post FROM Entity\Post Post WHERE Post.title = :p1 AND Post.authorId IS NULL', $queryBuilder->getDQL());
        $this->assertEqualsQueryParameters(array(
            'p1' => 'Post 1'
        ), $queryBuilder->getParameters());

        // Optional EQUALS filter
        $queryBuilder->filterByColumn('Post.categoryId', null, false);
        $this->assertEquals('SELECT Post FROM Entity\Post Post WHERE Post.title = :p1 AND Post.authorId IS NULL', $queryBuilder->getDQL());
        $this->assertEqualsQueryParameters(array(
            'p1' => 'Post 1'
        ), $queryBuilder->getParameters());

        // IN filter
        $queryBuilder->filterByColumn('Post.publishStatus', array('published', 'approved'));
        $this->assertEquals('SELECT Post FROM Entity\Post Post WHERE Post.title = :p1 AND Post.authorId IS NULL AND Post.publishStatus IN(\'published\', \'approved\')', $queryBuilder->getDQL());
        $this->assertEqualsQueryParameters(array(
            'p1' => 'Post 1'
        ), $queryBuilder->getParameters());
    }

    public function testLimit()
    {
        $queryBuilder = new EntityQueryBuilder($this->em, $this->mockRepository('Entity\Post'));

        $queryBuilder->limit(10, 15);
        $this->assertEquals($queryBuilder->getMaxResults(), 10);
        $this->assertEquals($queryBuilder->getFirstResult(), 15);

        $queryBuilder->setFirstResult(25);
        $queryBuilder->limit(50);
        $this->assertEquals($queryBuilder->getMaxResults(), 50);
        $this->assertEquals($queryBuilder->getFirstResult(), 25);
    }

    public function testPaginate()
    {
        $queryBuilder = new EntityQueryBuilder($this->em, $this->mockRepository('Entity\Post'));
        $queryBuilder->paginate(1, 15);

        $this->assertEquals($queryBuilder->getMaxResults(), 15);
        $this->assertEquals($queryBuilder->getFirstResult(), 0);

        $queryBuilder->paginate(5, 20);

        $this->assertEquals($queryBuilder->getMaxResults(), 20);
        $this->assertEquals($queryBuilder->getFirstResult(), 80);

        $this->setExpectedException('Doctrine\ORM\ORMInvalidArgumentException');
        $queryBuilder->paginate(0, 10);
    }


    protected function assertEqualsQueryParameters($expectedParameters, ArrayCollection $actualParameters, $message = '')
    {
        $actualParametersArray = array();
        /** @var $actualParameter \Doctrine\ORM\Query\Parameter */
        foreach ($actualParameters as $actualParameter) {
            $actualParametersArray[$actualParameter->getName()] = $actualParameter->getValue();
        }

        $this->assertEquals($actualParametersArray, $expectedParameters, $message);
    }
}