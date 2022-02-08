<?php
( @include __DIR__.'/../vendor/autoload.php' ) or die( 'Please use composer to install required packages.' . PHP_EOL );

use CeusMedia\Cache\Adapter\IniFile as IniFileAdapter;
use CeusMedia\Cache\CachePool;

$adapter	= new IniFileAdapter( __DIR__.'/pool.ini' );

//  ----------------------------------------------------------------------------

$pool		= new CachePool( $adapter );
$item		= $pool->getItem( 'datetime' );

if( !$item->isHit() ){
	$item->set( date( DATE_ATOM ) );
	$pool->save( $item );
} else {
	print 'Value is in cache'.PHP_EOL;
}
//print_r( $item );

print 'Value currently: '.$item->get().PHP_EOL;
