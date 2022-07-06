<?php
declare(strict_types=1);

/**
 *	Storage implementation using a database table via a PDO connection.
 *	Supports context. Does not support expiration, yet.
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
use PDO;
use RuntimeException;

/**
 *	Storage implementation using a database table via a PDO connection.
 *	Supports context. Does not support expiration, yet.
 *	@category		Library
 *	@package		CeusMedia_Cache_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@todo			implement expiration and cleanup
 */
class Database extends AbstractAdapter implements SimpleCacheInterface
{
	/** @var		string		$tableName		... */
	protected $tableName		= 'cache';

	/**	@var	PDO				$resource		PDO database connection */
	protected $resource;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		array			$resource		List of PDO database connection and table name
	 *	@param		string|NULL		$context		Name of context in table
	 *	@param		integer|NULL	$expiration		Number of seconds until data sets expire
	 *	@return		void
	 */
	public function __construct( $resource, ?string $context = NULL, ?int $expiration = NULL )
	{
		$this->resource		= $resource[0];
		$this->tableName	= $resource[1];
		if( !( $this->resource instanceof PDO ) )
			throw new InvalidArgumentException( 'No PDO database connection set' );
		if( !$this->tableName )
			throw new InvalidArgumentException( 'No table name set' );
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
		$query	= 'DELETE FROM '.$this->tableName.' WHERE context="'.$this->context.'"';
		$this->resource->exec( $query );
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
		$query	= 'DELETE FROM '.$this->tableName.' WHERE context="'.$this->context.'" AND hash="'.$key.'"';
		return (bool) $this->resource->exec( $query );
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
		$query	= 'SELECT value FROM '.$this->tableName.' WHERE context="'.$this->context.'" AND hash="'.$key.'"';
		$result	= $this->resource->query( $query );
		if( $result === FALSE )																		//  query was not successful
			throw new RuntimeException( 'Table "'.$this->tableName.'" not found or invalid' );		//  inform about invalid table
		$result	= $result->fetch( PDO::FETCH_OBJ );													//  fetch row object
		if( $result === FALSE )																		//  no row found
			return NULL;																			//  quit with empty result
		return $result->value;																		//  return value
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
		$query	= 'SELECT COUNT(value) as count FROM '.$this->tableName.' WHERE context="'.$this->context.'" AND hash="'.$key.'"';
		$result	= $this->resource->query( $query );
		if( $result === FALSE )																		//  query was not successful
			throw new RuntimeException( 'Table "'.$this->tableName.'" not found or invalid' );		//  inform about invalid table
		return (bool) $result->fetch( PDO::FETCH_OBJ )->count;
	}

	/**
	 *	Returns a list of all data pair keys.
	 *	@access		public
	 *	@return		array
	 */
	public function index(): array
	{
		$query	= 'SELECT hash FROM '.$this->tableName.' WHERE context="'.$this->context.'"';
		$result	= $this->resource->query( $query );
		if( $result === FALSE )																		//  query was not successful
			throw new RuntimeException( 'Table "'.$this->tableName.'" not found or invalid' );		//  inform about invalid table
		$list	= array();
		$rows	= $result->fetchAll( PDO::FETCH_OBJ );
		if( $rows !== FALSE )
			foreach( $rows as $row )
				$list[]	= $row->hash;
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
	 */
	public function set( $key, $value, $ttl = NULL )
	{
		if( is_object( $value ) || is_resource( $value ) )
			throw new InvalidArgumentException( 'Value must not be an object or resource' );
		if( $value === NULL || $value === '' )
			return $this->remove( $key );

		$ttl	= NULL !== $ttl ? $ttl : $this->expiration;
		if( 0 === $ttl )
			throw new InvalidArgumentException( 'TTL must be given on this adapter' );
		if( is_int( $ttl ) )
			$ttl	= new DateInterval( $ttl.'s' );
		$expiresAt	= (new DateTime)->add( $ttl )->format( 'U' );

		if( $this->has( $key ) )
			$query	= 'UPDATE '.$this->tableName.' SET value="'.addslashes( $value ).'", timestamp="'.time().'", expiration='.(int) $expiresAt.' WHERE context="'.$this->context.'" AND hash="'.$key.'"';
		else
			$query	= 'INSERT INTO '.$this->tableName.' (context, hash, value, timestamp, expiration) VALUES ("'.$this->context.'", "'.$key.'", "'.addslashes( $value ).'", "'.time().'", '.(int) $expiresAt.')';
		return (bool) $this->resource->exec( $query );
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
}
