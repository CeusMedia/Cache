<?php
( @include __DIR__.'/../vendor/autoload.php' ) or die( 'Please use composer to install required packages.' . PHP_EOL );

use CeusMedia\Cache\Adapter\IniFile as IniFileAdapter;
use CeusMedia\Cache\SimpleCacheFactory;

//  ----------------------------------------------------------------------------

$adapterType		= 'IniFile';
$adapterResource	= __DIR__.'/caches/simple.ini';
$cache				= SimpleCacheFactory::createStorage( $adapterType, $adapterResource );

$adapterType		= 'Redis';
$adapterResource	= NULL;
$cache				= SimpleCacheFactory::createStorage( $adapterType, $adapterResource );

$adapterType		= 'JsonFile';
$adapterResource	= __DIR__.'/caches/simple.json';
$cache				= SimpleCacheFactory::createStorage( $adapterType, $adapterResource, NULL, 10 );

$adapterType		= 'SerialFile';
$adapterResource	= __DIR__.'/caches/simple.serial';
$cache				= SimpleCacheFactory::createStorage( $adapterType, $adapterResource );

$adapterType		= 'Memcache';
$adapterResource	= NULL;
$cache				= SimpleCacheFactory::createStorage( $adapterType, $adapterResource );

//  ----------------------------------------------------------------------------


if( !$cache->has( 'datetime' ) ){
	$cache->set( 'datetime', date( DATE_ATOM ) );
	print 'Setting value to: '.$cache->get( 'datetime' ).PHP_EOL;
} else {
	print 'Value is in cache'.PHP_EOL;
	print 'Value currently: '.$cache->get( 'datetime' ).PHP_EOL;
	$cache->remove( 'datetime' );
}

print_r( $cache->index() );
