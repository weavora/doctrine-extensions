<?php

namespace Weavora\Tests;

error_reporting(E_ALL | E_STRICT);

/** @var $classLoader \Composer\Autoload\ClassLoader */
$classLoader = require_once __DIR__ . '/../vendor/autoload.php';
$classLoader->add('Weavora', __DIR__);
