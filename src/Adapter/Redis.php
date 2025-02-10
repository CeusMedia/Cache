<?php /** @noinspection PhpComposerExtensionStubsInspection */
/** @noinspection PhpMultipleClassDeclarationsInspection */
declare(strict_types=1);

/**
 *	Cache storage adapter for Redis.
 *	Supports context.
 *	@category		Library
 *	@package		CeusMedia_Cache_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
namespace CeusMedia\Cache\Adapter;

use CeusMedia\Cache\Encoder\Igbinary as IgbinaryEncoder;
use CeusMedia\Cache\Encoder\JSON as JsonEncoder;
use CeusMedia\Cache\Encoder\Msgpack as MsgpackEncoder;
use CeusMedia\Cache\Encoder\Serial as SerialEncoder;
use CeusMedia\Cache\SimpleCacheException;
use CeusMedia\Cache\SimpleCacheInterface;
use CeusMedia\Cache\SimpleCacheInvalidArgumentException;
use CeusMedia\Common\ADT\URL;
use CeusMedia\Common\Exception\Deprecation as DeprecationException;
use DateInterval;
use DateTime;
use InvalidArgumentException;
use Redis as RedisClient;
use RedisException;

/**
 *	Cache storage adapter for Redis.
 *	Supports context.
 *	@category		Library
 *	@package		CeusMedia_Cache_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@todo			support databases, see ::set
 */
class Redis extends AbstractAdapter implements SimpleCacheInterface
{
	/**	@var	array					$enabledEncoders	List of allowed encoder classes */
	protected array $enabledEncoders	= [
		IgbinaryEncoder::class,
		JsonEncoder::class,
		MsgpackEncoder::class,
		SerialEncoder::class,
	];

	/**	@var	string|NULL				$encoder */
	protected ?string $encoder			= JsonEncoder::class;

	/**	@var	RedisClient				$resource */
	protected RedisClient $resource;

	/**	@var	string					$host */
	protected string $host				= 'localhost';

