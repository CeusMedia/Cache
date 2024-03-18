<?php
declare(strict_types=1);

/**
 *	Fake storage engine with no operations at all.
 *	@category		Library
 *	@package		CeusMedia_Cache_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
namespace CeusMedia\Cache\Adapter;

use CeusMedia\Cache\Encoder\Noop as NoopEncoder;
use CeusMedia\Cache\SimpleCacheInterface;
use CeusMedia\Cache\SimpleCacheInvalidArgumentException;
use CeusMedia\Common\Exception\Deprecation as DeprecationException;
use DateInterval;

/**
 *	Fake storage engine with no operations at all.
 *	@category		Library
 *	@package		CeusMedia_Cache_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
class Noop extends AbstractAdapter implements SimpleCacheInterface
{
	/**	@var	array					$enabledEncoders	List of allowed encoder classes */
	protected array $enabledEncoders	= [
		NoopEncoder::class,
	];

	/**	@var	string|NULL				$encoder */
	protected ?string $encoder			= NoopEncoder::class;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		string|NULL		$resource		Memcache server hostname and port, eg. 'localhost:11211' (default)
	 *	@param		string|NULL		$context		Internal prefix for keys for separation
	 *	@param		integer|NULL	$expiration		Data life time in seconds or expiration timestamp
	 *	@return		void
	 */
	public function __construct( $resource = NULL, ?string $context = NULL, ?int $expiration = NULL )
	{
		if( $context !== NULL )
			$this->setContext( $context );
		if( $expiration !== NULL )
			$this->setExpiration( $expiration );
	}

	/**
	 *	Does nothing since there is no stored data.
	 *	Originally: Wipes clean the entire cache's keys.
	 *
	 *	@access		public
	 *	@return		bool		True on success and false on failure.
	 */
	public function clear(): bool
	{
		return TRUE;
	}

	/**
	 *	Does nothing since there is no stored data.
	 *	Originally: Delete an item from the cache by its unique key.
	 *
	 *	@access		public
	 *	@param		string		$key		The unique cache key of the item to delete.
	 *	@return		boolean		True if the item was successfully removed. False if there was an error.
	 *	@throws		SimpleCacheInvalidArgumentException		if the $key string is not a legal value.
	 */
	public function delete( mixed $key ): bool
	{
		$this->checkKey( $key );
		return TRUE;
	}

	/**
	 *	Does nothing since there is no stored data.
	 *	Originally: Deletes multiple cache items in a single operation.
	 *
	 *	@param		iterable	$keys		A list of string-based keys to be deleted.
	 *	@return		boolean		True if the items were successfully removed. False if there was an error.
	 *	@throws		SimpleCacheInvalidArgumentException		if any of the $keys are not a legal value.
	 */
	public function deleteMultiple( iterable $keys ): bool
	{
		foreach( $keys as $key )
			$this->checkKey( $key );
		return TRUE;
	}

	/**
	 *	Does nothing since there is no stored data.
	 *	@access		public
	 *	@return		self
	 *	@deprecated	use clear instead
	 *	@codeCoverageIgnore
	 */
	public function flush(): self
	{
		return $this;
	}

	/**
	 *	Returns NULL always since there is no stored data.
	 *	Originally: Fetches a value from the cache.
	 *
	 *	@access		public
	 *	@param		string		$key		The unique key of this item in the cache.
	 *	@param		mixed		$default	Default value to return if the key does not exist.
	 *	@return		mixed		The value of the item from the cache, or $default in case of cache miss.
	 *	@throws		SimpleCacheInvalidArgumentException		if the $key string is not a legal value.
	 */
	public function get( string $key, mixed $default = NULL ): mixed
	{
		$this->checkKey( $key );
		return NULL;
	}

	/**
	 *	Returns empty list since there is no stored data.
	 *	Originally: Obtains multiple cache items by their unique keys.
	 *
	 *	@param		iterable	$keys		A list of keys that can obtained in a single operation.
	 *	@param		mixed		$default	Default value to return for keys that do not exist.
	 *	@return		array<string, mixed>			A list of key => value pairs. Cache keys that do not exist or are stale will have $default as value.
	 *	@throws		SimpleCacheInvalidArgumentException	if any of the $keys are not a legal value.
	 */
	public function getMultiple( iterable $keys, mixed $default = NULL ): array
	{
		foreach( $keys as $key )
			$this->checkKey( $key );
		return [];
	}

	/**
	 * 	Returns FALSE always since there is no stored data.
	 *	Originally: Determines whether an item is present in the cache.
	 *
	 *	NOTE: It is recommended that has() is only to be used for cache warming type purposes
	 *	and not to be used within your live applications operations for get/set, as this method
	 *	is subject to a race condition where your has() will return true and immediately after,
	 *	another script can remove it, making the state of your app out of date.
	 *
	 *	@access		public
	 *	@param		string		$key		The cache item key.
	 *	@return		boolean
	 *	@throws		SimpleCacheInvalidArgumentException	if the $key string is not a legal value.
	 */
	public function has( string $key ): bool
	{
		$this->checkKey( $key );
		return FALSE;
	}

	/**
	 *	Returns empty list since there is no stored data.
	 *	@access		public
	 *	@return		array
	 */
	public function index(): array
	{
		return array();
	}

	/**
	 *	Does nothing since there is no stored data.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		boolean
	 *	@deprecated	use delete instead
	 *	@codeCoverageIgnore
	 */
	public function remove( string $key ): bool
	{
		throw DeprecationException::create()
			->setMessage( 'Deprecated' )
			->setSuggestion( 'Use delete instead' );
//		return TRUE;
	}

	/**
	 *	Does nothing since there is no stored data.
	 *	Originally: Persists data in the cache, uniquely referenced by a key with an optional expiration TTL time.
	 *
	 *	@access		public
	 *	@param		string					$key		The key of the item to store.
	 *	@param		mixed					$value		The value of the item to store. Must be serializable.
	 *	@param		DateInterval|int|NULL	$ttl		Optional. The TTL value of this item. If no value is sent and
	 *													the driver supports TTL then the library may set a default value
	 *													for it or let the driver take care of that.
	 *	@return		boolean		True on success and false on failure.
	 *	@throws		SimpleCacheInvalidArgumentException	if the $key string is not a legal value.
	 */
	public function set( string $key, mixed $value, DateInterval|int|null $ttl = NULL ): bool
	{
		$this->checkKey( $key );
		return TRUE;
	}

	/**
	 *	Does nothing since there is no stored data.
	 *	Originally: Persists a set of key => value pairs in the cache, with an optional TTL.
	 *
	 *	@param		iterable				$values		A list of key => value pairs for a multiple-set operation.
	 *	@param		DateInterval|int|NULL	$ttl		Optional. The TTL value of this item. If no value is sent and
	 *													the driver supports TTL then the library may set a default value
	 *													for it or let the driver take care of that.
	 *	@return		bool		True on success and false on failure.
	 *	@throws		SimpleCacheInvalidArgumentException	if any of the $values are not a legal value.
	 */
	public function setMultiple( iterable $values, DateInterval|int|null $ttl = NULL ): bool
	{
		foreach( $values as $key => $value )
			$this->checkKey( $key );
		return TRUE;
	}
}
