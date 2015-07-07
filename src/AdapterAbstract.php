<?php
/**
 *	Adapter abstraction, adding some magic to the storage engine instance.
 *	@category		Library
 *	@package		CeusMedia_Cache
 *	@implements		ArrayAccess
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 */
namespace CeusMedia\Cache;
/**
 *	Adapter abstraction, adding some magic to the storage engine instance.
 *	@category		Library
 *	@package		CeusMedia_Cache
 *	@implements		ArrayAccess
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 */
abstract class AdapterAbstract implements \ArrayAccess{

	protected $context;
	protected $expiration	= 0;

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
/*
	abstract public function get( $key );

	abstract public function has( $key );

	abstract public function remove( $key );

	abstract public function set( $key, $value );
*/
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

	/**
	 *	Returns current context within storage.
	 *	@access		public
	 *	@return		string
	 */
	public function getContext(){
		return $this->context;
	}

	/**
	 *	Sets context within storage.
	 *	@access		public
	 *	@param		string		$context		Context within storage
	 *	@return		void
	 */
	public function setContext( $context ){
		$this->context = $context;
	}

	/**
	 *	...
	 *	@access		public
	 *	@param		integer		$expiration	Data life time in seconds or expiration timestamp
	 *	@return		void
	 */
	public function setExpiration( $expiration ){
		$this->expiration	= abs( $expiration );
	}
}
?>
