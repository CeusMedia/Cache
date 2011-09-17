<?php
/**
 *	Volatile Memory Storage.
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
 *	Volatile Memory Storage.
 *	Supports context.
 *	@category		cmModules
 *	@package		SEA
 *	@extends		CMM_SEA_Adapter_Abstract
 *	@implements		CMM_SEA_Adapter_Interface
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 *	@version		$Id$
 */
class CMM_SEA_Adapter_Memory extends CMM_SEA_Adapter_Abstract implements CMM_SEA_Adapter_Interface{

	protected $data	= array();

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		string		$resource		Memcache server hostname and port, eg. 'localhost:11211' (default)
	 *	@param		string		$context		Internal prefix for keys for separation
	 *	@param		integer		$expiration		Data life time in seconds or expiration timestamp
	 *	@return		void
	 */
	public function __construct( $resource = NULL, $context = NULL, $expiration = NULL ){
		if( $context !== NULL )
			$this->setContext( $context );
		if( $expiration !== NULL )
			$this->setExpiration( $expiration );
	}

	/**
	 *	Removes all data pairs from storage.
	 *	@access		public
	 *	@return		void
	 */
	public function flush(){
		$this->data	= array();
	}

	/**
	 *	Returns a data pair value by its key or NULL if pair not found.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		mixed
	 */
	public function get( $key ){
		if( isset( $this->data[$this->context.$key] ) )
			return $this->data[$this->context.$key];
		return NULL;
	}

	/**
	 *	Indicates whether a data pair is stored by its key.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		boolean
	 */
	public function has( $key ){
		return isset( $this->data[$this->context.$key] );
	}

	/**
	 *	Returns a list of all data pair keys.
	 *	@access		public
	 *	@return		array
	 */
	public function index(){
		if( $this->context ){
			$list	= array();
			$length	= strlen( $this->context );
			foreach( $this->data as $key => $value )
				if( substr( $key, 0, $length ) == $this->context )
					$list[]	= substr( $key, $length );
			return $list;
		}
		return array_keys( $this->data );
	}

	/**
	 *	Removes data pair from storage by its key.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		void
	 */
	public function remove( $key ){
		unset( $this->data[$this->context.$key] );
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
		$this->data[$this->context.$key]	= $value;
	}
}
?>
