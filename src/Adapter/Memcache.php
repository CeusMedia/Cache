<?php
/**
 *	Cache storage adapter for memcache.
 *	Supports context.
 *	@category		Library
 *	@package		CeusMedia_Cache_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
namespace CeusMedia\Cache\Adapter;

use CeusMedia\Cache\AbstractAdapter;
use CeusMedia\Cache\SimpleCacheInterface;
use CeusMedia\Cache\SimpleCacheInvalidArgumentException as InvalidArgumentException;

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
	protected $resource;

	/**	@var	string			$host */
	protected $host				= 'localhost';

	/**	@var	int				$port */
	protected $port				= 11211;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		string			$resource		Memcache server hostname and port, eg. 'localhost:11211' (default)
	 *	@param		string|NULL		$context		Internal prefix for keys for separation
	 *	@param		integer|NULL	$expiration		Data life time in seconds or expiration timestamp
	 *	@return		void
	 */
	public function __construct( $resource = 'localhost:11211', ?string $context = NULL, ?int $expiration = NULL )
	{
		$parts	= explode( ":", trim( $resource ) );
		if( isset( $parts[0] ) && '' !== trim( $parts[0] ) )
			$this->host	= $parts[0];
		if( isset( $parts[1] ) && '' !== trim( $parts[1] ) )
			$this->port	= (int) $parts[1];
		$this->resource = new MemcacheClient;
		$this->resource->addServer( $this->host, $this->port );
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
			$this->resource->flush();
		else{
			foreach( $this->index() as $key )
				$this->remove( $key );
		}
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
		return $this->resource->delete( $this->context.$key, 0 );
	}

	/**
	 *	Not implemented, yet.
	 *	Originally: Deletes multiple cache items in a single operation.
	 *
	 *	@param		iterable	$keys		A list of string-based keys to be deleted.
	 *	@return		boolean		True if the items were successfully removed. False if there was an error.
	 *	@throws		InvalidArgumentException		if $keys is neither an array nor a Traversable,
	 *												or if any of the $keys are not a legal value.
	 */
	public function deleteMultiple( $keys )
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
		$data	= $this->resource->get( $this->context.$key );
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
	 */
	public function getMultiple($keys, $default = null)
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
	 */
	public function index(): array
	{
		$list	= array();
		$string	= $this->sendMemcacheCommand( "stats items" );
		$lines	= explode( "\r\n", $string );
		$slabs	= [];
		foreach( $lines as $line ){
			if( preg_match( "/STAT items:([\d]+):/", $line, $matches ) == 1 ){
				if( isset( $matches[1] ) ){
					if( !in_array( $matches[1], $slabs, TRUE ) ){
						$slabs[]	= $matches[1];
						$string		= $this->sendMemcacheCommand( "stats cachedump ".$matches[1]." 100" );
						preg_match_all( "/ITEM (.*?) /", $string, $matches );
						$list		= array_merge( $list, $matches[1] );
					}
				}
			}
		}
		if( NULL !== $this->context )
			foreach( $list as $nr => $item )
				if( substr( $item, 0, strlen( $this->context ) ) == $this->context )
					$list[$nr]	= substr( $list[$nr], strlen( $this->context ) );
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
	 *	@see		https://www.php.net/manual/en/memcached.expiration.php Expiration Times
	 */
	public function set( $key, $value, $ttl = NULL )
	{
		$ttl	= NULL !== $ttl ? $ttl : $this->expiration;
		if( is_int( $ttl ) )
			$ttl	= new DateInterval( $ttl.'s' );
		$expiresAt	= (new DateTime)->add( $ttl )->format( 'U' );

		return $this->resource->set( $this->context.$key, serialize( $value ), 0, (int) $expiresAt );
	}

	/**
	 *	Sets context within storage.
	 *	@access		public
	 *	@param		string|NULL		$context		Context within storage
	 *	@return		self
	 *	@todo		remove inner delimiter
	 */
	public function setContext( ?string $context = NULL ): self
	{
		if( NULL !== $context && 0 !== strlen( trim( $context ) ) )
			$context	.= ':';
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
	public function setMultiple($values, $ttl = null)
	{
		return TRUE;
	}

	/**
	 *	Sends command to memache daemon using a socket connection.
	 *	Taken directly from memcache PECL source
	 *	@access		protected
	 *	@param		string		$command		Memcache command to send directly
	 *	@return		string
	 *	@see		http://pecl.php.net/package/memcache
	 */
	protected function sendMemcacheCommand( string $command ): string
	{
		$socket = @fsockopen( $this->host, $this->port );
		if( FALSE === $socket )
			die( "Cant connect to:".$this->host.':'.$this->port );
		fwrite( $socket, $command."\r\n" );
		$buffer	= '';
		while( ( !feof( $socket ) ) ){
			$buffer .= fgets( $socket, 256 );
			if( FALSE !== preg_match( '/(END|DELETED|NOT_FOUND|OK)\r\n/s', $buffer ) )
				break;
		}
		fclose( $socket );
		return $buffer;
	}
}
