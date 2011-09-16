<?php
/**
 *	Adapter interface.
 *	@category		cmModules
 *	@package		SEA
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 *	@version		$Id$
 */
/**
 *	Adapter interface.
 *	@category		cmModules
 *	@package		SEA
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 *	@version		$Id$
 */
interface CMM_SEA_Adapter_Interface{

	/**
	 *	Removes all data pairs from storage.
	 *	@access		public
	 *	@return		void
	 */
	public function flush();
	
	/**
	 *	Returns a data pair value by its key or NULL if pair not found.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		mixed
	 */
	public function get( $key );
	
	/**
	 *	Indicates whether a data pair is stored by its key.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		boolean
	 */
	public function has( $key );
	
	/**
	 *	Returns a list of all data pair keys.
	 *	@access		public
	 *	@return		array
	 */
	public function index();
	
	/**
	 *	Removes data pair from storage by its key.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		void
	 */
	public function remove( $key );
	
	/**
	 *	Adds or updates a data pair.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@param		string		$value		Data pair value
	 *	@param		integer		$expiration	Data life time in seconds or expiration timestamp
	 *	@return		void
	 */
	public function set( $key, $value, $expiration = NULL );
}
?>
