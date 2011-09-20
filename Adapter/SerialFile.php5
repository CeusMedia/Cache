<?php
/**
 *	....
 *	@category		cmModules
 *	@package		SEA
 *	@extends		CMM_SEA_Adapter_Abstract
 *	@implements		CMM_SEA_Adapter_Interface
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 *	@version		$Id$
 */
/**
 *	....
 *	@category		cmModules
 *	@package		SEA
 *	@extends		CMM_SEA_Adapter_Abstract
 *	@implements		CMM_SEA_Adapter_Interface
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 *	@version		$Id$
 */
class CMM_SEA_Adapter_SerialFile extends CMM_SEA_Adapter_Abstract implements CMM_SEA_Adapter_Interface{

	protected $resource;

	public function __construct( $resource ){
		$this->resource	= $resource;
		if( !file_exists( $resource ) )
			file_put_contents( $resource, serialize( array() ) );	
		$this->resource = new File_Editor( $resource );
	}

	/**
	 *	Removes all data pairs from storage.
	 *	@access		public
	 *	@return		void
	 */
	public function flush(){
		$this->resource->remove();
	}

	/**
	 *	Returns a data pair value by its key or NULL if pair not found.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		mixed
	 */
	public function get( $key ){
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
	public function has( $key ){
		$data	= unserialize( $this->resource->readString() );
		return isset( $data[$key] );
	}

	/**
	 *	Returns a list of all data pair keys.
	 *	@access		public
	 *	@return		array
	 */
	public function index(){
		$data	= unserialize( $this->resource->readString() );
		return array_keys( $data );
	}

	/**
	 *	Removes data pair from storage by its key.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		boolean
	 */
	public function remove( $key ){
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
	 *	@param		string		$value		Data pair value
	 *	@param		integer		$expiration	Data life time in seconds or expiration timestamp
	 *	@return		void
	 */
	public function set( $key, $value, $expiration = NULL ){
		$data	= unserialize( $this->resource->readString() );
		$data[$key] = serialize( $value );
		$this->resource->writeString( serialize( $data ) );
	}
}
?>
