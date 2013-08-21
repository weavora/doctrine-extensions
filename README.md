Doctrine2 Extensions [![Build Status](https://secure.travis-ci.org/weavora/doctrine-extensions.png)](http://travis-ci.org/weavora/doctrine-extensions)
==============================

[![Total Downloads](https://poser.pugx.org/weavora/doctrine-extensions/downloads.png)](https://packagist.org/packages/weavora/doctrine-extensions)
[![Latest Stable Version](https://poser.pugx.org/weavora/doctrine-extensions/v/stable.png)](https://packagist.org/packages/weavora/doctrine-extensions)

This library extend base Doctrine2 classes with some useful things.

ORM Extensions
--------------

Doctrine advice to use entity repositories that will contain business logic related to entities retrieve.
Most probably you will use query builder inside repository to build DQL.
The issue is that standard query build has quite overall API and doesn't provide such useful shortcuts like named scopes,
single method to apply custom criteria with parameters and etc.

This library tries to fix missing things and make your life a little bit easier.

### How to organize repositories

Let's say you want to create blog application. Probably you create post entity which have references to category,
author and comments. And now you're thinking about approach to organize your repositories.

Common issues with repositories:

 - You have to duplicate code as soon as you have duplicated conditions
 - Even simple methods with few conditions and ordering look massive

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
        return $this->filterByColumn('Post.publishStatus', Post::STATUS_PUBLISHED);
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
                ->filterByColumn('Post.category', $category) // get only posts in specified category
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
                ->filterByColumn('Post.author', $author) // calculate only author's posts
                ->groupBy('Post.author') // group by author
            ->fetchScalar(); // get scalar result (first column of first row)
    }
}

```

Quite simple, yeah?

### API / EntityQueryBuilder


**getEntityAlias() : string** | *Get entity alias in the query*

```php
// entity class name -> alias
// \Entity\Post -> Post
// \Acme\DemoBundle\Entity\AuthorSettings -> AuthorSettings
// \Comment -> Comment
$alias = $queryBuilder->getEntityAlias();
```

**filterByColumn($columnName, $value, $strict = true) : EntityQueryBuilder** | *Compare column with specified value*

```php
// SELECT * FROM Entity\Post Post WHERE Post.title = :p1, [p1 = 'Post 1']
$queryBuilder->filterByColumn('Post.title', 'Post 1');

// SELECT * FROM Entity\Post Post WHERE Post.category IN (1,2,3)
$queryBuilder->filterByColumn('Post.category', array(1,2,3));

// SELECT * FROM Entity\Post Post WHERE Post.author IS NULL
$queryBuilder->filterByColumn('Post.author', null);

// SELECT * FROM Entity\Post Post
$queryBuilder->filterByColumn('Post.author', null, false);

// SELECT * FROM Entity\Post Post WHERE Post.author = 1
$queryBuilder->filterByColumn('Post.author', 1, false);
```

**filterByStatement($statement, $parameters = array()) : EntityQueryBuilder** | *Add custom statement*

```php
// SELECT * FROM Entity\Post Post WHERE Post.title = :title, [title = 'Post 1']
$queryBuilder->filterByStatement('Post.title = :title', ['title' => 'Post 1']);

// SELECT * FROM Entity\Post Post WHERE Post.category IN (1,2,3)
$queryBuilder->filterByColumn('Post.category IN (1,2,3)');
```

**limit($maxResults, $offset = null) : EntityQueryBuilder** | *Limit results*

```php
// SELECT * FROM Entity\Post Post LIMIT 0, 10
$queryBuilder->limit(10);

// SELECT * FROM Entity\Post Post LIMIT 15, 10
$queryBuilder->limit(10, 15);
```

**fetchAll($parameters = array()) : EntityClass[]** | *Fetch result*

```php
// SELECT * FROM Entity\Post Post -> Post[]
$queryBuilder->fetchAll();

// SELECT * FROM Entity\Post Post LIMIT 0, 10 -> Post[]
$queryBuilder->filterByStatement('Post.title = :title')->fetchAll(['title' => 'Post 1']);
```

**fetchOne($parameters = array()) : EntityClass** | *Fetch first result*

```php
// SELECT * FROM Entity\Post Post LIMIT 0, 1 -> Post
$queryBuilder->fetchOne();
```

**fetchScalar($parameters = array()) : int|string|float|null** | *Fetch scalar result*

```php
// SELECT COUNT(*) FROM Entity\Post Post LIMIT 0, 1 -> int
$queryBuilder->select('COUNT(*)')->fetchScalar();
```

DBAL Extensions
---------------

There is only one small enhancements to DBAL classes is `Connection::lockSafeUpdate` that allow you to restart query in case of
transaction was locked and failed. But maybe it will be useful as example how to extend Doctrine connection class with custom
methods.

**How to configure**

```yml
# config.yml
doctrine:
    dbal:
        wrapper_class: 'Weavora\Doctrine\DBAL\Connection'
```

**Usage example**

```
// Method will retry query if it failed cause of lock first time
// You can specify retry number as 3rd argument
$doctrine->getConnection()->locksSafeUpdate("UPDATE posts SET category_id = :category", ['category' => 2]);
```

About
=====

Stability
---------

**It's not stable yet**. Please, use it in your own risk.

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