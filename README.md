Doctrine2 Extensions [![Build Status](https://secure.travis-ci.org/weavora/doctrine-extensions.png)](http://travis-ci.org/weavora/doctrine-extensions)
==============================

[![Total Downloads](https://poser.pugx.org/weavora/doctrine-extensions/downloads.png)](https://packagist.org/packages/weavora/doctrine-extensions)
[![Latest Stable Version](https://poser.pugx.org/weavora/doctrine-extensions/v/stable.png)](https://packagist.org/packages/weavora/doctrine-extensions)

This library extends Doctrine2 base classes with some useful things.

Installation
------------

This library may be installed using Composer or by cloning it from its GitHub repository. These options are described below.

**Composer**

You can read more about Composer and its main repository on
[http://packagist.org](http://packagist.org "Packagist"). To install these doctrine extensions using Composer, first install Composer for your project using the instructions on the Packagist home page. You can then define your development dependency on doctrine-extensions using the parameters suggested below. While every effort is made to keep the master branch stable, you may prefer to use the current stable version tag instead.

    {
        "require-dev": {
            "weavora/doctrine-extensions": "dev-master@dev"
        }
    }

To install, you may call:

    composer.phar install

**Git / GitHub**

The git repository hosts the development version in its master branch. You can
install it using Composer by referencing dev-master as your preferred version
in your project's composer.json file as the previous example shows.

You may also install this development version:

    git clone git://github.com/weavora/doctrine-extensions.git
    cd doctrine-extensions

The above processes will install library to the doctrine-extensions folder.


ORM Extensions
--------------

Doctrine advices to use entity repositories that will contain business logic related to entities retrieval.
Most probably, you will use a query builder inside the repository to build DQL.
The issue is that a standard query builder has quite general API and doesn't provide such useful shortcuts like named scopes, a single method to apply custom criteria with parameters, etc.

This library is intended to fix what's missing and make your life a little bit easier.

### How to organize repositories

Let's say you want to create a blog application. You'll probably create a post entity which will have references to the category, author and comments. And now you're thinking about an approach to organize your repositories.

Common issues with repositories:

 - You have to duplicate code as soon as you have duplicated conditions
 - Even simple methods with few conditions and ordering look massive

You can solve the first issue with a custom query builder per entity. It will also hide your criteria details from the repository that doesn't really need to know that.

To solve the issue with huge and not very descriptive methods, you'll create an extension for the query builder with some helpful shortcuts to make code more readable.

An example of how PostQueryBuilder & PostRepository could look like:

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
                ->filterByColumn('Post.category', $category) // get only posts in the specified category
                ->paginate($page, $itemsPerPage) // get only the specified page
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
            ->fetchScalar(); // get scalar results (the first column of the first row)
    }
}

```

Quite simple, right?

### API / EntityQueryBuilder


**getEntityAlias() : string** | *Get entity alias in the query*

```php
// entity class name -> alias
// \Entity\Post -> Post
// \Acme\DemoBundle\Entity\AuthorSettings -> AuthorSettings
// \Comment -> Comment
$alias = $queryBuilder->getEntityAlias();
```

**filterByColumn($columnName, $value, $strict = true) : EntityQueryBuilder** | *Compare a column with the specified value*

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

**filterByStatement($statement, $parameters = array()) : EntityQueryBuilder** | *Add a custom statement*

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

**fetchAll($parameters = array()) : EntityClass[]** | *Fetch a result*

```php
// SELECT * FROM Entity\Post Post -> Post[]
$queryBuilder->fetchAll();

// SELECT * FROM Entity\Post Post LIMIT 0, 10 -> Post[]
$queryBuilder->filterByStatement('Post.title = :title')->fetchAll(['title' => 'Post 1']);
```

**fetchOne($parameters = array()) : EntityClass** | *Fetch the first result*

```php
// SELECT * FROM Entity\Post Post LIMIT 0, 1 -> Post
$queryBuilder->fetchOne();
```

**fetchScalar($parameters = array()) : int|string|float|null** | *Fetch a scalar result*

```php
// SELECT COUNT(*) FROM Entity\Post Post LIMIT 0, 1 -> int
$queryBuilder->select('COUNT(*)')->fetchScalar();
```

DBAL Extensions
---------------

There is only one small enhancement to DBAL classes - `Connection::lockSafeUpdate` that allows you to restart a query in case a transaction has been locked and failed. Maybe, it will be useful to show how to extend Doctrine connection class with custom methods.

**How to configure**

```yml
# config.yml
doctrine:
    dbal:
        wrapper_class: 'Weavora\Doctrine\DBAL\Connection'
```

**Usage example**

```
// Method will retry a query if it failed because of lock the first time
// You can specify the retry number as the 3rd argument
$doctrine->getConnection()->locksSafeUpdate("UPDATE posts SET category_id = :category", ['category' => 2]);
```

About
=====

Stability
---------

**It's not stable yet**. Please, use it at your own risk.

Requirements
------------

- Any flavor of PHP 5.3 or later should do
- [optional] PHPUnit 3.5+ to execute the test suite (phpunit --version)

Submitting bugs and feature requests
------------------------------------

Bugs and feature request are tracked on [GitHub](https://github.com/weavora/doctrine-extensions/issues)

Author
------

Weavora LLC - <http://weavora.com> - <http://twitter.com/weavora><br />
Also see the list of [contributors](https://github.com/weavora/doctrine-extensions/contributors) who have participated in this project.

License
-------

This library is licensed under the MIT License - see the `LICENSE` file for details
