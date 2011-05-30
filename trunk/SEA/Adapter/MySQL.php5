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
class CMM_SEA_Adapter_MySQL implements CMM_SEA_Adapter_Interface{

	protected $tableName	= 'cache';

	public function __construct( $resource ){
		$this->resource	= $resource;
	}

	public function flush(){
		mysql_query( 'TRUNCATE '.$this->tableName, $this->resource );
	}

	public function get( $key ){
		$query	= 'SELECT value FROM '.$this->tableName.' WHERE name="'.$key.'"';
		$result	= mysql_query( $query, $this->resource );
		if( !mysql_num_rows( $result ) )
			return NULL;
		return mysql_fetch_row( $result );
	}

	public function has( $key ){
		$query	= 'SELECT COUNT(value) as count FROM '.$this->tableName.' WHERE name="'.$key.'"';
		$result	= mysql_query( $query, $this->resource );
		$row	= mysql_fetch_row( $result );
		print_m( $row );
		return $row['count'];
	}

	public function index(){
		$list	= array();
		$query	= 'SELECT name FROM '.$this->tableName;
		$result	= mysql_query( $query, $this->resource );
		while( $row = mysql_fetch_array( $result ) )
			$list[]	= $row[0];
		return $list;
	}

	public function remove( $key ){
		$query	= 'DELETE FROM '.$this->tableName.' WHERE name="'.$key.'"';
		mysql_query( $query, $this->resource );
	}

	public function set( $key, $value, $ttl = 0 ){
		$value	= serialize( $value );
		if( $value === NULL || $value === '' )
			return $this->remove( $key );
		$query	= 'INSERT INTO '.$this->tableName.' (name, value) VALUES ("'.$key.'", "'.$value.'") ON DUPLICATE KEY UPDATE value="'.$value.'"';
		mysql_query( $query, $this->resource );
	}

	public function setTableName( $tableName ){
		$this->tableName	= $tableName;
	}
}
?>
