<?php
/**
 *	Storage implementation using a database table via a PDO connection.
 *	Supports context. Does not support expiration, yet.
 *	@category		Library
 *	@package		CeusMedia_Cache_Adapter
 *	@extends		CMM_SEA_Adapter_Abstract
 *	@implements		CMM_SEA_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 */
namespace CeusMedia\Cache\Adapter;
/**
 *	Storage implementation using a database table via a PDO connection.
 *	Supports context. Does not support expiration, yet.
 *	@category		Library
 *	@package		CeusMedia_Cache_Adapter
 *	@extends		CMM_SEA_Adapter_Abstract
 *	@implements		CMM_SEA_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 *	@todo			implement expiration and cleanup
 */
class PDO extends \CeusMedia\Cache\AdapterAbstract implements \CeusMedia\Cache\AdapterInterface{

	protected $context		= '';
	protected $tableName	= 'cache';

	/**	@var	Database_PDO_Connection	$resource		PDO database connection */
	protected $resource;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		array		$resource		List of PDO database connection and table name
	 *	@param		string		$context		Name of context in table
	 *	@param		integer		$expiration		Number of seconds until data sets expire
	 *	@return		void
	 */
	public function __construct( $resource = NULL, $context = NULL, $expiration = NULL ){
		$this->resource		= $resource[0];
		$this->tableName	= $resource[1];
		if( !( $this->resource instanceof PDO ) )
			throw new InvalidArgumentException( 'No PDO database connection set' );
		if( !$this->tableName )
			throw new InvalidArgumentException( 'No table name set' );
		if( $context !== NULL )
			$this->setContext( $context );
		if( $expiration !== NULL )
			$this->setContext( $expiration );
	}

	/**
	 *	Removes all data pairs from storage.
	 *	@access		public
	 *	@return		integer		Number of removed rows
	 */
	public function flush(){
		$query	= 'DELETE FROM '.$this->tableName.' WHERE context="'.$this->context.'"';
		return $this->resource->exec( $query );
	}

	/**
	 *	Returns a data pair value by its key or NULL if pair not found.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		mixed
	 */
	public function get( $key ){
		$query	= 'SELECT value FROM '.$this->tableName.' WHERE context="'.$this->context.'" AND hash="'.$key.'"';
		$result	= $this->resource->query( $query );
		if( $result === NULL )																		//  query was not successful
			throw new RuntimeException( 'Table "'.$this->tableName.'" not found or invalid' );		//  inform about invalid table
		$result	= $result->fetch( PDO::FETCH_OBJ );													//  fetch row object
		if( $result === FALSE )																		//  no row found
			return NULL;																			//  quit with empty result
		return $result->value;																		//  return value
	}

	/**
	 *	Indicates whether a data pair is stored by its key.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		boolean		Result state of operation
	 */
	public function has( $key ){
		$query	= 'SELECT COUNT(value) as count FROM '.$this->tableName.' WHERE context="'.$this->context.'" AND hash="'.$key.'"';
		$result	= $this->resource->query( $query );
		if( $result === NULL )																		//  query was not successful
			throw new RuntimeException( 'Table "'.$this->tableName.'" not found or invalid' );		//  inform about invalid table
		return (bool) $result->fetch( PDO::FETCH_OBJ )->count;
	}

	/**
	 *	Returns a list of all data pair keys.
	 *	@access		public
	 *	@return		array
	 */
	public function index(){
		$query	= 'SELECT hash FROM '.$this->tableName.' WHERE context="'.$this->context.'"';
		$result	= $this->resource->query( $query );
		if( $result === NULL )																		//  query was not successful
			throw new RuntimeException( 'Table "'.$this->tableName.'" not found or invalid' );		//  inform about invalid table
		$list	= array();
		foreach( $result->fetchAll( PDO::FETCH_OBJ ) as $row )
			$list[]	= $row->hash;
		return $list;
	}

	/**
	 *	Removes data pair from storage by its key.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		boolean		Result state of operation
	 */
	public function remove( $key ){
		$query	= 'DELETE FROM '.$this->tableName.' WHERE context="'.$this->context.'" AND hash="'.$key.'"';
		return (bool) $this->resource->exec( $query );
	}

	/**
	 *	Adds or updates a data pair.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@param		string		$value		Data pair value
	 *	@param		integer		$expiration	Data life time in seconds or expiration timestamp
	 *	@return		boolean		Result state of operation
	 */
	public function set( $key, $value, $expiration = NULL ){
		if( is_object( $value ) || is_resource( $value ) )
			throw new InvalidArgumentException( 'Value must not be an object or resource' );
		if( $value === NULL || $value === '' )
			return $this->remove( $key );
		if( $this->has( $key ) )
			$query	= 'UPDATE '.$this->tableName.' SET value="'.addslashes( $value ).'", timestamp="'.time().'", expiration='.(int) $expiration.' WHERE context="'.$this->context.'" AND hash="'.$key.'"';
		else
			$query	= 'INSERT INTO '.$this->tableName.' (context, hash, value, timestamp, expiration) VALUES ("'.$this->context.'", "'.$key.'", "'.addslashes( $value ).'", "'.time().'", '.(int) $expiration.')';
		return (bool) $this->resource->exec( $query );
	}
}
?>
