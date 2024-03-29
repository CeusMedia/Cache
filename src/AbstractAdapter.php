<?php
declare(strict_types=1);

/**
 *	Adapter abstraction, adding some magic to the storage engine instance.
 *	@category		Library
 *	@package		CeusMedia_Cache
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
namespace CeusMedia\Cache;

use CeusMedia\Cache\Encoder\SupportException as EncoderSupportException;

use ArrayAccess;
use RangeException;


/**
 *	Adapter abstraction, adding some magic to the storage engine instance.
 *	@category		Library
 *	@package		CeusMedia_Cache
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
abstract class AbstractAdapter implements ArrayAccess, SimpleCacheInterface
{
	/** @var		string|NULL		$context			... */
	protected $context;

	/** @var		array			$enabledEncoders	List of allowed encoder classes */
	protected $enabledEncoders	= [];

	/** @var		string|NULL		$encoder			... */
	protected $encoder;

	/** @var		integer			$expiration			... */
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
	 *	Returns data life time in seconds or expiration timestamp.
	 *	@access		public
	 *	@return		mixed
	 */
	public function getExpiration()
	{
		return $this->expiration;
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

	public function setEncoder( string $className ): SimpleCacheInterface
	{
		$enabledAll = 0 === count( $this->enabledEncoders );
		$enabledThis = in_array( $className, $this->enabledEncoders, TRUE );
		if( !$enabledAll && !$enabledThis )
			throw new EncoderSupportException( 'This encoder is not enabled for this adapter' );
		$this->encoder = $className;
		return $this;
	}

	/**
	 *	Sets data life time in seconds or expiration timestamp.
	 *	@access		public
	 *	@param		integer		$expiration	Data life time in seconds or expiration timestamp
	 *	@return		SimpleCacheInterface
	 */
	public function setExpiration( int $expiration ): SimpleCacheInterface
	{
		$this->expiration	= abs( $expiration );
		return $this;
	}

	/**
	 *	Decodes encoded value by applying set encoder.
	 *	@access		protected
	 *	@param		string		$value		Encoded value
	 *	@return		mixed		Decoded value
	 */
	protected function decodeValue( string $value )
	{
		if( NULL !== $this->encoder ){
			/** @var Callable $callable */
			$callable	= [$this->encoder, 'decode'];
			$value		= call_user_func_array( $callable, [$value] );
		}
		return $value;
	}

	/**
	 *	Encodes decoded value by applying set encoder.
	 *	@access		protected
	 *	@param		mixed		$value		Decoded value
	 *	@return		string		Encoded value
	 */
	protected function encodeValue( $value ): string
	{
		if( NULL !== $this->encoder ){
			/** @var Callable $callable */
			$callable	= [$this->encoder, 'encode'];
			$value		= call_user_func_array( $callable, [$value] );
		}
		return $value;
	}
}
