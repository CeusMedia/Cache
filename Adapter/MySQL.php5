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
 *	@todo			implement expiration
 *	@todo			implement context
 *	@todo			change setting of resource and table name, see PDO adapter
 */
class CMM_SEA_Adapter_MySQL implements CMM_SEA_Adapter_Interface{

	protected $tableName	= 'cache';

	public function __construct( $resource ){
		$this->resource	= $resource;
	}

	/**
	 *	Removes all data pairs from storage.
	 *	@access		public
	 *	@return		void
	 */
	public function flush(){
		mysql_query( 'TRUNCATE '.$this->tableName, $this->resource );
	}

	/**
	 *	Returns a data pair value by its key or NULL if pair not found.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		mixed
	 */
	public function get( $key ){
		$query	= 'SELECT value FROM '.$this->tableName.' WHERE hash="'.$key.'"';
		$result	= mysql_query( $query, $this->resource );
		if( !mysql_num_rows( $result ) )
			return NULL;
		return mysql_fetch_row( $result );
	}

	/**
	 *	Indicates whether a data pair is stored by its key.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		boolean
	 */
	public function has( $key ){
		$query	= 'SELECT COUNT(value) as count FROM '.$this->tableName.' WHERE hash="'.$key.'"';
		$result	= mysql_query( $query, $this->resource );
		$row	= mysql_fetch_row( $result );
		print_m( $row );
		return $row['count'];
	}

	/**
	 *	Returns a list of all data pair keys.
	 *	@access		public
	 *	@return		array
	 */
	public function index(){
		$list	= array();
		$query	= 'SELECT hash FROM '.$this->tableName;
		$result	= mysql_query( $query, $this->resource );
		while( $row = mysql_fetch_array( $result ) )
			$list[]	= $row[0];
		return $list;
	}

	/**
	 *	Removes data pair from storage by its key.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		boolean
	 */
	public function remove( $key ){
		$query	= 'DELETE FROM '.$this->tableName.' WHERE hash="'.$key.'"';
		mysql_query( $query, $this->resource );
		return (bool) mysql_affected_rows();
	}

	/**
	 *	Adds or updates a data pair.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@param		string		$value		Data pair value
	 *	@param		integer		$expiration	Data life time in seconds or expiration timestamp
	 *	@return		void
	 */
	public function set( $key, $value, $expiration = NULL ){
		$value	= serialize( $value );
		if( $value === NULL || $value === '' )
			return $this->remove( $key );
		$query	= 'INSERT INTO '.$this->tableName.' (hash, value, timestamp, expiration) VALUES ("'.$key.'", "'.addslashes( $value ).'", "'.time().'", "'.$expiration.'") ON DUPLICATE KEY UPDATE value="'.$value.'"';
		mysql_query( $query, $this->resource );
	}

	public function setTableName( $tableName ){
		$this->tableName	= $tableName;
	}
}
?>
