<?php
declare(strict_types=1);

/**
 *	Volatile Memory Storage.
 *	Supports context.
 *	@category		Library
 *	@package		CeusMedia_Cache_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
namespace CeusMedia\Cache\Adapter;

use CeusMedia\Cache\AbstractAdapter;
use CeusMedia\Cache\Encoder\Noop as NoopEncoder;
use CeusMedia\Cache\SimpleCacheInterface;
use CeusMedia\Cache\SimpleCacheInvalidArgumentException as InvalidArgumentException;

use DateInterval;

/**
 *	Volatile Memory Storage.
 *	Supports context.
 *	@category		Library
 *	@package		CeusMedia_Cache_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
class Memory extends AbstractAdapter implements SimpleCacheInterface
{
	/**	@var	array					$data */
	protected array $data				= [];

	/**	@var	array					$enabledEncoders	List of allowed encoder classes */
	protected array $enabledEncoders	= [
		NoopEncoder::class,
	];

	/**	@var	string|NULL				$encoder */
	protected ?string $encoder			= NoopEncoder::class;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		mixed			$resource		No relevant for this adapter
	 *	@param		string|NULL		$context		Internal prefix for keys for separation
	 *	@param		integer|NULL	$expiration		Data life time in seconds or expiration timestamp
	 *	@return		void
	 */
	public function __construct( $resource, ?string $context = NULL, ?int $expiration = NULL )
	{
		if( $context !== NULL )
			$this->setContext( $context );
		if( $expiration !== NULL )
			$this->setExpiration( $expiration );
	}

	/**
	 *	Wipes clean the entire cache's keys.
	 *
	 *	@access		public
	 *	@return		bool		True on success and false on failure.
	 */
	public function clear(): bool
	{
		$this->data	= [];
		return TRUE;
	}

	/**
	 *	Delete an item from the cache by its unique key.
	 *
	 *	@access		public
	 *	@param		string		$key		The unique cache key of the item to delete.
	 *	@return		boolean		True if the item was successfully removed. False if there was an error.
	 *	@throws		InvalidArgumentException		if the $key string is not a legal value.
	 */
	public function delete( string $key ): bool
	{
		if( !$this->has( $key ) )
			return FALSE;
		unset( $this->data[$this->context.$key] );
		return TRUE;
	}

	/**
	 *	Not implemented, yet.
	 *	Originally: Deletes multiple cache items in a single operation.
	 *
	 *	@param		iterable	$keys		A list of string-based keys to be deleted.
	 *	@return		boolean		True if the items were successfully removed. False if there was an error.
	 *	@throws		InvalidArgumentException		if $keys is neither an array nor a Traversable,
	 *												or if any of the $keys are not a legal value.
	 *	@todo		implement
	 */
	public function deleteMultiple( iterable $keys ): bool
	{
		return TRUE;
	}

	/**
	 *	Deprecated alias of clear.
	 *	@access		public
	 *	@return		self
	 *	@deprecated	use clear instead
	 */
	public function flush(): self
	{
		$this->clear();
		return $this;
	}

	/**
	 *	Fetches a value from the cache.
	 *
	 *	@access		public
	 *	@param		string		$key		The unique key of this item in the cache.
	 *	@param		mixed		$default	Default value to return if the key does not exist.
	 *	@return		mixed		The value of the item from the cache, or $default in case of cache miss.
	 *	@throws		InvalidArgumentException		if the $key string is not a legal value.
	 */
	public function get( string $key, mixed $default = NULL ): mixed
	{
		if( isset( $this->data[$this->context.$key] ) )
			return $this->decodeValue( $this->data[$this->context.$key] );
		return NULL;
	}

	/**
	 *	Not implemented, yet.
	 *	Originally: Obtains multiple cache items by their unique keys.
	 *
	 *	@param		iterable	$keys		A list of keys that can obtained in a single operation.
	 *	@param		mixed		$default	Default value to return for keys that do not exist.
	 *	@return		iterable<string,mixed>	A list of key => value pairs. Cache keys that do not exist or are stale will have $default as value.
	 *	@throws		InvalidArgumentException		if $keys is neither an array nor a Traversable,
	 *												or if any of the $keys are not a legal value.
	 *	@todo		implement
	 */
	public function getMultiple( iterable $keys, mixed $default = NULL ): iterable
	{
		return [];
	}

	/**
	 * 	Determines whether an item is present in the cache.
	 *
	 *	NOTE: It is recommended that has() is only to be used for cache warming type purposes
	 *	and not to be used within your live applications operations for get/set, as this method
	 *	is subject to a race condition where your has() will return true and immediately after,
	 *	another script can remove it, making the state of your app out of date.
	 *
	 *	@access		public
	 *	@param		string		$key		The cache item key.
	 *	@return		boolean
	 *	@throws		InvalidArgumentException		if the $key string is not a legal value.
	 */
	public function has( string $key ): bool
	{
		return isset( $this->data[$this->context.$key] );
	}

	/**
	 *	Returns a list of all data pair keys.
	 *	@access		public
	 *	@return		array
	 */
	public function index(): array
	{
		if( NULL !== $this->context ){
			$list	= [];
			$length	= strlen( $this->context );
			foreach( $this->data as $key => $value )
				if( substr( $key, 0, $length ) == $this->context )
					$list[]	= substr( $key, $length );
			return $list;
		}
		return array_keys( $this->data );
	}

	/**
	 *	Deprecated alias of delete.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		boolean
	 *	@deprecated	use delete instead
	 */
	public function remove( string $key ): bool
	{
		return $this->delete( $key );
	}

	/**
	 *	Persists data in the cache, uniquely referenced by a key with an optional expiration TTL time.
	 *
	 *	@access		public
	 *	@param		string					$key		The key of the item to store.
	 *	@param		mixed					$value		The value of the item to store. Must be serializable.
	 *	@param		DateInterval|int|NULL	$ttl		Optional. The TTL value of this item. If no value is sent and
	 *													the driver supports TTL then the library may set a default value
	 *													for it or let the driver take care of that.
	 *	@return		boolean		True on success and false on failure.
	 *	@throws		InvalidArgumentException		if the $key string is not a legal value.
	 */
	public function set( string $key, mixed $value, DateInterval|int $ttl = NULL ): bool
	{
		$this->data[$this->context.$key]	= $this->encodeValue( $value );
		return TRUE;
	}

	/**
	 *	Not implemented, yet.
	 *	Originally: Persists a set of key => value pairs in the cache, with an optional TTL.
	 *
	 *	@param		iterable				$values		A list of key => value pairs for a multiple-set operation.
	 *	@param		DateInterval|int|NULL	$ttl		Optional. The TTL value of this item. If no value is sent and
	 *													the driver supports TTL then the library may set a default value
	 *													for it or let the driver take care of that.
	 *	@return		bool		True on success and false on failure.
	 *	@throws		InvalidArgumentException		if $values is neither an array nor a Traversable,
	 *												or if any of the $values are not a legal value.
	 */
	public function setMultiple( iterable $values, DateInterval|int $ttl = NULL ): bool
	{
		return TRUE;
	}

	//  --  PROTECTED  --  //

	/**
	 *	Improve speed by skipping encoder.
	 *	@access		protected
	 *	@param		string		$value		Value, will be reflected
	 *	@return		mixed		Reflected value
	 */
	protected function decodeValue( string $value ): mixed
	{
		return $value;
	}

	/**
	*	Improve speed by skipping encoder.
	 *	@access		protected
	 *	@param		mixed		$value		Value, will be reflected
	 *	@return		string		Reflected value
	 */
	protected function encodeValue( mixed $value ): string
	{
		return strval( $value );
	}
}
