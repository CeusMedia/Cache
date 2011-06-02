<?php
/**
 *	Fake storage engine with no operations at all.
 *	@category		cmModules
 *	@package		SEA
 *	@extends		CMM_SEA_Adapter_Abstract
 *	@implements		CMM_SEA_Adapter_Interface
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			02.06.2011
 *	@version		$Id$
 */
/**
 *	Fake storage engine with no operations at all.
 *	@category		cmModules
 *	@package		SEA
 *	@extends		CMM_SEA_Adapter_Abstract
 *	@implements		CMM_SEA_Adapter_Interface
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			02.06.2011
 *	@version		$Id$
 */
class CMM_SEA_Adapter_Noop extends CMM_SEA_Adapter_Abstract implements CMM_SEA_Adapter_Interface{

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
	 *	@return		void
	 */
	public function remove( $key ){}

	/**
	 *	Does nothing since there is no stored data.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@param		string		$value		Data pair value
	 *	@param		integer		$ttl		Data life time in seconds or expiration timestamp
	 *	@return		void
	 */
	public function set( $key, $value, $ttl = 0 ){}
}
?>