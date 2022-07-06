<?php
declare(strict_types=1);

/**
 *	....
 *	@category		Library
 *	@package		CeusMedia_Cache
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
namespace CeusMedia\Cache;

use Psr\SimpleCache\CacheException as CacheExceptionInterface;
use Exception;

/**
 *	....
 *	@category		Library
 *	@package		CeusMedia_Cache
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
class SimpleCacheException extends Exception implements CacheExceptionInterface
{
}
