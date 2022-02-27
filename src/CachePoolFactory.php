<?php
/**
 *	....
 *	@category		Library
 *	@package		CeusMedia_Cache
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
namespace CeusMedia\Cache;

/**
 *	....
 *	@category		Library
 *	@package		CeusMedia_Cache
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
class CachePoolFactory
{
	/**
	 *	...
	 *
	 *	@access		public
	 *	@static
	 *	@param		string		$adapterType		...
	 *	@param		mixed		$adapterResource	...
	 *	@return		CachePool
	 */
	public static function createPool( string $adapterType, $adapterResource ): CachePool
	{
		$adapter	= SimpleCacheFactory::createStorage( $adapterType, $adapterResource );
		return new CachePool( $adapter );
	}
}
