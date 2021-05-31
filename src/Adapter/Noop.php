<?php
/**
 *	Fake storage engine with no operations at all.
 *	@category		Library
 *	@package		CeusMedia_Cache_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			02.06.2011
 */
namespace CeusMedia\Cache\Adapter;

use CeusMedia\Cache\AbstractAdapter;
use CeusMedia\Cache\AdapterInterface;

/**
 *	Fake storage engine with no operations at all.
 *	@category		Library
 *	@package		CeusMedia_Cache_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			02.06.2011
 */
class Noop extends AbstractAdapter implements AdapterInterface
{
	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		string		$resource		Memcache server hostname and port, eg. 'localhost:11211' (default)
	 *	@param		string		$context		Internal prefix for keys for separation
	 *	@param		integer		$expiration		Data life time in seconds or expiration timestamp
	 *	@return		void
	 */
	public function __construct( $resource, string $context = NULL, int $expiration = NULL )
	{
		if( $context !== NULL )
			$this->setContext( $context );
		if( $expiration !== NULL )
			$this->setExpiration( $expiration );
	}

	/**
	 *	Does nothing since there is no stored data.
	 *	@access		public
	 *	@return		self
	 */
	public function flush(): self
	{
		return $this;
	}

	/**
	 *	Returns NULL always since there is no stored data.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		mixed
	 */
	public function get( string $key )
	{
		return NULL;
	}

	/**
	 *	Returns FALSE always since there is no stored data.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		boolean
	 */
	public function has( string $key ): bool
	{
		return FALSE;
	}

	/**
	 *	Returns empty list since there is no stored data.
	 *	@access		public
	 *	@return		array
	 */
	public function index(): array
	{
		return array();
	}

	/**
	 *	Does nothing since there is no stored data.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		boolean
	 */
	public function remove( string $key ): bool
	{
		return TRUE;
	}

	/**
	 *	Does nothing since there is no stored data.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@param		mixed		$value		Data pair value
	 *	@param		integer		$expiration	Data life time in seconds or expiration timestamp
	 *	@return		boolean
	 */
	public function set( string $key, $value, int $expiration = NULL ): bool
	{
		return TRUE;
	}
}
