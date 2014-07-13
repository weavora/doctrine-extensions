<?php

namespace Weavora\Doctrine\ORM;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository as DoctrineEntityRepository;
use Doctrine\ORM\ORMInvalidArgumentException;
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
     * Apply filter by column
     *
     * Example:
     *   $queryBuilder->filterByColumn('Post.title', 'Superman');
     *   $queryBuilder->filterByColumn('Post.categoryId', array(1,2,3));
     *   $queryBuilder->filterByColumn('Post.subtitle', null);
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

        if (is_array($value) || $value instanceof \Iterator) {
            return $this->filterByStatement($this->expr()->in($columnName, $value));
        }

        if ($value === null) {
            return $this->filterByStatement($this->expr()->isNull($columnName));
        }

        $parameterName = $this->findUnusedParameterName();
        return $this->filterByStatement($columnName . ' = :' . $parameterName, array($parameterName => $value));
    }

    /**
     * Apply statement filter
     *
     * Example:
     *   $queryBuilder->filterByStatement('Post.title = :title', ['title' => 'Superman']);
     *
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
     * Limit max number of results
     *
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
     * Limit results with selected page
     *
     * @param int $page Page number 1 .. n
     * @param int $itemsPerPage Item per page
     * @return $this
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     */
    public function paginate($page = 1, $itemsPerPage = 10)
    {
        if ($page < 1) {
            throw new ORMInvalidArgumentException("Incorrect page number: {$page}. Only positive page number allowed.");
        }

        return $this->limit($itemsPerPage, ($page - 1) * $itemsPerPage);
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
        $result = $this->getQuery()->getSingleResult();
        return is_array($result) ? array_shift($result) : $result;
    }

    /**
     * Generate parameter name
     *
     * @return string
     */
    protected function findUnusedParameterName()
    {
        $index = 0;
        $usedParameters = $this->getUsedParameterNames();
        do {
            $index++;
            $parameterName = 'p'. $index;
        } while (in_array($parameterName, $usedParameters));

        return $parameterName;
    }

    /**
     * Get parameters that are already used
     *
     * @return array
     */
    private function getUsedParameterNames(){
        $parameters = $this->getParameters();
        $names = [];
        foreach ($parameters as $p) {
            array_push($names, $p->getName());
        }
        return $names;
    }

    /**
     * Get parameter values
     *
     * @return array
     */
    private function getUsedParameterValues(){
        $parameters = $this->getParameters();

        $names = [];
        foreach ($parameters as $p) {
            array_push($names, $p->getValue());
        }
        return $names;
    }

    /**
     * Get SQL query with parameters
     *
     * @return string
     */
    function showQuery()
    {
        return vsprintf(str_replace('?', '%s', $this->getQuery()->getSql()), $this->getUsedParameterValues());
    }
}