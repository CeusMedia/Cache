<?php
/**
 *	....
 *	@category		Library
 *	@package		CeusMedia_Cache
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
namespace CeusMedia\Cache;

use Psr\SimpleCache\InvalidArgumentException as InvalidArgumentExceptionInterface;

/**
 *	....
 *	@category		Library
 *	@package		CeusMedia_Cache
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
class SimpleCacheInvalidArgumentException extends SimpleCacheException implements InvalidArgumentExceptionInterface
{
}
