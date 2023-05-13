<?php
( @include '../vendor/autoload.php' ) or die( 'Please use composer to install required packages.' . PHP_EOL );

use CeusMedia\Cache\SimpleCacheFactory as CacheFactory;
//use CeusMedia\Cache\AdapterInterface as CacheAdapterInterface;
use Psr\SimpleCache\CacheInterface as SimpleCacheInterface;

ini_set( 'display_errors', 'On' );
$EOL		= ( 'cli' === php_sapi_name() ) ? PHP_EOL : '<br/>';

$engine		= 'JsonFile';
$resource	= 'caches/cache.json';
$context	= NULL;

$engine		= 'Noop';
$resource	= 'caches/cache.json';
$context	= NULL;

$engine		= 'Session';
$resource	= '';
$context	= NULL;

$engine		= 'Memcache';
$resource	= 'localhost:11211';
$context	= NULL;

$engine		= 'Folder';
$resource	= 'caches/folderPlain';
$context	= NULL;

$engine		= 'SerialFolder';
$resource	= 'caches/folderSerial';
$context	= NULL;

//$factory	= new CacheFactory();
//$cache		= $factory->newStorage( $engine, $resource, $context, 10 );

//use Psr\SimpleCache\CacheInterface as SimpleCacheInterface;

$cache		= CacheFactory::createStorage( $engine, $resource, $context, 10 );

//function takeA( CacheAdapterInterface $storage ){}
function takeB( SimpleCacheInterface $storage ){}

//takeA( $cache );
takeB( $cache );

//$cache->flush();


print "Current timestamp: " . time() . $EOL;

print "Index:" . $EOL;
foreach( $cache->index() as $key )
	print '- ' . $key . ': ' . $cache->get( $key ) . $EOL;
print $EOL;

if( $cache->has( 'lastTest' ) ){
	print "Reading 'lastTest' from cache..." . $EOL;
	print "lastTest: ".$cache->get( 'lastTest' ) . $EOL;
}
else{
	print "Writing 'lastTest' to cache..." . $EOL;
	$cache->set( 'lastTest', time() );
}
