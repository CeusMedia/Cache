<?php
/**
 *	....
 *	Supports context.
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
 *	Supports context.
 *	@category		cmModules
 *	@package		SEA
 *	@extends		CMM_SEA_Adapter_Abstract
 *	@implements		CMM_SEA_Adapter_Interface
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 *	@version		$Id$
 */
class CMM_SEA_Adapter_PDO extends CMM_SEA_Adapter_Abstract{

	protected $context	= 'cache';
	protected $resource;

	public function __construct( $resource = NULL, $context = NULL, $expiration = NULL ){
		$this->resource	= $resource;
		if( $context !== NULL )
			$this->setContext( $context );
		if( $expiration !== NULL )
			$this->setContext( $expiration );
	}

	/**
	 *	Removes all data pairs from storage.
	 *	@access		public
	 *	@return		integer
	 */
	public function flush(){
		$query	= 'DELETE FROM '.$this->context;
		return $this->resource->exec( $query );
	}

	/**
	 *	Returns a data pair value by its key or NULL if pair not found.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		mixed
	 */
	public function get( $key ){
		$query	= 'SELECT value FROM '.$this->context.' WHERE hash="'.$key.'"';
		$result	= $this->resource->query( $query );
		return $result->fetch( PDO::FETCH_OBJ )->value;
	}

	/**
	 *	Indicates whether a data pair is stored by its key.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		boolean
	 */
	public function has( $key ){
		$query	= 'SELECT COUNT(value) as count FROM '.$this->context.' WHERE hash="'.$key.'"';
		$result	= $this->resource->query( $query );
		return (bool) $result->fetch( PDO::FETCH_OBJ )->count;
	}

	/**
	 *	Returns a list of all data pair keys.
	 *	@access		public
	 *	@return		array
	 */
	public function index(){
		$list	= array();
		$query	= 'SELECT hash FROM '.$this->context;
		$result	= $this->resource->query( $query );
		if( $result )
			foreach( $result->fetch( PDO::FETCH_OBJ ) as $key )
				$list[]	= $key;
		return $list;
	}

	/**
	 *	Removes data pair from storage by its key.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		boolean
	 */
	public function remove( $key ){
		$query	= 'DELETE FROM '.$this->context.' WHERE hash="'.$key.'"';
		return (bool) $this->resource->exec( $query );
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
		if( $value === NULL || $value === '' )
			return $this->remove( $key );
		else if( $this->has( $key ) )
			$query	= 'UPDATE '.$this->context.' SET value="'.serialize( $value ).'" WHERE hash="'.$key.'"';
		else
			$query	= 'INSERT INTO '.$this->context.' (hash, value) VALUES ("'.$key.'", "'.serialize( $value ).'")';
		$this->resource->exec( $query );
	}
}
?>
