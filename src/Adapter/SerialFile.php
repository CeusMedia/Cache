<?php
/**
 *	....
 *	@category		Library
 *	@package		CeusMedia_Cache_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 */
namespace CeusMedia\Cache\Adapter;

use CeusMedia\Cache\AbstractAdapter;
use CeusMedia\Cache\AdapterInterface;
use FS_File_Editor as FileEditor;

/**
 *	....
 *	@category		Library
 *	@package		CeusMedia_Cache_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 */
class SerialFile extends AbstractAdapter implements AdapterInterface
{
	/**	@var	FileEditor		$resource */
	protected $resource;

	public function __construct( $resource, ?string $context = NULL, ?int $expiration = NULL )
	{
		if( !file_exists( $resource ) )
			file_put_contents( $resource, serialize( array() ) );
		$this->resource = new FileEditor( $resource );
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
		$this->resource->remove();
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
		$data	= unserialize( $this->resource->readString() );
		if( isset( $data[$key] ) )
			return unserialize( $data[$key] );
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
		$data	= unserialize( $this->resource->readString() );
		return isset( $data[$key] );
	}

	/**
	 *	Returns a list of all data pair keys.
	 *	@access		public
	 *	@return		array
	 */
	public function index(): array
	{
		$data	= unserialize( $this->resource->readString() );
		return array_keys( $data );
	}

	/**
	 *	Removes data pair from storage by its key.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		boolean
	 */
	public function remove( string $key ): bool
	{
		$data	= unserialize( $this->resource->readString() );
		if( !isset( $data[$key] ) )
			return FALSE;
		unset( $data[$key] );
		$this->resource->writeString( serialize( $data ) );
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
		$data	= unserialize( $this->resource->readString() );
		$data[$key] = serialize( $value );
		return (bool) $this->resource->writeString( serialize( $data ) );
	}
}
