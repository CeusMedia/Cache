<?php
interface CMM_SEA_Adapter_Interface{

	public function flush();
	
	public function get( $key );
	
	public function has( $key );
	
	public function index();
	
	public function remove( $key );
	
	public function set( $key, $value, $ttl = 0 );
}
?>
