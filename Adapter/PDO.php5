<?php
class CMM_SEA_Adapter_PDO implements CMM_SEA_Adapter_Interface{

	protected $tableName	= 'cache';

	public function __construct( $resource ){
		$this->resource	= $resource;
	}

	public function get( $key ){
		$query	= 'SELECT value FROM '.$this->tableName.' WHERE key="'.$key.'"';
		$result	= $this->resource->query( $query );
		return $result->fetch( PDO::FETCH_OBJECT )->value;
	}

	public function has( $key ){
		$query	= 'SELECT COUNT(value) as count FROM '.$this->tableName.' WHERE key="'.$key.'"';
		$result	= $this->resource->query( $query );
		if( $result )
			return (bool) $result->fetch( PDO::FETCH_OBJECT )->count;
		return FALSE;
	}

	public function index(){
		$list	= array();
		$query	= 'SELECT key FROM '.$this->tableName;
		$result	= $this->resource->query( $query );
		if( $result )
			foreach( $result->fetch( PDO::FETCH_OBJECT ) as $key )
				$list[]	= $key;
		return $list;
	}

	public function remove( $key ){
	}

	public function set( $key, $value, $ttl = 0 ){
		if( $this->has( $key ) )
			$query	= 'UPDATE '.$this->tableName.' SET key="'.serialize( $value ).'"';
		else
			$query	= 'INSERT INTO '.$this->tableName.' (key, value) SET ("'.$key.'", "'.serialize( $value ).'")';
		$this->resource->query( $query );		
			 
	}

	public function setTableName( $tableName ){
		$this->tableName	= $tableName;
	}
}
?>
