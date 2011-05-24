<?php
class CMM_SEA_Adapter_SerialFile extends CMM_SEA_Adapter_Abstract implements CMM_SEA_Adapter_Interface{

	public function __construct( $resource ){
		if( !file_exists( $resource ) )
			file_put_contents( $resource, serialize( array() ) );	
		$this->file = new File_Editor( $resource );
	}

	public function get( $key ){
		$data	= unserialize( $this->file->readString() );
		if( isset( $data[$key] ) )
			return unserialize( $data[$key] );
		return null;
	}

	public function has( $key ){
		$data	= unserialize( $this->file->readString() );
		return isset( $data[$key] );
	}

	public function index(){
		$data	= unserialize( $this->file->readString() );
		return array_keys( $data );
	}

	public function remove( $key ){
		$data	= unserialize( $this->file->readString() );
		if( isset( $data[$key] ) )
			unset( $data[$key] );
		$this->file->writeString( serialize( $data ) );
	}

	public function set( $key, $value, $ttl = 0 ){
		$data	= unserialize( $this->file->readString() );
		$data[$key] = serialize( $value );
		$this->file->writeString( serialize( $data ) );
	}
}
?>
