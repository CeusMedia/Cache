<?php
class CMM_SEA_Adapter_JsonFile extends CMM_SEA_Adapter_Abstract implements CMM_SEA_Adapter_Interface{

	protected $file;
	protected $resource;

	public function __construct( $resource ){
		$this->resource	= $resource;
		if( !file_exists( $resource ) )
			file_put_contents( $resource, json_encode( array() ) );	
		$this->file = new File_Editor( $resource );
	}

	public function flush(){
		file_put_contents( $this->resource, json_encode( array() ) );	
	}

	public function get( $key ){
		$data	= json_decode( $this->file->readString(), TRUE );
		if( isset( $data[$key] ) )
			return unserialize( $data[$key] );
		return null;
	}

	public function has( $key ){
		$data	= json_decode( $this->file->readString(), TRUE );
		return isset( $data[$key] );
	}

	public function index(){
		$data	= json_decode( $this->file->readString(), TRUE );
		return array_keys( $data );
	}

	public function remove( $key ){
		$data	= json_decode( $this->file->readString(), TRUE );
		if( isset( $data[$key] ) )
			unset( $data[$key] );
		$this->file->writeString( json_encode( $data ) );
	}

	public function set( $key, $value, $ttl = 0 ){
		$data	= json_decode( $this->file->readString(), TRUE );
		$data[$key] = serialize( $value );
		$this->file->writeString( json_encode( $data ) );
	}
}
?>
