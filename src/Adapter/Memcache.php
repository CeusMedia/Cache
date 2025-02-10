<?php /** @noinspection PhpComposerExtensionStubsInspection */
/** @noinspection PhpMultipleClassDeclarationsInspection */
declare(strict_types=1);

/**
 *	Cache storage adapter for memcache.
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
use Memcache as MemcacheClient;

/**
 *	Cache storage adapter for memcache.
 *	Supports context.
 *	@category		Library
 *	@package		CeusMedia_Cache_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
class Memcache extends AbstractAdapter implements SimpleCacheInterface
{
	/**	@var	MemcacheClient	$resource */
	protected MemcacheClient $resource;

	/**	@var	string					$host */
	protected string $host				= 'localhost';

	/**	@var	int						$port */
	protected int $port					= 11211;

	/**	@var	array					$enabledEncoders	List of allowed encoder classes */
	protected array $enabledEncoders	= [
		IgbinaryEncoder::class,
		JsonEncoder::class,
		MsgpackEncoder::class,
		SerialEncoder::class,
	];

	/**	@var	string|NULL				$encoder */
	protected ?string $encoder			= JsonEncoder::class;

	protected array $keys				= [];
	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		string			$resource		Memcache server hostname and port, eg. 'localhost:11211' (default)
	 *	@param		string|NULL		$context		Internal prefix for keys for separation
	 *	@param		integer|NULL	$expiration		Data lifetime in seconds or expiration timestamp
	 *	@return		void
	 *	@throws		SupportException				if memcache is not supported, PHP module not installed
	 *	@throws		SimpleCacheException			if reading or writing data failed
	 */
	public function __construct( $resource = NULL, ?string $context = NULL, ?int $expiration = NULL )
	{
		if( !class_exists( MemcacheClient::class ) )
			throw new SupportException( 'No memcache support found' );

		$resource = $resource ?? 'localhost:11211';
		if( 0 === preg_match( '#^[a-z0-9]+://#i', $resource ) )
			$resource	= 'schema://'.$resource;

		$url	= new URL( $resource.'/' );
		if( '' !== $url->getHost() )
			$this->host = $url->getHost();

		if( 0 !== (int) $url->getPort() )
			$this->port = (int) $url->getPort();

		$this->resource = new MemcacheClient;
		$this->resource->addServer( $this->host, $this->port );
		if( $context !== NULL )
			$this->setContext( $context );
		if( $expiration !== NULL )
			$this->setExpiration( $expiration );
		$this->keys[$this->context ?? '']	= $this->loadKeys();
	}

	/**
	 *	Wipes clean the entire cache's keys.
	 *
	 *	@access		public
	 *	@return		bool		True on success and false on failure.
	 *	@throws		SimpleCacheException	if reading or writing data failed
	 */
	public function clear(): bool
	{
		if( NULL === $this->context || '' === $this->context )
			$this->resource->flush();
		else{
			foreach( $this->keys[$this->context] as $key )
//			foreach( $this->index() as $key )
				/** @noinspection PhpUnhandledExceptionInspection */
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
	 *	@throws		SimpleCacheInvalidArgumentException	if the $key string is not a legal value.
	 *	@throws		SimpleCacheException				if deleting data failed
	 */
	public function delete( string $key ): bool
	{
		$this->checkKey( $key );
		if( !$this->has( $key ) )
			return FALSE;
		$context	= $this->context ?? '';
		$this->keys[$context]	= array_values( array_diff( $this->keys[$context], [$key] ) );
		return $this->resource->delete( $context.$key );
	}

	/**
	 *	Deletes multiple cache items in a single operation.
	 *
	 *	@param		iterable	$keys		A list of string-based keys to be deleted.
	 *	@return		boolean		True if the items were successfully removed. False if there was an error.
	 *	@throws		SimpleCacheInvalidArgumentException	if any of the $keys are not a legal value.
	 *	@throws		SimpleCacheException				if deleting data failed
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
	 *	@throws		SimpleCacheException	if deleting data failed
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
	 *	@throws		SimpleCacheInvalidArgumentException	if the $key string is not a legal value.
	 */
	public function get( string $key, mixed $default = NULL ): mixed
	{
		if( !$this->has( $key ) )
			return $default;
		/** @var string|FALSE $data */
		$data	= $this->resource->get( $this->context.$key );
		if( FALSE !== $data )
			return $this->decodeValue( $data );
		return $default;
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
	 *	@throws		SimpleCacheInvalidArgumentException	if the $key string is not a legal value.
	 */
	public function has( string $key ): bool
	{
		$this->checkKey( $key );
		return in_array( $key, $this->keys[$this->context ?? ''], TRUE );
//		return $this->get( $key ) !== NULL;
	}

	public function index(): array
	{
		return array_values( $this->keys[$this->context ?? ''] );
	}
	/**
	 *	Returns a list of all data pair keys.
	 *	@access		public
	 *	@return		array
	 *	@throws		SimpleCacheException		if reading or writing data failed
	 */
	protected function loadKeys(): array
	{
		$list	= [];
		$string	= $this->sendMemcacheCommand( "stats items", TRUE );
		$slabs	= [];
		foreach( explode( "\r\n", $string ) as $line ){
			if( '' === trim( $line ) )
				continue;
			$match	= preg_match( "/^STAT items:(\d+):number (\d+)$/", trim( $line ), $matches );
			if( FALSE === $match || !isset( $matches[1] ) )
				continue;
			if( !in_array( $matches[1], $slabs, TRUE ) ){
				$slabs[]	= $matches[1];
				$string		= $this->sendMemcacheCommand( "stats cachedump ".$matches[1]." 100" );
				preg_match_all( "/ITEM (.*?) /", $string, $matches );
				$list		= array_merge( $list, $matches[1] );
			}
		}
		if( NULL !== $this->context && '' !== $this->context )
			foreach( $list as $nr => $item )
				if( str_starts_with( $item, $this->context ) )
					$list[$nr]	= substr( $item, strlen( $this->context ) );
				else
					unset( $list[$nr] );

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
	 *	@throws		SimpleCacheInvalidArgumentException	if the $key string is not a legal value.
	 *	@see		https://www.php.net/manual/en/memcached.expiration.php Expiration Times
	 */
	public function set( string $key, mixed $value, DateInterval|int $ttl = NULL ): bool
	{
		$this->checkKey( $key );
		$ttl		= NULL !== $ttl ? $ttl : $this->expiration;
		$interval	= is_int( $ttl ) ? new DateInterval( 'PT'.$ttl.'S' ) : $ttl;
		$expiresAt	= (new DateTime)->add( $interval )->format( 'U' );

		$context	= $this->context ?? '';
		$this->keys[$context][]	= $key;
		$this->keys[$context]	= array_unique( $this->keys[$context] );
		return $this->resource->set( $context.$key, $this->encodeValue( $value ), 0, (int) $expiresAt );
	}

	/**
	 *	Sets context within storage.
	 *	@access		public
	 *	@param		string|NULL		$context		Context within storage
	 *	@return		static
	 *	@todo		remove inner delimiter
	 *	@throws		SimpleCacheException			if reading or writing data failed
	 */
	public function setContext( ?string $context = NULL ): static
	{
		if( NULL !== $context && 0 !== strlen( trim( $context ) ) )
			$context	.= ':';
		$this->context	= $context;
		if( !array_key_exists( $context ?? '', $this->keys ) )
			$this->keys[$context ?? '']		= $this->loadKeys();
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
		return parent::setMultiple( $values, $ttl );
	}

	/**
	 *	Sends command to memache daemon using a socket connection.
	 *	Taken directly from memcache PECL source
	 *	@access		protected
	 *	@param		string		$command			Memcache command to send directly
	 *	@param		bool		$firstLineOnly		Whether to return only the first line of the response
	 *	@return		string
	 *	@see		http://pecl.php.net/package/memcache
	 *	@throws		SimpleCacheException			if reading or writing data failed
	 */
	protected function sendMemcacheCommand( string $command, bool $firstLineOnly = FALSE ): string
	{
		$socket = @fsockopen( $this->host, $this->port );
		if( FALSE === $socket )
			throw new SimpleCacheException( 'Can\'t connect to: '.$this->host.':'.$this->port );
		fwrite( $socket, $command."\r\n" );
		$buffer	= '';
		while( ( !feof( $socket ) ) ){
			$buffer .= fgets( $socket, 256 );
			if( $firstLineOnly || 0 !== preg_match( '/(END|DELETED|NOT_FOUND|OK)\r\n/', $buffer ) )
				break;
		}
		fclose( $socket );
		return $buffer;
	}
}
