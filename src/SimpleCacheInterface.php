<?php
/**
 *	Adapter interface.
 *	@category		Library
 *	@package		CeusMedia_Cache
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
namespace CeusMedia\Cache;

use Psr\SimpleCache\CacheInterface as GenericSimpleCacheInterface;

/**
 *	Adapter interface.
 *	@category		Library
 *	@package		CeusMedia_Cache
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
interface SimpleCacheInterface extends GenericSimpleCacheInterface
{
	/**
	 *	Removes all data pairs from storage.
	 *	@access		public
	 *	@param		mixed|NULL		$resource		...
	 *	@param		string|NULL		$context		...
	 *	@param		integer|NULL	$expiration	Data life time in seconds or expiration timestamp
	 *	@return		void
	 */
	public function __construct( $resource, string $context = NULL, int $expiration = NULL );

	/**
	 *	Returns current context within storage.
	 *	@access		public
	 *	@return		string|NULL
	 */
	public function getContext(): ?string;

	/**
	 *	Returns a list of all data pair keys.
	 *	@access		public
	 *	@return		array
	 */
	public function index(): array;

	/**
	 *	Sets context within storage.
	 *	@access		public
	 *	@param		string|NULL		$context		Context within storage
	 *	@return		SimpleCacheInterface
	 */
	public function setContext( ?string $context = NULL ): SimpleCacheInterface;

	public function setExpiration( int $expiration ): SimpleCacheInterface;
}
