<?php
/** @noinspection PhpMultipleClassDeclarationsInspection */
declare(strict_types=1);

/**
 *	....
 *	Supports context.
 *	@category		Library
 *	@package		CeusMedia_Cache_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
namespace CeusMedia\Cache\Adapter;

use CeusMedia\Cache\Encoder\JSON as JsonEncoder;
use CeusMedia\Cache\SimpleCacheException;
use CeusMedia\Cache\SimpleCacheInterface;
use CeusMedia\Cache\SimpleCacheInvalidArgumentException;
use CeusMedia\Cache\Util\FileLock;
use CeusMedia\Common\Exception\Deprecation as DeprecationException;
use CeusMedia\Common\FS\File\Editor as FileEditor;
use DateInterval;
use DateTime;
use Exception;
use RuntimeException;

/**
 *	....
 *	Supports context.
 *	@category		Library
 *	@package		CeusMedia_Cache_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
class JsonFile extends AbstractAdapter implements SimpleCacheInterface
{
	/**	@var	FileEditor				$file */
	protected FileEditor $file;

	/**	@var	FileLock				$lock */
	protected FileLock $lock;

	/**	@var	string					$resource */
	protected string $resource;

	/**	@var	array					$enabledEncoders	List of allowed encoder classes */
	protected array $enabledEncoders	= [
		JsonEncoder::class,
	];

	/**	@var	string|NULL				$encoder */
	protected ?string $encoder			= JsonEncoder::class;

	public function __construct( $resource, ?string $context = NULL, ?int $expiration = NULL )
	{
		$this->resource	= $resource;
		if( !file_exists( $resource ) )
			file_put_contents( $resource, $this->encodeValue( [] ) );
		$this->file	= new FileEditor( $resource );
		$this->lock	= new FileLock( $resource.'.lock' );
		$this->setContext( $context );
		if( $expiration !== NULL )
			$this->setExpiration( $expiration );
	}

	/**
	 *
	 *	@return		void
	 *	@codeCoverageIgnore
	 */
	public function cleanup(): void
	{
		$this->lock->lock();
		try{
			$changed	= FALSE;
			$contexts	= $this->decodeValue( $this->file->readString() ?? '[]' );
			foreach( $contexts as $context => $entries ){
				foreach( $entries as $key => $entry ){
					if( $this->isExpiredEntry( $entry ) ){
						unset( $contexts[$context][$key] );
						$changed	= TRUE;
					}
				}
			}
			if( $changed )
				file_put_contents( $this->resource, $this->encodeValue( $contexts ) );
			$this->lock->unlock();
		}
		catch( Exception $e ){
			$this->lock->unlock();
			throw new RuntimeException( 'Cleanup failed: '.$e->getMessage() );
		}
	}

	/**
	 *	Wipes clean the entire cache's keys.
	 *
	 *	@access		public
	 *	@return		bool		True on success and false on failure.
	 */
	public function clear(): bool
	{
		$this->lock->lock();
		$entries	= $this->decodeValue( $this->file->readString() ?? '[]' );
		if( isset( $entries[$this->context] ) ){
			foreach( array_keys( $entries[$this->context] ) as $key ){
				unset( $entries[$this->context][$key] );
			}
		}
		file_put_contents( $this->resource, $this->encodeValue( $entries ) );
		$this->lock->unlock();
		return TRUE;
	}

	/**
	 *	Delete an item from the cache by its unique key.
	 *
	 *	@access		public
	 *	@param		string		$key		The unique cache key of the item to delete.
	 *	@return		boolean		True if the item was successfully removed. False if there was an error.
	 *	@throws		SimpleCacheInvalidArgumentException		if the $key string is not a legal value.
	 */
	public function delete( string $key ): bool
	{
		$this->checkKey( $key );
		$entries	= $this->decodeValue( $this->file->readString() ?? '[]' );
		if( !isset( $entries[$this->context][$key] ) )
			return FALSE;
		$this->lock->lock();
		try{
			unset( $entries[$this->context][$key] );
			$this->file->writeString( $this->encodeValue( $entries ) );
			$this->lock->unlock();
		}
		catch( Exception $e ){
			$this->lock->unlock();
			throw new RuntimeException( 'Removing cache key failed: '.$e->getMessage() );
		}
		return TRUE;
	}

	/**
	 *	Not implemented, yet.
	 *	Originally: Deletes multiple cache items in a single operation.
	 *
	 *	@param		iterable	$keys		A list of string-based keys to be deleted.
	 *	@return		boolean		True if the items were successfully removed. False if there was an error.
	 *	@throws		SimpleCacheInvalidArgumentException		if $keys is neither an array nor a Traversable,
	 *												or if any of the $keys are not a legal value.
	 */
	public function deleteMultiple( iterable $keys ): bool
	{
		foreach( $keys as $key )
			$this->checkKey( $key );
		foreach( $keys as $key )
			$this->delete( $key );
		return TRUE;
	}

	/**
	 *	Deprecated alias of clear.
	 *	@access		public
	 *	@return		static
	 *	@deprecated	use clear instead
	 *	@codeCoverageIgnore
	 */
	public function flush(): static
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
	 *	@throws		SimpleCacheInvalidArgumentException		if the $key string is not a legal value.
	 */
	public function get( string $key, mixed $default = NULL ): mixed
	{
		$this->checkKey( $key );
		$entries	= $this->decodeValue( $this->file->readString() ?? '[]' );
		if( !isset( $entries[$this->context][$key] ) )
			return $default;
		$entry	= $entries[$this->context][$key];
		if( $this->isExpiredEntry( $entry ) ){
			$this->delete( $key );
			return $default;
		}
		return unserialize( $entry['value'] );
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
	 *	@throws		SimpleCacheInvalidArgumentException	if the $key string is not a legal value
	 */
	public function has( string $key ): bool
	{
		$this->checkKey( $key );
		$entries	= $this->decodeValue( $this->file->readString() ?? '[]' );
		if( !isset( $entries[$this->context][$key] ) )
			return FALSE;
		$entry	= $entries[$this->context][$key];
		if( $this->isExpiredEntry( $entry ) ){
			$this->delete( $key );
			return FALSE;
		}
		return TRUE;
	}

	/**
	 *	Returns a list of all data pair keys.
	 *	@access		public
	 *	@return		array
	 *	@throws		SimpleCacheException		if reading or writing data failed
	 */
	public function index(): array
	{
		$entries	= $this->decodeValue( $this->file->readString() ?? '[]' );
		if( !isset( $entries[$this->context] ) )
			return array();
		if( 0 !== $this->expiration ){
			foreach( $entries[$this->context] as $key => $entry ){
				if( $this->isExpiredEntry( $entry ) ){
					/** @noinspection PhpUnhandledExceptionInspection */
					$this->delete( $key );
					unset( $entries[$this->context][$key] );
				}
			}
		}
		return array_keys( $entries[$this->context] );
	}

	/**
	 *	Deprecated alias of delete.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		boolean
	 *	@deprecated	use delete instead
	 *	@codeCoverageIgnore
	 *	@noinspection PhpUnusedParameterInspection
	 */
	public function remove( string $key ): bool
	{
		throw DeprecationException::create()
			->setMessage( 'Deprecated' )
			->setSuggestion( 'Use delete instead' );
//		return $this->delete( $key );
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
	 *	@throws		SimpleCacheInvalidArgumentException		if the $key string is not a legal value.
	 */
	public function set( string $key, mixed $value, DateInterval|int $ttl = NULL ): bool
	{
		$this->checkKey( $key );
		$this->lock->lock();
		try{
			if( is_object( $value ) || is_resource( $value ) )
				throw new SimpleCacheInvalidArgumentException( 'Value must not be an object or resource' );
			if( $value === NULL || $value === '' )
				return $this->delete( $key );
			$ttl	= $ttl ?? $this->expiration;
			if( 0 === $ttl )
				throw new SimpleCacheInvalidArgumentException( 'TTL must be given on this adapter' );
			if( is_int( $ttl ) )
				$ttl	= new DateInterval( 'PT'.$ttl.'S' );
			$expiresAt	= (new DateTime)->add( $ttl )->format( 'U' );

			$entries	= $this->decodeValue( $this->file->readString() ?? '[]' );
			if( !isset( $entries[$this->context] ) )
				$entries[$this->context]	= [];

			$entries[$this->context][$key] = array(
				'value'		=> serialize( $value ),
				'timestamp'	=> time(),
				'expires'	=> $expiresAt,
	//			'tags'		=> $tags,
			);
			$this->file->writeString( $this->encodeValue( $entries ) );
			$this->lock->unlock();
			return TRUE;
		}
		catch( Exception $e ){
			$this->lock->unlock();
			throw new RuntimeException( 'Setting cache key failed: '.$e->getMessage() );
		}
//		return FALSE;
	}

	/**
	 *	Sets context within storage.
	 *	@access		public
	 *	@param		string|NULL		$context		Context within storage
	 *	@return		SimpleCacheInterface
	 */
	public function setContext( ?string $context = NULL ): SimpleCacheInterface
	{
		if( NULL === $context || '' === $context )
			$context	= 'default';
		return parent::setContext( $context );
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
	 *	@throws		SimpleCacheInvalidArgumentException	if any of the given keys is invalid
	 *	@throws		SimpleCacheException				if writing data failed
	 */
	public function setMultiple( iterable $values, DateInterval|int $ttl = NULL ): bool
	{
		return parent::setMultiple( $values, $ttl );
	}

	//  --  PROTECTED  --  //

	protected function isExpiredEntry( array $entry ): bool
	{
		if( 0 !== $this->expiration ){
			$now	= time();
			$age	= (int) $entry['timestamp'] + $this->expiration;
			if( $age <= $now || $entry['expires'] <= $now )
				return TRUE;
		}
		return FALSE;
	}
}
