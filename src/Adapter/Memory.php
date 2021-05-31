<?php
/**
 *	Volatile Memory Storage.
 *	Supports context.
 *	@category		Library
 *	@package		CeusMedia_Cache_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 */
namespace CeusMedia\Cache\Adapter;

use CeusMedia\Cache\AbstractAdapter;
use CeusMedia\Cache\AdapterInterface;

/**
 *	Volatile Memory Storage.
 *	Supports context.
 *	@category		Library
 *	@package		CeusMedia_Cache_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 */
class Memory extends AbstractAdapter implements AdapterInterface
{
	protected $data	= array();

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		mixed		$resource		No relevant for this adapter
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
	 *	Removes all data pairs from storage.
	 *	@access		public
	 *	@return		self
	 */
	public function flush(): self
	{
		$this->data	= array();
		return $this;
	}

	/**
	 *	Returns a data pair value by its key or NULL if pair not found.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		mixed
	 */
	public function get( string $key )
	{
		if( isset( $this->data[$this->context.$key] ) )
			return $this->data[$this->context.$key];
		return NULL;
	}

	/**
	 *	Indicates whether a data pair is stored by its key.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		boolean
	 */
	public function has( string $key ): bool
	{
		return isset( $this->data[$this->context.$key] );
	}

	/**
	 *	Returns a list of all data pair keys.
	 *	@access		public
	 *	@return		array
	 */
	public function index(): array
	{
		if( $this->context ){
			$list	= array();
			$length	= strlen( $this->context );
			foreach( $this->data as $key => $value )
				if( substr( $key, 0, $length ) == $this->context )
					$list[]	= substr( $key, $length );
			return $list;
		}
		return array_keys( $this->data );
	}

	/**
	 *	Removes data pair from storage by its key.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		boolean
	 */
	public function remove( string $key ): bool
	{
		if( !$this->has( $key ) )
			return FALSE;
		unset( $this->data[$this->context.$key] );
		return TRUE;
	}

	/**
	 *	Adds or updates a data pair.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@param		mixed		$value		Data pair value
	 *	@param		integer		$expiration	Data life time in seconds or expiration timestamp
	 *	@return		boolean
	 */
	public function set( string $key, $value, int $expiration = NULL ): bool
	{
		$this->data[$this->context.$key]	= $value;
		return TRUE;
	}
}
