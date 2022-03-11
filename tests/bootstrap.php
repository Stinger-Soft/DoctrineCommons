<?php
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\ArrayCache;
use Composer\Autoload\ClassLoader;
define('TESTS_PATH', __DIR__);
define('TESTS_TEMP_DIR', __DIR__ . '/temp');
define('VENDOR_PATH', realpath(__DIR__ . '/../vendor'));

/** @var $loader ClassLoader */
$loader = require __DIR__ . '/../vendor/autoload.php';
AnnotationRegistry::registerLoader(array(
	$loader,
	'loadClass' 
));
//Gedmo\DoctrineExtensions::registerAnnotations();

$reader = new AnnotationReader();
$_ENV['annotation_reader'] = $reader;
