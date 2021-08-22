<?php
/**
 *	Adapter interface.
 *	@category		Library
 *	@package		CeusMedia_Cache
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 */
namespace CeusMedia\Cache;

/**
 *	Adapter interface.
 *	@category		Library
 *	@package		CeusMedia_Cache
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 */
interface AdapterInterface
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
	 *	Removes all data pairs from storage.
	 *	@access		public
	 *	@return		AbstractAdapter
	 */
	public function flush(): AbstractAdapter;

	/**
	 *	Returns a data pair value by its key or NULL if pair not found.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		mixed
	 */
	public function get( string $key );

	/**
	 *	Returns current context within storage.
	 *	@access		public
	 *	@return		string|NULL
	 */
	public function getContext(): ?string;

	/**
	 *	Indicates whether a data pair is stored by its key.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		boolean
	 */
	public function has( string $key );

	/**
	 *	Returns a list of all data pair keys.
	 *	@access		public
	 *	@return		array
	 */
	public function index(): array;

	/**
	 *	Removes data pair from storage by its key.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		boolean
	 */
	public function remove( string $key ): bool;

	/**
	 *	Adds or updates a data pair.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@param		string		$value		Data pair value
	 *	@param		integer		$expiration	Data life time in seconds or expiration timestamp
	 *	@return		boolean
	 */
	public function set( string $key, $value, int $expiration = NULL ): bool;

	/**
	 *	Sets context within storage.
	 *	@access		public
	 *	@param		string|NULL		$context		Context within storage
	 *	@return		AbstractAdapter
	 */
	public function setContext( ?string $context = NULL ): AbstractAdapter;

	public function setExpiration( int $expiration ): AbstractAdapter;
}
