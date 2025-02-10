<?php
( @include __DIR__.'/../vendor/autoload.php' ) or die( 'Please use composer to install required packages.' . PHP_EOL );
ini_set( 'display_errors', 'On' );
error_reporting( E_ALL );

use CeusMedia\Cache\Adapter\IniFile as IniFileAdapter;
use CeusMedia\Cache\SimpleCacheFactory;

//  ----------------------------------------------------------------------------

$adapterType		= 'Redis';
$adapterType		= 'JsonFile';
$adapterType		= 'SerialFile';
$adapterType		= 'IniFile';
$adapterType		= 'Memcache';

$adapterResource	= NULL;
$context			= NULL;
$expiration			= 10;

switch( $adapterType ){
	case 'Redis':
		break;
	case 'JsonFile':
		$adapterResource	= __DIR__.'/caches/simple.json';
		break;
	case 'SerialFile':
		$adapterResource	= __DIR__.'/caches/simple.serial';
		break;
	case 'Memcache':
		break;
	case 'IniFile':
	default:
		$adapterResource	= __DIR__.'/caches/simple.ini';
		break;
}

$cache		= SimpleCacheFactory::createStorage( $adapterType, $adapterResource, $context, $expiration );


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
