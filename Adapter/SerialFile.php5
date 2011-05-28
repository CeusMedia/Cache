<?php
class CMM_SEA_Adapter_SerialFile extends CMM_SEA_Adapter_Abstract implements CMM_SEA_Adapter_Interface{

	protected $resource;

	public function __construct( $resource ){
		$this->resource	= $resource;
		if( !file_exists( $resource ) )
			file_put_contents( $resource, serialize( array() ) );	
		$this->resource = new File_Editor( $resource );
	}

	public function flush(){
		$this->resource->remove();
	}

	public function get( $key ){
		$data	= unserialize( $this->resource->readString() );
		if( isset( $data[$key] ) )
			return unserialize( $data[$key] );
		return null;
	}

	public function has( $key ){
		$data	= unserialize( $this->resource->readString() );
		return isset( $data[$key] );
	}

	public function index(){
		$data	= unserialize( $this->resource->readString() );
		return array_keys( $data );
	}

	public function remove( $key ){
		$data	= unserialize( $this->resource->readString() );
		if( isset( $data[$key] ) )
			unset( $data[$key] );
		$this->resource->writeString( serialize( $data ) );
	}

	public function set( $key, $value, $ttl = 0 ){
		$data	= unserialize( $this->resource->readString() );
		$data[$key] = serialize( $value );
		$this->resource->writeString( serialize( $data ) );
	}
}
?>
