<?php
/**
 *	Adapter interface.
 *	@category		Library
 *	@package		CeusMedia_Cache
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 */
namespace CeusMedia\Cache;

use Psr\SimpleCache\CacheInterface as SimpleCacheInterface;

/**
 *	Adapter interface.
 *	@category		Library
 *	@package		CeusMedia_Cache
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 */
interface AdapterInterface extends SimpleCacheInterface
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
	 *	@return		AdapterInterface
	 */
	public function setContext( ?string $context = NULL ): AdapterInterface;

	public function setExpiration( int $expiration ): AdapterInterface;
}
