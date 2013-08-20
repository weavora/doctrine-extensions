<?php

namespace Weavora\Doctrine\ORM;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository as DoctrineEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;

class EntityQueryBuilder extends QueryBuilder
{
    /**
     * @var DoctrineEntityRepository
     */
    protected $repository;

    public function __construct(EntityManager $em, DoctrineEntityRepository $repository)
    {
        parent::__construct($em);
        $this->setRepository($repository);
    }

    /**
     * @param \Doctrine\ORM\EntityRepository $repository
     */
    public function setRepository($repository)
    {
        $this->repository = $repository;
        $this->useRepositoryEntity();
    }

    /**
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * Get related entity class name
     *
     * @return string
     */
    protected function getEntityClassName()
    {
        return $this->getRepository()->getClassName();
    }

    /**
     * Get related entity alias used in query
     * Will be used entity class name without namespace
     *
     * @return string
     */
    public function getEntityAlias()
    {
        $className = explode('\\', $this->getEntityClassName());
        return end($className);
    }

    /**
     * Configure query to work with repository entity
     */
    protected function useRepositoryEntity()
    {
        $this->select($this->getEntityAlias());
        $this->from($this->getEntityClassName(), $this->getEntityAlias());
    }

    /**
     * Add where statement to filter by column
     *
     * @param string $columnName
     * @param mixed $value
     * @param bool $strict in case of null value, should we compare column with null (true) or skip filtering (false)
     *
     * @return $this
     */
    public function filterByColumn($columnName, $value, $strict = true)
    {
        if ($value === null && !$strict) {
            return $this;
        }

        $columnPath = $this->getEntityAlias() . '.' . $columnName;

        if (is_array($value) || $value instanceof \Iterator) {
            return $this->filterByStatement($this->expr()->in($columnPath, $value));
        }

        if ($value === null) {
            return $this->filterByStatement($this->expr()->isNull($columnPath));
        }

        $parameterName = $this->findUnusedParameterName();
        return $this->filterByStatement($columnPath . ' = :' . $parameterName, array($parameterName => $value));
    }

    /**
     * @param string $statement DQL
     * @param array $parameters
     * @return $this
     */
    public function filterByStatement($statement, $parameters = array())
    {
        $this->andWhere($statement);
        $this->appendParameters($parameters);
        return $this;
    }

    /**
     * @param $maxResults
     * @param null $offset
     * @return $this
     */
    public function limit($maxResults, $offset = null)
    {
        $this->setMaxResults($maxResults);
        if ($offset !== null) {
            $this->setFirstResult($offset);
        }
        return $this;
    }

    /**
     * @param array $parameters
     * @return $this
     */
    public function appendParameters($parameters)
    {
        if (is_array($parameters)) {
            foreach ($parameters as $key => $value) {
                $this->setParameter($key, $value);
            }
        }
        return $this;
    }

    /**
     * Fetch all entities
     *
     * @param array $parameters
     * @return array
     */
    public function fetchAll($parameters = array())
    {
        $this->appendParameters($parameters);
        return $this->getQuery()->getResult();
    }

    /**
     * Fetch first entity
     *
     * @param array $parameters
     * @return array
     */
    public function fetchOne($parameters = array())
    {
        $this->appendParameters($parameters);
        $this->limit(1, 0);
        return $this->getQuery()->getOneOrNullResult();
    }

    /**
     * Fetch first column of first result row
     *
     * @param array $parameters
     * @return mixed
     */
    public function fetchScalar($parameters = array())
    {
        $this->appendParameters($parameters);
        $this->limit(1, 0);
        return $this->getQuery()->getScalarResult();
    }

    /**
     * Find unused parameter name
     *
     * @return string
     */
    protected function findUnusedParameterName()
    {
        $parameters = $this->getParameters();
        $index = 0;

        do {
            $index++;
            $parameterName = 'p' . $index;
        } while ($parameters->containsKey($parameterName));

        return $parameterName;
    }
}