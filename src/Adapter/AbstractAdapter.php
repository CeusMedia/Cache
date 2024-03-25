<?php
/** @noinspection PhpMultipleClassDeclarationsInspection */
declare(strict_types=1);

/**
 *	Adapter abstraction, adding some magic to the storage engine instance.
 *	@category		Library
 *	@package		CeusMedia_Cache
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
namespace CeusMedia\Cache\Adapter;

use ArrayAccess;
use CeusMedia\Cache\Encoder\SupportException as EncoderSupportException;
use CeusMedia\Cache\SimpleCacheException;
use CeusMedia\Cache\SimpleCacheInterface;
use CeusMedia\Cache\SimpleCacheInvalidArgumentException;
use CeusMedia\Common\Exception\IO as IoException;
use DateInterval;

/**
 *	Adapter abstraction, adding some magic to the storage engine instance.
 *	@category		Library
 *	@package		CeusMedia_Cache
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
abstract class AbstractAdapter implements ArrayAccess, SimpleCacheInterface
{
	/** @var		string|NULL			$context			... */
	protected ?string $context			= NULL;

	/** @var		array				$enabledEncoders	List of allowed encoder classes */
	protected array $enabledEncoders	= [];

	/** @var		string|NULL			$encoder			... */
	protected ?string $encoder			= NULL;

	/** @var		integer				$expiration			... */
	protected int $expiration			= 0;

	protected string $regexKey			= '@^[a-z0-9_./\[\]]+$@i';

	/**
	 *	Returns a data pair value by its key or NULL if pair not found.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		mixed
	 *	@throws		SimpleCacheInvalidArgumentException	if given key is invalid
	 *	@throws		SimpleCacheException				if reading data failed
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
	 *	@throws		SimpleCacheInvalidArgumentException	if given key is invalid
	 *	@throws		SimpleCacheException				if reading data failed
	 */
	public function __isset( string $key )
	{
		return $this->has( $key );
	}

	/**
	 *	Adds or updates a data pair.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@param		mixed		$value		Data pair value
	 *	@return		void
	 *	@throws		SimpleCacheInvalidArgumentException	if given key is invalid
	 *	@throws		SimpleCacheException				if writing data failed
	 */
	public function __set( string $key, mixed $value )
	{
		$this->set( $key, $value );
	}

	/**
	 *	Removes data pair from storage by its key.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		void
	 *	@throws		SimpleCacheInvalidArgumentException	if given key is invalid
	 *	@throws		SimpleCacheException				if writing data failed
	 */
	public function __unset( string $key ): void
	{
		$this->delete( $key );
	}

	/**
	 *	Indicates whether a data pair is stored by its key.
	 *	@access		public
	 *	@param		mixed		$offset		Data pair key
	 *	@return		boolean
	 *	@throws		SimpleCacheInvalidArgumentException	if given key is invalid
	 */
	public function offsetExists( mixed $offset ): bool
	{
		return $this->has( strval( $offset ) );
	}

	/**
	 *	Returns a data pair value by its key or NULL if pair not found.
	 *	@access		public
	 *	@param		mixed		$offset		Data pair key
	 *	@return		mixed
	 *	@throws		SimpleCacheInvalidArgumentException	if given key is invalid
	 *	@throws		SimpleCacheException				if reading data failed
	 */
	public function offsetGet( mixed $offset ): mixed
	{
		return $this->get( strval( $offset ) );
	}

	/**
	 *	Adds or updates a data pair.
	 *	@access		public
	 *	@param		mixed		$offset		Data pair key
	 *	@param		mixed		$value		Data pair value
	 *	@return		void
	 *	@throws		SimpleCacheInvalidArgumentException	if given key is invalid
	 *	@throws		SimpleCacheException				if writing data failed
	 */
	public function offsetSet( mixed $offset, mixed $value ): void
	{
		$this->set( strval( $offset ), $value );
	}

	/**
	 *	Removes data pair from storage by its key.
	 *	@access		public
	 *	@param		mixed		$offset		Data pair key
	 *	@return		void
	 *	@throws		SimpleCacheInvalidArgumentException	if given key is invalid
	 *	@throws		SimpleCacheException				if writing data failed
	 */
	public function offsetUnset( mixed $offset ): void
	{
		$this->delete( strval( $offset ) );
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
	 *	Returns current encoder class.
	 *	@access		public
	 *	@return		string|NULL
	 */
	public function getEncoder(): ?string
	{
		return $this->encoder;
	}

	/**
	 *	Returns data lifetime in seconds or expiration timestamp.
	 *	@access		public
	 *	@return		int
	 */
	public function getExpiration(): int
	{
		return $this->expiration;
	}

	/**
	 *	Obtains multiple cache items by their unique keys.
	 *
	 *	@param		iterable	$keys		A list of keys that can obtained in a single operation.
	 *	@param		mixed		$default	Default value to return for keys that do not exist.
	 *	@return		array<string,mixed>		A list of key => value pairs. Cache keys that do not exist or are stale will have $default as value.
	 *	@throws		SimpleCacheInvalidArgumentException	if given key is invalid
	 *	@throws		SimpleCacheException				if reading data failed
	 */
	public function getMultiple( iterable $keys, mixed $default = NULL ): array
	{
		foreach( $keys as $key )
			$this->checkKey( $key );
		$list	= [];
		/** @var string $key */
		foreach( $keys as $key )
			$list[$key]	= $this->get( $key, $default );
		return $list;
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
	 *	@param		integer		$expiration	Data lifetime in seconds or expiration timestamp
	 *	@return		SimpleCacheInterface
	 */
	public function setExpiration( int $expiration ): SimpleCacheInterface
	{
		$this->expiration	= abs( $expiration );
		return $this;
	}

	/**
	 *	Persists a set of key => value pairs in the cache, with an optional TTL.
	 *
	 *	@param		iterable				$values		A list of key => value pairs for a multiple-set operation.
	 *	@param		DateInterval|int|NULL	$ttl		Optional. The TTL value of this item. If no value is sent and
	 *													the driver supports TTL then the library may set a default value
	 *													for it or let the driver take care of that.
	 *	@return		bool		True on success and false on failure.
	 *	@throws		SimpleCacheInvalidArgumentException	if any of the given keys is invalid
	 *	@throws		SimpleCacheException				if writing data failed
	 */
	public function setMultiple( iterable $values, DateInterval|int $ttl = NULL ): bool
	{
		foreach( $values as $key => $value )
			$this->checkKey( (string) $key );
		foreach( $values as $key => $value )
			$this->set( (string) $key, $value );
		return TRUE;
	}

	//  --  PROTECTED  --  //

	/**
	 *	@param		string		$key
	 *	@return		bool
	 *	@throws		SimpleCacheInvalidArgumentException	if the $key string is not a legal value
	 */
	protected function checkKey( string $key ): bool
	{
		if( 1 === preg_match( $this->regexKey, $key ) )
			return TRUE;
		throw new SimpleCacheInvalidArgumentException( 'Invalid key: '.$key );
	}

	/**
	 *	Decodes encoded value by applying set encoder.
	 *	@access		protected
	 *	@param		string		$value		Encoded value
	 *	@return		mixed		Decoded value
	 */
	protected function decodeValue( string $value ): mixed
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
	protected function encodeValue( mixed $value ): string
	{
		if( NULL !== $this->encoder ){
			/** @var Callable $callable */
			$callable	= [$this->encoder, 'encode'];
			$value		= call_user_func_array( $callable, [$value] );
		}
		return strval( $value );
	}
}
