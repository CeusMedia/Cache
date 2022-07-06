<?php
declare(strict_types=1);

( @include __DIR__.'/../vendor/autoload.php' ) or die( 'Please use composer to install required packages.' . PHP_EOL );

use CeusMedia\Cache\Adapter\IniFile as IniFileAdapter;
use CeusMedia\Cache\Adapter\JsonFile as JsonFileAdapter;
use CeusMedia\Cache\Adapter\Redis as RedisAdapter;
use CeusMedia\Cache\CachePool;

//  ----------------------------------------------------------------------------

$adapter	= new IniFileAdapter( __DIR__.'/caches/pool.ini' );
$adapter	= new JsonFileAdapter( __DIR__.'/caches/pool.json', NULL, 160 );
$adapter	= new RedisAdapter( NULL, NULL, 10 );

//  ----------------------------------------------------------------------------

$pool		= new CachePool( $adapter );
$item		= $pool->getItem( 'datetime' );

if( !$item->isHit() ){
	$item->set( date( DATE_ATOM ) );
	$pool->save( $item );
} else {
	print 'Value is in cache'.PHP_EOL;
}

print 'Value currently: '.$item->get().PHP_EOL;
