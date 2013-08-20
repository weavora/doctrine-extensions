<?php

namespace Weavora\Doctrine\ORM;

class EntityRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * @return EntityQueryBuilder|\Doctrine\ORM\QueryBuilder
     */
    public function filter()
    {
        return new EntityQueryBuilder($this->getEntityManager(), $this);
    }
}