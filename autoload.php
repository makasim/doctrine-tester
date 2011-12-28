<?php

$loader = include_once __DIR__ . '/vendor/.composer/autoload.php';

$loader->add('Doctrine\\Common\\Tester',  __DIR__ . '/src/');
$loader->add('Doctrine\\Tests\\Common\\Tester', __DIR__ . '/tests/');

