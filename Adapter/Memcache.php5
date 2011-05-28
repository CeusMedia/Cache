<?php
class CMM_SEA_Adapter_Memcache extends CMM_SEA_Adapter_Abstract implements CMM_SEA_Adapter_Interface{

	protected $resource;

	public function __construct( $resource ){
		$this->resource = $resource;
	}

	public function flush(){
		$this->resource->flush();
	}

	public function get( $key ){
		$data	= $this->resource->get( $key );
		if( $data )
			return unserialize( $data );
		return NULL;
	}

	public function has( $key ){
		return $this->get( $key ) !== NULL;
	}

	public function index(){
		throw Exception( 'Not supported by memcache' );
	}

	public function remove( $key ){
		$this->resource->delete( $key );
	}

	public function set( $key, $value, $ttl = 0 ){
		$this->resource->set( $key, serialize( $value ), 0, $ttl );
	}
}
?>
