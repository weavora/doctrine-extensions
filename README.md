Doctrine2 ORM Extensions [![Build Status](https://secure.travis-ci.org/weavora/doctrine-extensions.png)](http://travis-ci.org/weavora/doctrine-extensions)
==============================

[![Total Downloads](https://poser.pugx.org/weavora/doctrine-extensions/downloads.png)](https://packagist.org/packages/weavora/doctrine-extensions)
[![Latest Stable Version](https://poser.pugx.org/weavora/doctrine-extensions/v/stable.png)](https://packagist.org/packages/weavora/doctrine-extensions)

Extensions of standard Doctrine2 ORM classes to more easy data filtering (kind of named scopes)

Usage
-----

```php
<?php

namespace Acme\BlogBundle\Entity;

use Weavora\Doctrine\ORM as ORM;

class PostQueryBuilder extends ORM\EntityQueryBuilder
{
    public function published()
    {
        return $this->filterByColumn('publishStatus', Post::STATUS_PUBLISHED);
    }

    public function paginate($page = 1, $itemsPerPage = 10)
    {
        $offset = ($page > 0) ? ($page - 1) * $itemsPerPage : 0;
        return $this->limit($itemsPerPage, $offset);
    }

    public function recentFirst()
    {
        return $this->orderBy('Post.createdAt', 'DESC');
    }
}

class PostRepository extends ORM\EntityRepository
{
    public function filter()
    {
        return new PostQueryBuilder($this->getEntityManager(), $this);
    }

    public function findRecent(Category $category = null)
    {
        return $this
            ->filter()
                ->published()
                ->limit(10)
                ->recentFirst()
            ->fetchAll();
    }

    public function findByCategory(Category $category, $page = 1, $itemsPerPage = 10)
    {
        return $this
            ->filter()
                ->filterByColumn('category', $category)
                ->paginate($page, $itemsPerPage)
                ->recentFirst()
            ->fetchAll();
    }

    public function countByAuthor(Author $author)
    {
        return $this
            ->filter()
                ->select('COUNT(Post.id)')
                ->filterByColumn('author', $author)
                ->groupBy('Post.id')
            ->fetchScalar();
    }
}

```

About
=====

Requirements
------------

- Any flavor of PHP 5.3 or above should do
- [optional] PHPUnit 3.5+ to execute the test suite (phpunit --version)

Submitting bugs and feature requests
------------------------------------

Bugs and feature request are tracked on [GitHub](https://github.com/weavora/doctrine-extensions/issues)

Author
------

Weavora LLC - <http://weavora.com> - <http://twitter.com/weavora><br />
See also the list of [contributors](https://github.com/weavora/doctrine-extensions/contributors) which participated in this project.

License
-------

This library is licensed under the MIT License - see the `LICENSE` file for details