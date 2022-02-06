<?php
/**
 *	....
 *	Supports context.
 *	@category		Library
 *	@package		CeusMedia_Cache_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 */
namespace CeusMedia\Cache\Adapter;

use CeusMedia\Cache\AbstractAdapter;
use CeusMedia\Cache\AdapterInterface;
use Exception;
use RuntimeException;
use FS_File_Editor as FileEditor;
use CeusMedia\Cache\Util\FileLock;

/**
 *	....
 *	Supports context.
 *	@category		Library
 *	@package		CeusMedia_Cache_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 */
class JsonFile extends AbstractAdapter implements AdapterInterface
{
	/**	@var	FileEditor		$file */
	protected $file;

	/**	@var	FileLock		$lock */
	protected $lock;

	/**	@var	string			$resource */
	protected $resource;

	public function __construct( $resource, ?string $context = NULL, ?int $expiration = NULL )
	{
		$this->resource	= $resource;
		if( !file_exists( $resource ) )
			file_put_contents( $resource, json_encode( array() ) );
		$this->file	= new FileEditor( $resource );
		$this->lock	= new FileLock( $resource.'.lock' );
		$this->setContext( $context ? $context : 'default' );
		if( $expiration !== NULL )
			$this->setExpiration( $expiration );
	}

	/**
	 *
	 *	@return		void
	 */
	public function cleanup()
	{
		$this->lock->lock();
		try{
			$changed	= FALSE;
			$contexts	= json_decode( $this->file->readString(), TRUE );
			foreach( $contexts as $context => $entries ){
				foreach( $entries as $key => $entry ){
					if( $this->isExpiredEntry( $entry ) ){
						unset( $contexts[$context][$key] );
						$changed	= TRUE;
					}
				}
			}
			if( $changed )
				file_put_contents( $this->resource, json_encode( $contexts ) );
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
		$entries	= json_decode( $this->file->readString(), TRUE );
		if( isset( $entries[$this->context] ) ){
			foreach( array_keys( $entries[$this->context] ) as $key ){
				unset( $entries[$this->context][$key] );
			}
		}
		file_put_contents( $this->resource, json_encode( $entries ) );
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
	public function delete( $key ): bool
	{
		$entries	= json_decode( $this->file->readString(), TRUE );
		if( !isset( $entries[$this->context][$key] ) )
			return FALSE;
		$this->lock->lock();
		try{
			unset( $entries[$this->context][$key] );
			$this->file->writeString( json_encode( $entries ) );
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
	 *														or if any of the $keys are not a legal value.
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
		return $this->clear();
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
	public function get( $key, $default = NULL )
	{
		$entries	= json_decode( $this->file->readString(), TRUE );
		if( !isset( $entries[$this->context][$key] ) )
			return NULL;
		$entry	= $entries[$this->context][$key];
		if( $this->isExpiredEntry( $entry ) ){
			$this->remove( $key );
			return NULL;
		}
		return unserialize( $entry['value'] );
	}

	/**
	 *	Not implemented, yet.
	 *	Originally: Obtains multiple cache items by their unique keys.
	 *
	 *	@param		iterable	$keys		A list of keys that can obtained in a single operation.
	 *	@param		mixed		$default	Default value to return for keys that do not exist.
	 *	@return		iterable	A list of key => value pairs. Cache keys that do not exist or are stale will have $default as value.
	 *	@throws		SimpleCacheInvalidArgumentException		if $keys is neither an array nor a Traversable,
	 *														or if any of the $keys are not a legal value.
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
	 *	@throws		SimpleCacheInvalidArgumentException		if the $key string is not a legal value.
	 */
	public function has( $key ): bool
	{
		$entries	= json_decode( $this->file->readString(), TRUE );
		if( !isset( $entries[$this->context][$key] ) )
			return FALSE;
		$entry	= $entries[$this->context][$key];
		if( $this->isExpiredEntry( $entry ) ){
			$this->remove( $key );
			return FALSE;
		}
		return TRUE;
	}

	/**
	 *	Returns a list of all data pair keys.
	 *	@access		public
	 *	@return		array
	 */
	public function index(): array
	{
		$entries	= json_decode( $this->file->readString(), TRUE );
		if( !isset( $entries[$this->context] ) )
			return array();
		if( $this->expiration ){
			$now	= time();
			foreach( $entries[$this->context] as $key => $entry ){
				if( $this->isExpiredEntry( $entry ) ){
					$this->remove( $key );
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
	 *	@throws		SimpleCacheInvalidArgumentException		if the $key string is not a legal value.
	 */
	public function set( $key, $value, $ttl = NULL )
	{
		$this->lock->lock();
		try{
			$expiration	= $expiration ? $expiration : $this->expiration;
			$entries	= json_decode( $this->file->readString(), TRUE );
			if( !isset( $entries[$this->context] ) )
				$entries[$this->context]	= array();
			$entries[$this->context][$key] = array(
				'value'		=> serialize( $value ),
				'timestamp'	=> time(),
				'expires'	=> time() + (int) $expiration,
	//			'tags'		=> $tags,
			);
			$this->file->writeString( json_encode( $entries ) );
			$this->lock->unlock();
			return TRUE;
		}
		catch( Exception $e ){
			$this->lock->unlock();
			throw new RuntimeException( 'Setting cache key failed: '.$e->getMessage() );
		}
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
	 *	@throws		SimpleCacheInvalidArgumentException		if $values is neither an array nor a Traversable,
	 *														or if any of the $values are not a legal value.
	 */
	public function setMultiple($values, $ttl = null)
	{
		return TRUE;
	}

	//  --  PROTECTED  --  //

	protected function isExpiredEntry( array $entry ): bool
	{
		if( $this->expiration ){
			$now	= time();
			$age	= (int) $entry['timestamp'] + $this->expiration;
			if( $age <= $now || $entry['expires'] <= $now )
				return TRUE;
		}
		return FALSE;
	}
}
