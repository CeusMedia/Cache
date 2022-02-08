<?php
/**
 *	Adapter abstraction, adding some magic to the storage engine instance.
 *	@category		Library
 *	@package		CeusMedia_Cache
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
namespace CeusMedia\Cache;

use ArrayAccess;

/**
 *	Adapter abstraction, adding some magic to the storage engine instance.
 *	@category		Library
 *	@package		CeusMedia_Cache
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
abstract class AbstractAdapter implements ArrayAccess, SimpleCacheInterface
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
	public function __get( string $key )
	{
		return $this->get( $key );
	}

	/**
	 *	Indicates whether a data pair is stored by its key.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		boolean
	 */
	public function __isset( string $key )
	{
		return $this->has( $key );
	}

	/**
	 *	Adds or updates a data pair.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@param		string		$value		Data pair value
	 *	@return		boolean
	 */
	public function __set( string $key, $value )
	{
		return $this->set( $key, $value );
	}

	/**
	 *	Removes data pair from storage by its key.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		boolean
	 */
	public function __unset( string $key )
	{
		$this->delete( $key );
	}

	/**
	 *	Indicates whether a data pair is stored by its key.
	 *	@access		public
	 *	@param		mixed		$key		Data pair key
	 *	@return		boolean
	 */
	public function offsetExists( $key )
	{
		return $this->has( $key );
	}

	/**
	 *	Returns a data pair value by its key or NULL if pair not found.
	 *	@access		public
	 *	@param		mixed		$key		Data pair key
	 *	@return		mixed
	 */
	public function offsetGet( $key )
	{
		return $this->get( $key );
	}

	/**
	 *	Adds or updates a data pair.
	 *	@access		public
	 *	@param		mixed		$key		Data pair key
	 *	@param		mixed		$value		Data pair value
	 *	@return		boolean		Result state of operation
	 */
	public function offsetSet( $key, $value )
	{
		return $this->set( $key, $value );
	}

	/**
	 *	Removes data pair from storage by its key.
	 *	@access		public
	 *	@param		mixed		$key		Data pair key
	 *	@return		boolean		Result state of operation
	 */
	public function offsetUnset( $key )
	{
		return  $this->delete( $key );
	}

	/**
	 *	Returns current context within storage.
	 *	@access		public
	 *	@return		string|NULL
	 */
	public function getContext(): ?string
	{
		return $this->context;
	}

	/**
	 *	Sets context within storage.
	 *	@access		public
	 *	@param		string|NULL		$context		Context within storage
	 *	@return		SimpleCacheInterface
	 */
	public function setContext( ?string $context = NULL ): SimpleCacheInterface
	{
		$this->context = $context;
		return $this;
	}

	/**
	 *	...
	 *	@access		public
	 *	@param		integer		$expiration	Data life time in seconds or expiration timestamp
	 *	@return		SimpleCacheInterface
	 */
	public function setExpiration( int $expiration ): SimpleCacheInterface
	{
		$this->expiration	= abs( $expiration );
		return $this;
	}
}
