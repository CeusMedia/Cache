<?php /** @noinspection PhpMultipleClassDeclarationsInspection */
/** @noinspection SqlNoDataSourceInspection */
/** @noinspection PhpComposerExtensionStubsInspection */
declare(strict_types=1);

/**
 *	Storage implementation using a database table via a PDO connection.
 *	Supports context. Does not support expiration, yet.
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
use CeusMedia\Common\Exception\Deprecation as DeprecationException;
use DateInterval;
use DateTime;
use InvalidArgumentException;
use PDO;
use PDOException;
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
	/**	@var	array					$enabledEncoders	List of allowed encoder classes */
	protected array $enabledEncoders	= [
		IgbinaryEncoder::class,
		JsonEncoder::class,
		MsgpackEncoder::class,
		SerialEncoder::class,
	];

	/**	@var	string|NULL				$encoder */
	protected ?string $encoder			= JsonEncoder::class;

	/** @var		string				$tableName		... */
	protected string $tableName			= 'cache';

	/**	@var	PDO						$resource		PDO database connection */
	protected PDO $resource;

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
		$dbc	= $resource[0] ?? NULL;
		if( !( $dbc instanceof PDO ) )
			throw new InvalidArgumentException( 'No PDO database connection set' );

		$this->resource		= $dbc;
		$this->tableName	= $resource[1] ?? '';
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
		if( NULL !== $this->context && '' !== $this->context ){
			/** @noinspection SqlResolve */
			$query		= sprintf( 'DELETE FROM %s WHERE context=:context', $this->tableName );
			return $this->resource->prepare( $query )->execute( ['context' => $this->context] );
		}
		/** @noinspection SqlResolve */
		$query	= sprintf( 'DELETE FROM %s', $this->tableName );
		return $this->resource->prepare( $query )->execute();
	}

	/**
	 *	Delete an item from the cache by its unique key.
	 *
	 *	@access		public
	 *	@param		string		$key		The unique cache key of the item to delete.
	 *	@return		boolean		True if the item was successfully removed. False if there was an error.
	 *	@throws		SimpleCacheInvalidArgumentException	if the $key string is not a legal value.
	 */
	public function delete( string $key ): bool
	{
		$this->checkKey( $key );
		/** @noinspection SqlResolve */
		$query	= vsprintf( 'DELETE FROM %s WHERE context="%s" AND hash="%s"', [
			$this->tableName,
			$this->context,
			$key,
		] );
		return (bool) $this->resource->exec( $query );
	}

	/**
	 *	Not implemented, yet.
	 *	Originally: Deletes multiple cache items in a single operation.
	 *
	 *	@param		iterable	$keys		A list of string-based keys to be deleted.
	 *	@return		boolean		True if the items were successfully removed. False if there was an error.
	 *	@throws		SimpleCacheInvalidArgumentException	if the $key string is not a legal value.
	 */
	public function deleteMultiple( iterable $keys ): bool
	{
		foreach( $keys as $key )
			$this->checkKey( $key );
		$keyList	= array_map( static fn( string $key ) => '"'.$key.'"', (array) $keys );
		/** @noinspection SqlResolve */
		$query	= vsprintf( 'DELETE FROM %s WHERE context="%s" AND hash IN (%s)', [
			$this->tableName,
			$this->context ?? '',
			join( ',', $keyList ),
		] );
		return (bool) $this->resource->exec( $query );
	}

	/**
	 *	Deprecated alias of clear.
	 *	@access		public
	 *	@return		self
	 *	@deprecated	use clear instead
	 *	@codeCoverageIgnore
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
	 *	@throws		SimpleCacheInvalidArgumentException	if the $key string is not a legal value.
	 *	@throws		SimpleCacheException				if reading data failed
	 */
	public function get( string $key, mixed $default = NULL ): mixed
	{
		$this->checkKey( $key );
		/** @noinspection SqlResolve */
		$query		= 'SELECT value FROM '.$this->tableName.' WHERE context=:context AND hash=:hash LIMIT 0, 1';
		try{
			$statement	= $this->resource->prepare( $query );
			$result		= $statement->execute( [
				'context'	=> $this->context ?? '',
				'hash'		=> $key,
			] );
			$data	= $statement->fetch( PDO::FETCH_OBJ );										//  fetch row object
			if( FALSE !== $data )																		//  no row found
				return $this->decodeValue( $data->value );													//  return value
		}
		catch( PDOException ){
			throw new SimpleCacheException( 'Table "'.$this->tableName.'" not found or invalid' );		//  inform about invalid table
		}
		return $default;																		//  quit with empty result
	}

	/**
	 *	Obtains multiple cache items by their unique keys.
	 *
	 *	@param		iterable	$keys		A list of keys that can obtained in a single operation.
	 *	@param		mixed		$default	Default value to return for keys that do not exist.
	 *	@return		array<string,mixed>		A list of key => value pairs. Cache keys that do not exist or are stale will have $default as value.
	 *	@throws		SimpleCacheInvalidArgumentException	if $keys is neither an array nor a Traversable,
	 *													or if any of the $keys are not a legal value.
	 */
	public function getMultiple( iterable $keys, mixed $default = NULL ): array
	{
		foreach( $keys as $key )
			$this->checkKey( $key );
		$list	= [];
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
	 *	@throws		SimpleCacheInvalidArgumentException		if the $key string is not a legal value.
	 *	@throws		SimpleCacheException					if reading data failed
	 */
	public function has( string $key ): bool
	{
		$this->checkKey( $key );
		/** @noinspection SqlResolve */
		$query		= 'SELECT COUNT(value) as count FROM %s WHERE context=:context AND hash=:hash';
		try{
			$statement	= $this->resource->prepare( sprintf( $query, $this->tableName ) );
			$statement->execute( [
				'context'	=> $this->context ?? '',
				'hash'		=> $key,
			] );
			return (bool) $statement->fetch( PDO::FETCH_OBJ )->count;
		}
		catch( PDOException ){
			throw new SimpleCacheException( 'Table "'.$this->tableName.'" not found or invalid' );		//  inform about invalid table
		}
	}

	/**
	 *	Returns a list of all data pair keys.
	 *	@access		public
	 *	@return		array
	 *	@throws		SimpleCacheException					if reading data failed
	 */
	public function index(): array
	{
		if( NULL !== $this->context && '' !== $this->context ){
			/** @noinspection SqlResolve */
			$query		= 'SELECT hash FROM %s WHERE context=:context';
			$parameters	= ['context' => $this->context];
		}
		else{
			/** @noinspection SqlResolve */
			$query		= 'SELECT hash FROM %s';
			$parameters	= [];
		}
		try{
			$list		= [];
			$statement	= $this->resource->prepare( sprintf( $query, $this->tableName ) );
			$statement->execute( $parameters );
			foreach( $statement->fetchAll( PDO::FETCH_OBJ ) as $row )
				$list[]	= $row->hash;
			return $list;
		}
		catch( PDOException ){
			throw new SimpleCacheException( 'Table "'.$this->tableName.'" not found or invalid' );		//  inform about invalid table
		}
	}

	/**
	 *	Deprecated alias of delete.
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
	 *	@throws		SimpleCacheException				if writing data failed
	 */
	public function set( string $key, mixed $value, DateInterval|int $ttl = NULL ): bool
	{
		$this->checkKey( $key );
		if( is_resource( $value ) )
			throw new InvalidArgumentException( 'Value must not be a resource' );

		$ttl	= NULL !== $ttl ? $ttl : $this->expiration;
		if( 0 === $ttl )
			throw new InvalidArgumentException( 'TTL must be given on this adapter' );
		if( is_int( $ttl ) )
			$ttl	= new DateInterval( 'PT'.$ttl.'S' );
		$expiresAt	= (int) (new DateTime)->add( $ttl )->format( 'U' );

		if( $this->has( $key ) ){
			$query	= vsprintf( 'UPDATE %s SET %s WHERE %s', [
				$this->tableName,
				join( ', ', [
					'value=:value',
					'timestamp=:timestamp',
					'expiration=:expiration',
				] ),
				join( ' AND ', [
					'context=:context',
					'hash=:hash',
				] ),
			] );
		}
		else{
			$query	= vsprintf( 'INSERT INTO %s (%s) VALUES (%s)', [
				$this->tableName,
				join( ', ', ['context', 'hash', 'value', 'timestamp', 'expiration'] ),
				join( ', ', [':context', ':hash', ':value', ':timestamp', ':expiration'] )
			] );
		}
		$statement	= $this->resource->prepare( $query );
		$statement->bindValue( 'context', $this->context ?? '' );
		$statement->bindValue( 'hash', $key );
		$statement->bindValue( 'value', $this->encodeValue( $value ) );
		$statement->bindValue( 'timestamp', time() );
		$statement->bindValue( 'expiration', $expiresAt );
		try{
			return $statement->execute();
		}
		catch( PDOException $e ){
			throw new SimpleCacheException( 'Writing data to database failed', 0, $e );
		}
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
	 *	@throws		SimpleCacheInvalidArgumentException		if any of the $values are not a legal value.
	 *	@throws		SimpleCacheException				if writing data failed
	 */
	public function setMultiple( iterable $values, DateInterval|int $ttl = NULL ): bool
	{
		foreach( $values as $key => $value )
			$this->checkKey( $key );
		foreach( $values as $key => $value )
			$this->set( $key, $value, $ttl );
		return TRUE;
	}
}