	/**	@var	int						$port */
	protected int $port					= 6379;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		string			$resource		Redis server hostname and port, eg. 'localhost:6379' (default)
	 *	@param		string|NULL		$context		Internal prefix for keys for separation
	 *	@param		integer|NULL	$expiration		Data life time in seconds or expiration timestamp
	 *	@return		void
	 *	@throws		SupportException				if Redis is not supported, PHP module not installed
	 */
	public function __construct( $resource = NULL, ?string $context = NULL, ?int $expiration = NULL )
	{
		if( !class_exists( RedisClient::class ) )
			throw new SupportException( 'No Redis support found' );

		$this->resource = new RedisClient();
		$resource = $resource ?? 'localhost:6379';
		if( 1 === preg_match('#^/#', $resource ) ){								//  absolute socket file
			$this->host	= $resource;
			$this->port	= 0;
			$this->resource->connect( $this->host );
		}
		else {
			if( 0 === preg_match( '#^[a-z0-9]+://#i', $resource ) )
				$resource	= 'schema://'.$resource;

			$url	= new URL( $resource.'/' );
			if( '' !== $url->getHost() ){
				$scheme	= $url->getScheme() !== 'schema' ? $url->getScheme().'://' : '';
				$this->host = $scheme.$url->getHost();
			}

			if( 0 !== (int) $url->getPort() )
				$this->port = (int) $url->getPort();

			$this->resource->connect( $this->host, $this->port );
			if( '' !== $url->getPassword() ){
				$credentials	= ['pass' => $url->getPassword()];
				if( '' !== $url->getUsername() )
					$credentials['user'] = $url->getUsername();
				$this->resource->auth( $credentials );
			}
		}

		//  Enable Redis::scan()
//		$redis->setOption( RedisClient::OPT_SCAN, RedisClient::SCAN_RETRY );


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
	 *	@throws		SimpleCacheException		if deleting data failed
	 */
	public function clear(): bool
	{
		if( NULL === $this->context )
			try{
//				$this->resource->flushAll();
				$this->resource->flushDB();
			}
			catch( RedisException $e ){
				throw new SimpleCacheException( 'Failed to delete data: '.$e->getMessage(), 0, $e );
			}
		else{
			foreach( $this->index() as $key )
				$this->delete( $key );
		}
		return TRUE;
	}

	/**
	 *	Delete an item from the cache by its unique key.
	 *
	 *	@access		public
	 *	@param		string		$key		The unique cache key of the item to delete.
	 *	@return		boolean		True if the item was successfully removed. False if there was an error.
	 *	@throws		SimpleCacheInvalidArgumentException	if the $key string is not a legal value
	 *	@throws		SimpleCacheException				if deleting data failed
	 */
	public function delete( string $key ): bool
	{
		$this->checkKey( $key );
		try{
			return 1 === $this->resource->unlink( $this->context.$key );
		}
		catch( RedisException $e ){
			throw new SimpleCacheException( 'Failed to delete data: '.$e->getMessage(), 0, $e );
		}
	}

	/**
	 *	Not implemented, yet.
	 *	Originally: Deletes multiple cache items in a single operation.
	 *
	 *	@param		iterable	$keys		A list of string-based keys to be deleted.
	 *	@return		boolean		True if the items were successfully removed. False if there was an error.
	 *	@throws		SimpleCacheInvalidArgumentException	if any of the $keys are not a legal value
	 *	@throws		SimpleCacheException				if deleting data failed
	 */
	public function deleteMultiple( iterable $keys ): bool
	{
		foreach( $keys as $key )
			$this->checkKey( $key );
		/** @var string $key */
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
	 *	@throws		SimpleCacheException		if deleting data failed
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
	 *	@throws		SimpleCacheException				if reading data failed
	 */
	public function get( string $key, mixed $default = NULL ): mixed
	{
		$this->checkKey( $key );
		try{
			/** @var string|FALSE $data */
			$data	= $this->resource->get( $this->context.$key );
			if( FALSE !== $data )
				return $this->decodeValue( $data );
			return $default;
		}
		catch( RedisException $e ){
			throw new SimpleCacheException( 'Failed to read data: '.$e->getMessage(), 0, $e );
		}
	}

	/**
	 *	Not implemented, yet.
	 *	Originally: Obtains multiple cache items by their unique keys.
	 *
	 *	@param		iterable	$keys		A list of keys that can obtained in a single operation.
	 *	@param		mixed		$default	Default value to return for keys that do not exist.
	 *	@return		array<string,mixed>		A list of key => value pairs. Cache keys that do not exist or are stale will have $default as value.
	 *	@throws		SimpleCacheInvalidArgumentException		if any of the $keys are not a legal value
	 *	@todo		implement mget
	 */
	public function getMultiple( iterable $keys, mixed $default = NULL ): array
	{
		$list	= [];
		foreach( $keys as $key )
			$this->checkKey( $key );
		/** @var string $key */
		foreach( $keys as $key )
			$list[$key]	= $this->get( $key );
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
		return $this->get( $key ) !== NULL;
	}

	/**
	 *	Returns a list of all data pair keys.
	 *	@access		public
	 *	@return		array
	 */
	public function index(): array
	{
		$it		= NULL;
		$list	= [];
		if( RedisClient::SCAN_RETRY === $this->resource->getOption( RedisClient::OPT_SCAN ) ){
			while( $keys = $this->resource->scan( $it ) )
				foreach( $keys as $key )
					$list[]	= $key;
		}
		else{
			do{
				$keys = $this->resource->scan( $it );
				if( FALSE !== $keys )
					foreach( $keys as $key )
						$list[]	= $key;
			}
			while( $it > 0 );
		}

		if( NULL !== $this->context ){
			$length	= strlen( $this->context );
			foreach( $list as $nr => $key ){
				if( !str_starts_with( $key, $this->context ) ){
					unset( $list[$nr] );
					continue;
				}
				$list[$nr]	= substr( $key, $length );
			}
		}
		sort( $list );
		/** @phpstan-ignore-next-line */
		return array_values( $list );
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
	 *	@see		... Expiration Times
	 */
	public function set( string $key, mixed $value, DateInterval|int $ttl = NULL ): bool
	{
		$this->checkKey( $key );
		$ttl	= $ttl ?? $this->expiration;
		if( $ttl instanceof DateInterval )
			$ttl	= (int) (new DateTime)->add( $ttl )->format( 'U' );

		$serial	= $this->encodeValue( $value );
		try{
			if( 0 !== $ttl)
				$result = $this->resource->setex( $this->context.$key, $ttl, $serial );
			else
				$result = $this->resource->set( $this->context.$key, $serial );
		}
		catch( RedisException $e ){
			throw new SimpleCacheException( 'Failed to write data: '.$e->getMessage(), 0, $e );
		}
		return $result;
	}

	/**
	 *	Sets context within storage.
	 *	@access		public
	 *	@param		string|NULL		$context		Context within storage
	 *	@return		static
	 *	@todo		remove inner delimiter
	 *	@todo		even better: use select(int database), but lacks string2int conversion
	 *	@throws		InvalidArgumentException		if given context is invalid
	 */
	public function setContext( ?string $context = NULL ): static
	{
		if( NULL === $context || '' === $context )
			$context	= '';
		if( 0 === preg_match( '/@\d+$/', $context ) )
			$context	.= '@0';

		if( 1 === preg_match( '/^(.+)?@(\d+)$/', $context, $matches ) ){
			$this->context	= $matches[1];
			$this->resource->select( (int) $matches[2] );
		} else {
			throw new InvalidArgumentException( 'Invalid context' );
		}

//		$db = $this->convertDatabaseNameFromStringToInteger( $context ?? '' );
//		$this->resource->select( $db );
//		$this->context = $context;
		return $this;
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
	 *	@throws		SimpleCacheInvalidArgumentException		if any of the $values are not a legal value
	 *	@throws		SimpleCacheException					if writing data failed
	 */
	public function setMultiple( iterable $values, mixed $ttl = NULL ): bool
	{
//		parent::setMultiple( $values, $ttl );
		foreach( $values as $key => $value )
			$this->checkKey( $key );
		/** @var string $key */
/*		foreach( $values as $key => $value )
			$this->set( $key, $value );
		return TRUE;*/
		$ttl	= $ttl ?? $this->expiration;
		if( $ttl instanceof DateInterval )
			$ttl	= (int) (new DateTime)->add( $ttl )->format( 'U' );

		if( 0 !== $ttl){
			foreach( $values as $key => $value )
				$this->set( $key, $value );
			return TRUE;
		}
		$list	= [];
		foreach( $values as $key => $value )
			$list[$this->context.$key]	= $this->encodeValue( $value );
		try{
			return $this->resource->mset( $list );
		}
		catch( RedisException $e ){
			throw new SimpleCacheException( 'Failed to write data: '.$e->getMessage(), 0, $e );
		}
	}

	//  --  PROTECTED  --  //

	/**
	 *	This is work in progress.
	 *	@return		integer
	 *	@codeCoverageIgnore
	 */
	protected function convertDatabaseNameFromStringToInteger(string $database ): int
	{
		if( '' === $database )
			return 0;
		$length	= strlen( $database );
		$hash	= md5( $database );

		$list	= [];
		foreach( str_split( $hash ) as $character )
			$list[]	= hexdec( $character );
		$vector1	= array_sum( $list );

		$list	= [];
		foreach( str_split( $database ) as $character )
			$list[]	= intval( ord( $character ) );
		$vector2	= array_sum( $list );

		$product	= $length * $vector1 * $vector2;
		return $product % pow(2, 16);
	}
}
