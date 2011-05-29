<?php
class CMM_SEA_Adapter_VolatileMemory extends CMM_SEA_Adapter_Abstract implements CMM_SEA_Adapter_Interface{

	protected $data	= array();

	public function __construct( $resource ){
	}

	public function flush(){
		$this->data	= array();
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

	public function remove( $key ){
		unset( $this->data[$key] );
	}

	public function set( $key, $value, $ttl = 0 ){
		$this->data[$key]	= $value;
	}
}
?>
