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
class CMM_SEA_Adapter_IniFile extends CMM_SEA_Adapter_Abstract implements CMM_SEA_Adapter_Interface{

	protected $data;
	protected $resource;

	public function __construct( $resource = NULL, $context = NULL, $expiration = NULL ){
		$this->resource	= $resource;
		if( !file_exists( $resource ) )
			touch( $resource );
		$list	= trim( File_Reader::load( $resource ) );
		if( $list )
			foreach( explode( "\n", $list ) as $line ){
				$parts	= explode( '=', $line, 2 );
				$this->data[$parts[0]]	= unserialize( $parts[1] );
			}
	}

	/**
	 *	Removes all data pairs from storage.
	 *	@access		public
	 *	@return		void
	 */
	public function flush(){
		$this->data	= array();
		@unlink( $this->resource );
	}

	/**
	 *	Returns a data pair value by its key or NULL if pair not found.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		mixed
	 */
	public function get( $key ){
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
	public function has( $key ){
		return isset( $this->data[$key] );
	}

	/**
	 *	Returns a list of all data pair keys.
	 *	@access		public
	 *	@return		array
	 */
	public function index(){
		return array_keys( $this->data );
	}

	/**
	 *	Removes data pair from storage by its key.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		boolean
	 */
	public function remove( $key ){
		if( !$this->has( $key ) )
			return FALSE;
		unset( $this->data[$key] );
		$list	= array();
		foreach( $this->data as $key => $value )
			$list[]	= $key.'='.serialize( $value );
		File_Writer::save( $this->resource, join( "\n", $list ) );
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
		$this->data[$key]	= $value;
		$list	= array();
		foreach( $this->data as $key => $value )
			$list[]	= $key.'='.serialize( $value );
		File_Writer::save( $this->resource, join( "\n", $list ) );
	}
}
?>
