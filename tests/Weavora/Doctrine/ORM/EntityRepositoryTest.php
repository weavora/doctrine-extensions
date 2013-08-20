<?php

namespace Weavora\Doctrine\ORM;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr;
use Weavora\TestCase;

class EntityRepositoryTest extends TestCase
{
    /**
     * @var EntityManager
     */
    protected $em;

    protected function setUp()
    {
        parent::setUp();

        $this->em = $this->mockEntityManager();
    }

    /**
     * @return \Mockery\Mock|EntityManager
     */
    protected function mockEntityManager()
    {
        $em = \Mockery::mock('\Doctrine\ORM\EntityManager')->shouldIgnoreMissing();
        return $em;
    }

    /**
     * @return \Mockery\MockInterface|\Doctrine\ORM\Mapping\ClassMetadata
     */
    protected function mockEntityClassMetadata()
    {
        return \Mockery::mock('\Doctrine\ORM\Mapping\ClassMetadata');
    }

    public function testFilter()
    {
        $repository = new EntityRepository($this->em, $this->mockEntityClassMetadata());
        $this->assertInstanceOf('\Weavora\Doctrine\ORM\EntityQueryBuilder', $repository->filter());
    }
}