<?php
/** @noinspection PhpMultipleClassDeclarationsInspection */
declare(strict_types=1);

/**
 *	....
 *	@category		Library
 *	@package		CeusMedia_Cache_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
namespace CeusMedia\Cache\Adapter;

use CeusMedia\Cache\Encoder\JSON as JsonEncoder;
use CeusMedia\Cache\Encoder\Serial as SerialEncoder;
use CeusMedia\Cache\SimpleCacheException;
use CeusMedia\Cache\SimpleCacheInterface;
use CeusMedia\Cache\SimpleCacheInvalidArgumentException;
use CeusMedia\Common\Exception\Deprecation as DeprecationException;
use CeusMedia\Common\FS\File\Reader as FileReader;
use CeusMedia\Common\FS\File\Writer as FileWriter;
use DateInterval;
use Throwable;

/**
 *	....
 *	@category		Library
 *	@package		CeusMedia_Cache_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
class IniFile extends AbstractAdapter implements SimpleCacheInterface
{
	/**	@var	array					$enabledEncoders	List of allowed encoder classes */
	protected array $enabledEncoders	= [
		JsonEncoder::class,
		SerialEncoder::class,
	];

	/**	@var	string|NULL				$encoder */
	protected ?string $encoder			= JsonEncoder::class;

	/**	@var	string		$resource */
	protected string $resource;

	/**
	 *	@param		string			$resource
	 *	@param		string|null		$context
	 *	@param		int|null		$expiration
	 *	@throws		SimpleCacheException
	 */
	public function __construct( $resource, ?string $context = NULL, ?int $expiration = NULL )
	{
		$this->resource	= $resource;
		if( NULL !== $context )
			$this->setContext( $context );
		if( NULL !== $expiration )
			$this->setExpiration( $expiration );

		if( !file_exists( $resource ) )
			touch( $resource );
		$this->read();
	}

	/**
	 *	Wipes clean the entire cache's keys.
	 *
	 *	@access		public
	 *	@return		bool		True on success and false on failure.
	 *	@throws		SimpleCacheException		if clearing data failed
	 */
	public function clear(): bool
	{
		$data	= [];
		if( NULL !== $this->context && '' !== $this->context ){
			$data	= $this->read();
			foreach( $this->index() as $key )
				unset( $data[$this->context.$key] );
		}
		/** @noinspection PhpUnhandledExceptionInspection */
		return $this->write( $data, FALSE );
	}

	/**
	 *	Delete an item from the cache by its unique key.
	 *
	 *	@access		public
	 *	@param		string		$key		The unique cache key of the item to delete.
	 *	@return		boolean		True if the item was successfully removed. False if there was an error.
	 *	@throws		SimpleCacheInvalidArgumentException	if the $key string is not a legal value
	 *	@throws		SimpleCacheException				if writing data failed
	 */
	public function delete( string $key ): bool
	{
		$this->checkKey( $key );
		$data	= $this->read();
		if( !array_key_exists( $this->context.$key, $data ) )
			return FALSE;
		unset( $data[$this->context.$key] );
		return $this->write( $data );
	}

	/**
	 *	Not implemented, yet.
	 *	Originally: Deletes multiple cache items in a single operation.
	 *
	 *	@param		iterable	$keys		A list of string-based keys to be deleted.
	 *	@return		boolean		True if the items were successfully removed. False if there was an error.
	 *	@throws		SimpleCacheInvalidArgumentException	if any of the $keys are not a legal value
	 *	@throws		SimpleCacheException				if writing data failed
	 */
	public function deleteMultiple( iterable $keys ): bool
	{
		foreach( $keys as $key )
			$this->checkKey( $key );
		$data	= $this->read();
		foreach( $keys as $key )
			if( array_key_exists( $this->context.$key, $data ) )
				unset( $data[$this->context.$key] );
		$this->write( $data );
		return TRUE;
 	}

	/**
	 *	Deprecated alias of clear.
	 *	@access		public
	 *	@return		static
	 *	@deprecated	use clear instead
	 *	@codeCoverageIgnore
	 *	@noinspection	PhpUnhandledExceptionInspection
	 *	@noinspection	PhpDocMissingThrowsInspection
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
	 *	@throws		SimpleCacheInvalidArgumentException	if the $key string is not a legal value
	 *	@throws		SimpleCacheException				if reading data failed.
	 */
	public function get( string $key, mixed $default = NULL ): mixed
	{
		$this->checkKey( $key );
		$data	= $this->read();
		if( array_key_exists( $this->context.$key, $data ) )
			return $data[$this->context.$key];
		return $default;
	}

	/**
	 *	Not implemented, yet.
	 *	Originally: Obtains multiple cache items by their unique keys.
	 *
	 *	@param		iterable	$keys		A list of keys that can obtained in a single operation.
	 *	@param		mixed		$default	Default value to return for keys that do not exist.
	 *	@return		array<string,mixed>		A list of key => value pairs. Cache keys that do not exist or are stale will have $default as value.
	 *	@throws		SimpleCacheInvalidArgumentException	if any of the $keys are not a legal value
	 *	@throws		SimpleCacheException				if reading data
	 */
	public function getMultiple( iterable $keys, mixed $default = NULL ): array
	{
		foreach( $keys as $key )
			$this->checkKey( $key );

		$data	= $this->read();
		$list	= [];
		/** @var string $key */
		foreach( $keys as $key )
			if( array_key_exists( $this->context.$key, $data ) )
				$list[$key]	= $data[$this->context.$key];
		return $list;
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
	 *	@throws		SimpleCacheException				if reading data failed
	 */
	public function has( string $key ): bool
	{
		$this->checkKey( $key );
		return array_key_exists( $this->context.$key, $this->read() );
	}

	/**
	 *	Returns a list of all data pair keys.
	 *	@access		public
	 *	@return		array
	 *	@throws		SimpleCacheException				if reading data failed
	 */
	public function index(): array
	{
		$keys	= array_keys( $this->read() );
		if( '' === ( $this->context ?? '' ) )
			return $keys;
		return array_values( array_map( function( string $key ): string{
			return substr( $key, strlen( $this->context ?? '' ) );
		}, array_filter( $keys, function( string $key ): bool{
			return str_starts_with( $key, $this->context ?? '' );
		} ) ) );
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
	 *	@throws		SimpleCacheInvalidArgumentException	if the $key string is not a legal value
	 *	@throws		SimpleCacheException				if writing data failed
	 */
	public function set( string $key, mixed $value, DateInterval|int $ttl = NULL ): bool
	{
		$this->checkKey( $key );
		$data	= $this->read();
		$data[$this->context.$key]	= $value;
		return $this->write( $data );
	}

	/**
	 *	Not implemented, yet.
	 *	Originally: Persists a set of key => value pairs in the cache, with an optional TTL.
	 *
	 *	@param		iterable				$values		A list of key => value pairs for a multiple-set operation.
	 *	@param		null|int|DateInterval	$ttl		Optional. The TTL value of this item. If no value is sent and
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

	/**
	 *	@return		array
	 *	@throws		SimpleCacheException		if reading data failed
	 */
	protected function read(): array
	{
		try{
			$content	= FileReader::load( $this->resource );
		}
		catch( Throwable $t ){
			throw new SimpleCacheException( 'Reading data failed: '.$t->getMessage(), 0, $t );
		}
		$data	= [];
		$list	= trim( $content ?? '' );
		if( '' !== $list ){
			$lines	= preg_split( "/\r?\n/", $list );
			if( FALSE !== $lines ){
				foreach( $lines as $line ){
					$parts	= preg_split( '/\s*=\s*/', $line, 2, PREG_SPLIT_NO_EMPTY );
					if( FALSE !== $parts )
						$data[trim( $parts[0] )]	= $this->decodeValue( trim( $parts[1] ) );
				}
			}
		}
		return $data;
	}

	/**
	 *	@param		array		$data
	 *	@param		bool		$strict
	 *	@return		bool
	 *	@throws		SimpleCacheInvalidArgumentException	if the $key string is not a legal value
	 *	@throws		SimpleCacheException				if writing data failed
	 */
	protected function write( array $data, bool $strict = TRUE ): bool
	{
		$list	= [];
		foreach( $data as $dataKey => $dataValue )
			$list[]	= $dataKey.'='.$this->encodeValue( $dataValue );
		$content	= join( "\n", $list );
		try{
			FileWriter::save( $this->resource, $content );
		}
		catch( Throwable $t ){
			if( $strict )
				throw new SimpleCacheException( 'Writing data failed: '.$t->getMessage(), 0, $t );
			return FALSE;
		}
		return TRUE;
	}
}
