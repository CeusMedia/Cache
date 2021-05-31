<?php
/**
 *	Adapter abstraction, adding some magic to the storage engine instance.
 *	@category		Library
 *	@package		CeusMedia_Cache
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 */
namespace CeusMedia\Cache;

use ArrayAccess;

/**
 *	Adapter abstraction, adding some magic to the storage engine instance.
 *	@category		Library
 *	@package		CeusMedia_Cache
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 */
abstract class AbstractAdapter implements ArrayAccess
{
	/** @var		string|NULL		$context		... */
	protected $context;

	/** @var		integer			$expiration		... */
	protected $expiration	= 0;

	/**
	 *	Returns a data pair value by its key or NULL if pair not found.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		mixed
	 */
	public function __get( $key )
	{
		return $this->get( $key );
	}

	public function __isset( $key )
	{
		return $this->has( $key );
	}

	public function __set( $key, $value )
	{
		return $this->set( $key, $value );
	}

	public function __unset( $key )
	{
		$this->remove( $key );
	}

	/**
	 *	Returns a data pair value by its key or NULL if pair not found.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		mixed
	 */
	abstract public function get( string $key );

	/**
	 *	Indicates whether a data pair is stored by its key.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		boolean
	 */
	abstract public function has( string $key ): bool;

	/**
	 *	Returns a list of all data pair keys.
	 *	@access		public
	 *	@return		array
	 */
	abstract public function index(): array;

	/**
	 *	Removes data pair from storage by its key.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		boolean		Result state of operation
	 */
	abstract public function remove( string $key ): bool;

	/**
	 *	Adds or updates a data pair.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@param		mixed		$value		Data pair value
	 *	@param		integer		$expiration	Data life time in seconds or expiration timestamp
	 *	@return		boolean		Result state of operation
	 */
	abstract public function set( string $key, $value, int $expiration = NULL ): bool;

	public function offsetExists( $key )
	{
		return $this->has( $key );
	}

	public function offsetGet( $key )
	{
		return $this->get( $key );
	}

	public function offsetSet( $key, $value )
	{
		$this->set( $key, $value );
	}

	public function offsetUnset( $key )
	{
		$this->remove( $key );
	}

	/**
	 *	Returns current context within storage.
	 *	@access		public
	 *	@return		string
	 */
	public function getContext(): string
	{
		return $this->context;
	}

	/**
	 *	Sets context within storage.
	 *	@access		public
	 *	@param		string		$context		Context within storage
	 *	@return		AbstractAdapter
	 */
	public function setContext( string $context ): AbstractAdapter
	{
		$this->context = $context;
		return $this;
	}

	/**
	 *	...
	 *	@access		public
	 *	@param		integer		$expiration	Data life time in seconds or expiration timestamp
	 *	@return		AbstractAdapter
	 */
	public function setExpiration( int $expiration ): AbstractAdapter
	{
		$this->expiration	= abs( $expiration );
		return $this;
	}
}
