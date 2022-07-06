<?php
declare(strict_types=1);

/**
 *	Cache storage adapter for Redis.
 *	Supports context.
 *	@category		Library
 *	@package		CeusMedia_Cache_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
namespace CeusMedia\Cache\Adapter;

use CeusMedia\Cache\AbstractAdapter;
use CeusMedia\Cache\Encoder\Igbinary as IgbinaryEncoder;
use CeusMedia\Cache\Encoder\JSON as JsonEncoder;
use CeusMedia\Cache\Encoder\Msgpack as MsgpackEncoder;
use CeusMedia\Cache\Encoder\Serial as SerialEncoder;
use CeusMedia\Cache\SimpleCacheInterface;
use CeusMedia\Cache\SimpleCacheInvalidArgumentException as InvalidArgumentException;

use ADT_URL;

use DateInterval;
use DateTime;
use Redis as RedisClient;
use RuntimeException;

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
	/**	@var	array			$enabledEncoders	List of allowed encoder classes */
	protected $enabledEncoders	= [
		IgbinaryEncoder::class,
		JsonEncoder::class,
		MsgpackEncoder::class,
		SerialEncoder::class,
	];

	/**	@var	string|NULL		$encoder */
	protected $encoder			= JsonEncoder::class;

	/**	@var	RedisClient		$resource */
	protected $resource;

	/**	@var	string			$host */
	protected $host				= 'localhost';

	/**	@var	int				$port */
	protected $port				= 6379;

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

			$url	= new ADT_URL( $resource.'/' );
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
	 */
	public function clear(): bool
	{
		if( NULL === $this->context )
			$this->resource->flushAll();
		else
			$this->resource->flushDB();
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
	public function delete( $key ): bool
	{
		return 1 === $this->resource->unlink( $key );
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
	public function deleteMultiple( $keys ): bool
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
	public function get( $key, $default = NULL )
	{
		/** @var string|FALSE $data */
		$data	= $this->resource->get( $key );
		if( FALSE !== $data )
			return unserialize( $data );
		return $default;
	}

	/**
	 *	Not implemented, yet.
	 *	Originally: Obtains multiple cache items by their unique keys.
	 *
	 *	@param		iterable	$keys		A list of keys that can obtained in a single operation.
	 *	@param		mixed		$default	Default value to return for keys that do not exist.
	 *	@return		iterable	A list of key => value pairs. Cache keys that do not exist or are stale will have $default as value.
	 *	@throws		InvalidArgumentException		if $keys is neither an array nor a Traversable,
	 *												or if any of the $keys are not a legal value.
	 *	@todo		implement
	 */
	public function getMultiple( $keys, $default = NULL )
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
	public function has( $key ): bool
	{
		return $this->get( $key ) !== NULL;
	}

	/**
	 *	Returns a list of all data pair keys.
	 *	@access		public
	 *	@return		array
	 *	@todo		implement!
	 */
	public function index(): array
	{
		$it		= NULL;
		$list	= [];
		if( $this->resource->getOption( RedisClient::OPT_SCAN ) === RedisClient::SCAN_RETRY ){
			while( $keys = $this->resource->scan( $it ) )
				foreach( $keys as $key )
					$list[]	= $key;
		}
		else{
			do{
				if( ( $keys = $this->resource->scan( $it ) ) !== FALSE )
					foreach( $keys as $key )
						$list[]	= $key;
			}
			while( $it > 0 );

		}
		return $list;
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
	 *	@param		null|int|DateInterval	$ttl		Optional. The TTL value of this item. If no value is sent and
	 *													the driver supports TTL then the library may set a default value
	 *													for it or let the driver take care of that.
	 *	@return		boolean		True on success and false on failure.
	 *	@throws		InvalidArgumentException		if the $key string is not a legal value.
	 *	@see		... Expiration Times
	 */
	public function set( $key, $value, $ttl = NULL )
	{
		$ttl	= $ttl ?? $this->expiration;
		if( $ttl instanceof DateInterval )
			$ttl	= (int) (new DateTime)->add( $ttl )->format( 'U' );

		$serial	= serialize( $value );
		if( 0 !== $ttl)
			$result = $this->resource->setex( $key, $ttl, $serial );
		 else
			$result = $this->resource->set( $key, $serial );
		return $result;
	}

	/**
	 *	Sets context within storage.
	 *	@access		public
	 *	@param		string|NULL		$context		Context within storage
	 *	@return		self
	 *	@todo		remove inner delimiter
	 *	@todo		even better: use select(int database), but lacks string2int conversion
	 */
	public function setContext( ?string $context = NULL ): self
	{
		$db = $this->convertDatabaseNameFromStrimgToInteger( $context ?? '' );
		$this->resource->select( $db );
		$this->context = $context;
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
	 *	@throws		InvalidArgumentException		if $values is neither an array nor a Traversable,
	 *												or if any of the $values are not a legal value.
	 */
	public function setMultiple( $values, $ttl = NULL ): bool
	{
		return TRUE;
	}

	//  --  PROTECTED  --  //

	/**
	 *	This is work in progress.
	 *	@return		integer
	 */
	protected function convertDatabaseNameFromStrimgToInteger( string $database ): int
	{
		if( '' === $database )
			return 0;
		$length	= strlen( $database );
		$hash	= md5( $database );

		$list	= [];
		foreach( str_split($hash, 1) as $character )
			$list[]	= hexdec( $character );
		$vector1	= array_sum( $list );

		$list	= [];
		foreach( str_split($database, 1) as $character )
			$list[]	= intval( ord( $character ) );
		$vector2	= array_sum( $list );

		$product	= $length * $vector1 * $vector2;
		$key		= $product % pow(2, 16);
		return $key;
	}
}
