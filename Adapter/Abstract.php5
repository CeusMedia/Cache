<?php
/**
 *	....
 *	@category		cmModules
 *	@package		SEA
 *	@implements		ArrayAccess
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 *	@version		$Id$
 */
/**
 *	....
 *	@category		cmModules
 *	@package		SEA
 *	@implements		ArrayAccess
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 *	@version		$Id$
 */
abstract class CMM_SEA_Adapter_Abstract implements ArrayAccess{

	public function __get( $key ){
		return $this->get( $key );
	}
	
	public function __isset( $key ){
		return $this->has( $key );
	}
	
	public function __set( $key, $value ){
		return $this->set( $key, $value );
	}

	public function __unset( $key ){
		return $this->remove( $key );
	}

	public function offsetExists( $key ){
		return $this->has( $key );
	}

	public function offsetGet( $key ){
		return $this->get( $key );
	}

	public function offsetSet( $key, $value ){
		return $this->set( $key, $value );
	}
	
	public function offsetUnset( $key ){
		return $this->remove( $key );
	}
}
?>
