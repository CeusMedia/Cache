<?php
class CMM_SEA_Adapter_IniFile extends CMM_SEA_Adapter_Abstract implements CMM_SEA_Adapter_Interface{

	protected $file;
	protected $resource;

	public function __construct( $resource ){
		$this->resource	= $resource;
		if( !file_exists( $resource ) )
			file_put_contents( $resource, '' );	
		$this->file = new File_INI_Editor( $resource, false );
	}

	public function flush(){
		file_put_contents( $this->resource, serialize( array() ) );	
	}

	public function get( $key ){
		if( $this->file->hasProperty( $key ) )
			return unserialize( $this->file->getProperty( $key ) );
		return null;
	}

	public function has( $key ){
		return $this->file->hasProperty( $key );
	}

	public function index(){
		return $this->file->getPropertyList();
	}

	public function set( $key, $value, $ttl = 0 ){
		$this->file->setProperty( $key, serialize( $value ) );
	}

	public function remove( $key ){
		if( $this->file->hasProperty( $key ) )
			$this->file->deleteProperty( $key );
	}
}
?>
