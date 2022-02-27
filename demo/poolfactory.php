<?php
( @include '../vendor/autoload.php' ) or die( 'Please use composer to install required packages.' . PHP_EOL );

use CeusMedia\Cache\CachePoolFactory;

$pool	= CachePoolFactory::createPool( 'IniFile', __DIR__.'/pool.ini' );
$item	= $pool->getItem( 'datetime' );

if( !$item->isHit() ){
	$item->set( date( DATE_ATOM ) );
	$pool->save( $item );
} else {
	print 'Value is in cache'.PHP_EOL;
}
//print_r( $item );

print 'Value currently: '.$item->get().PHP_EOL;
