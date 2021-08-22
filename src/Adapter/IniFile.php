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
use FS_File_Reader as FileReader;
use FS_File_Writer as FileWriter;

/**
 *	....
 *	@category		Library
 *	@package		CeusMedia_Cache_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 */
class IniFile extends AbstractAdapter implements AdapterInterface
{
	/**	@var	array		$data */
	protected $data			= [];

	/**	@var	string		$resource */
	protected $resource;

	public function __construct( $resource, ?string $context = NULL, ?int $expiration = NULL )
	{
		$this->resource	= $resource;
		if( !file_exists( $resource ) )
			touch( $resource );
		$list	= trim( FileReader::load( $resource ) );
		if( $list ){
			foreach( explode( "\n", $list ) as $line ){
				$parts	= explode( '=', $line, 2 );
				$this->data[$parts[0]]	= unserialize( $parts[1] );
			}
		}
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
		@unlink( $this->resource );
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
		if( isset( $this->data[$key] ) )
			return $this->data[$key];
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
		return isset( $this->data[$key] );
	}

	/**
	 *	Returns a list of all data pair keys.
	 *	@access		public
	 *	@return		array
	 */
	public function index(): array
	{
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
		unset( $this->data[$key] );
		$list	= array();
		foreach( $this->data as $key => $value )
			$list[]	= $key.'='.serialize( $value );
		FileWriter::save( $this->resource, join( "\n", $list ) );
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
		$this->data[$key]	= $value;
		$list	= array();
		foreach( $this->data as $key => $value )
			$list[]	= $key.'='.serialize( $value );
		return (bool) FileWriter::save( $this->resource, join( "\n", $list ) );
	}
}
