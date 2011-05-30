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

	public function __construct( $resource ){
		$this->resource	= $resource;
		if( !file_exists( $resource ) )
			touch( $resource );
		$list	= File_Reader::loadArray( $resource );
		foreach( $list as $line ){
			$parts	= explode( '=', $line, 2 );
			$this->data[$parts[0]]	= unserialize( $parts[1] );
		}
	}

	public function flush(){
		$this->data	= array();
		@unlink( $this->resource );
	}

	public function get( $key ){
		if( isset( $this->data[$key] ) )
			return $this->data[$key];
		return NULL;
	}

	public function has( $key ){
		return isset( $this->data[$key] );
	}

	public function index(){
		return array_keys( $this->data[$key] );
	}

	public function set( $key, $value, $ttl = 0 ){
		$this->data[$key]	= $value;
		$list	= array();
		foreach( $this->data as $key => $value )
			$list[]	= $key.'='.serialize( $value );
		File_Writer::save( $this->resource, join( "\n", $list ) );
	}

	public function remove( $key ){
		unset( $this->data[$key] );
		$list	= array();
		foreach( $this->data as $key => $value )
			$list[]	= $key.'='.serialize( $value );
		File_Writer::save( $this->resource, join( "\n", $list ) );
	}
}
?>
