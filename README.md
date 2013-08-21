Doctrine2 Extensions [![Build Status](https://secure.travis-ci.org/weavora/doctrine-extensions.png)](http://travis-ci.org/weavora/doctrine-extensions)
==============================

[![Total Downloads](https://poser.pugx.org/weavora/doctrine-extensions/downloads.png)](https://packagist.org/packages/weavora/doctrine-extensions)
[![Latest Stable Version](https://poser.pugx.org/weavora/doctrine-extensions/v/stable.png)](https://packagist.org/packages/weavora/doctrine-extensions)

This library extend base Doctrine2 classes with some useful things.

ORM Extensions
--------------

Doctrine force developers to create entity repositories that will contain business logic to retrieve entities.
Most probably you will use query builder inside repository for that purposes.
The issue is that standard query build has quite overall API and doesn't provide such useful shortcuts like named scopes,
single method to apply custom criteria with parameters and etc.

This library tries to fix missing things and make your life a little bit easier.

**How to organize repositories**

Let's say you want to create blog application. Probably you create post entity which have references to category,
author and comments. And now you're thinking about approach to organize your repositories.

Common issues with repositories:
 - you have to duplicate code as soon as you have duplicated conditions
 - method for few conditions or ordering looks massive

You can solve first issue with custom query builder per entity. It will also hide your criteria details from repository
which not really need to know that.

To solve issue with huge  and not really well descriptive methods we create extension for query builder with some helpful
shortcuts to make code more readable.

Example of how PostQueryBuilder & PostRepository could look like:

```php
<?php

namespace Acme\BlogBundle\Entity;

use Weavora\Doctrine\ORM as ORM;

/**
 * Custom query class for Post entity
 * Contains useful criteria set for posts filtering
 */
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
        return $this->orderBy('Post.publishedAt', 'DESC');
    }
}

/**
 * Post entity repository
 * Contains methods for fetch posts
 */
class PostRepository extends ORM\EntityRepository
{
    /**
     * Instantiate custom query builder
     * @return PostQueryBuilder
     */
    public function filter()
    {
        return new PostQueryBuilder($this->getEntityManager(), $this);
    }

    /**
     * Find 10 recent posts
     *
     * @return Post[]
     */
    public function findRecent()
    {
        return $this
            ->filter() // use PostQueryBuilder
                ->published() // get only published posts
                ->limit(10) // get only first 10 posts
                ->recentFirst() // most recent posts should go first
            ->fetchAll(); // get posts
    }

    /**
     * Find posts by category
     *
     * @param Category $category
     * @param int $page
     * @param int $itemsPerPage
     * @return Post[]
     */
    public function findByCategory(Category $category, $page = 1, $itemsPerPage = 10)
    {
        return $this
            ->filter() // use PostQueryBuilder
                ->filterByColumn('category', $category) // get only posts in specified category
                ->paginate($page, $itemsPerPage) // get only specified page
                ->recentFirst()  // most recent posts should go first
            ->fetchAll(); // get posts
    }

    /**
     * Count posts by author
     *
     * @param Author $author
     * @return int
     */
    public function countByAuthor(Author $author)
    {
        return $this
            ->filter() // use PostQueryBuilder
                ->select('COUNT(Post.id)') // calculate count
                ->filterByColumn('author', $author) // calculate only author's posts
                ->groupBy('Post.author') // group by author
            ->fetchScalar(); // get scalar result (first column of first row), limit 1 will be placed automatically
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