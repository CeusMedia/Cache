<?php
/**
 *	Fake storage engine with no operations at all.
 *	@category		cmModules
 *	@package		SEA
 *	@extends		CMM_SEA_Adapter_Abstract
 *	@implements		CMM_SEA_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			02.06.2011
 *	@version		$Id$
 */
/**
 *	Fake storage engine with no operations at all.
 *	@category		cmModules
 *	@package		SEA
 *	@extends		CMM_SEA_Adapter_Abstract
 *	@implements		CMM_SEA_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			02.06.2011
 *	@version		$Id$
 */
class CMM_SEA_Adapter_Noop extends CMM_SEA_Adapter_Abstract implements CMM_SEA_Adapter{

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		string		$resource		Memcache server hostname and port, eg. 'localhost:11211' (default)
	 *	@param		string		$context		Internal prefix for keys for separation
	 *	@param		integer		$expiration		Data life time in seconds or expiration timestamp
	 *	@return		void
	 */
	public function __construct( $resource = NULL, $context = NULL, $expiration = NULL ){
	}

	/**
	 *	Does nothing since there is no stored data.
	 *	@access		public
	 *	@return		void
	 */
	public function flush(){}

	/**
	 *	Returns NULL always since there is no stored data.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		void
	 */
	public function get( $key ){
		return NULL;
	}

	/**
	 *	Returns FALSE always since there is no stored data.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		boolean
	 */
	public function has( $key ){
		return FALSE;
	}

	/**
	 *	Returns empty list since there is no stored data.
	 *	@access		public
	 *	@return		array
	 */
	public function index(){
		return array();
	}

	/**
	 *	Does nothing since there is no stored data.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		boolean
	 */
	public function remove( $key ){
		return TRUE;
	}

	/**
	 *	Does nothing since there is no stored data.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@param		string		$value		Data pair value
	 *	@param		integer		$expiration	Data life time in seconds or expiration timestamp
	 *	@return		void
	 */
	public function set( $key, $value, $expiration = NULL ){}
}
?>
