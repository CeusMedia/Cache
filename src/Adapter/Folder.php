<?php
/**
 *	....
 *	Supports context.
 *	@category		Library
 *	@package		CeusMedia_Cache_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
namespace CeusMedia\Cache\Adapter;

use CeusMedia\Cache\AbstractAdapter;
use CeusMedia\Cache\SimpleCacheInterface;
use CeusMedia\Cache\SimpleCacheInvalidArgumentException as InvalidArgumentException;

use FS_File_Editor as FileEditor;
use FS_Folder_Editor as FolderEditor;
use FS_Folder_RecursiveIterator as RecursiveFolderIterator;

use DateInterval;
use DirectoryIterator;

/**
 *	....
 *	Supports context.
 *	@category		Library
 *	@package		CeusMedia_Cache_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
class Folder extends AbstractAdapter implements SimpleCacheInterface
{
	/**	@var		string		$path			Path to Cache Files */
	protected $path;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		string			$resource		Path name of folder for cache files, eg. 'cache/'
	 *	@param		string|NULL		$context		Internal prefix for keys for separation
	 *	@param		integer|NULL	$expiration		Data life time in seconds or expiration timestamp
	 *	@return		void
	 */
	public function __construct( $resource, ?string $context = NULL, ?int $expiration = NULL )
	{
		$resource	= preg_replace( "@(.+)/$@", "\\1", $resource )."/";
		if( !file_exists( $resource ) )
			FolderEditor::createFolder( $resource, 0770 );
		$this->path		= $resource;
		if( $context !== NULL )
			$this->setContext( $context );
		if( $expiration !== NULL )
			$this->setExpiration( $expiration );
	}

	/**
	 *	Removes all expired Cache Files.
	 *	@access		public
	 *	@param		integer		$expires		Cache File Lifetime in Seconds
	 *	@return		integer
	 */
	public function cleanUp( int $expires = 0 ): int
	{
		$expires	= 0 !== $expires ? $expires : $this->expiration;
		if( 0 === $expires )
			throw new InvalidArgumentException( 'No expire time given or set on construction.' );

		$number	= 0;
		$index	= new DirectoryIterator( $this->path.$this->context );
		foreach( $index as $entry ){
			if( $entry->isDot() || $entry->isDir() )
				continue;
			$pathName	= $entry->getPathname();
			if( substr( $pathName, -7 ) !== ".serial" )												//  @todo: why ?
				continue;
			if( $this->isExpired( $pathName ) )
				$number	+= (int) @unlink( $pathName );
		}
		return $number;
	}

	/**
	 *	Wipes clean the entire cache's keys.
	 *
	 *	@access		public
	 *	@return		bool		True on success and false on failure.
	 */
	public function clear(): bool
	{
		$index	= new DirectoryIterator( $this->path.$this->context );
		foreach( $index as $entry ){
			if( $entry->isDot() )
				continue;
			if( $entry->isDir() )
				$this->rrmdir( $entry->getPathname() );
			else
				@unlink( $entry->getPathname() );
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
		if( !$this->has( $key ) )
			return FALSE;
		return @unlink( $this->path.$this->context.$key );
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
		$uri		= $this->path.$this->context.$key;
		if( !$this->isValidFile( $uri ) )
			return NULL;
		return FileEditor::load( $uri );
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
		return $this->isValidFile( $this->path.$this->context.$key );
	}

	/**
	 *	Returns a list of all data pair keys.
	 *	@access		public
	 *	@return		array
	 */
	public function index(): array
	{
		$list	= array();
		$index	= new RecursiveFolderIterator( $this->path.$this->context, TRUE, FALSE, FALSE );
		$length	= strlen( $this->path.$this->context );
		foreach( $index as $entry ){
			$name	= str_replace( '\\', '/', $entry->getPathname() );
			$list[]	= substr( $name, $length );
		}
		ksort( $list );
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
		$uri	= $this->path.$this->context.$key;
		if( dirname( $key ) != '.' )
			$this->createFolder( dirname( $key ) );
		return (bool) FileEditor::save( $uri, $value );
	}

	/**
	 *	Sets context folder within storage.
	 *	If folder is not existing, it will be created.
	 *	@access		public
	 *	@param		string|NULL		$context		Context folder within storage
	 *	@return		self
	 */
	public function setContext( ?string $context = NULL ): self
	{
		if( NULL === $context || 0 === strlen( trim( $context ) ) ){
			$this->context	= NULL;
		}
		else {
			$context	= preg_replace( "@(.+)/$@", "\\1", $context )."/";
			if( !file_exists( $this->path.$context ) )
				FolderEditor::createFolder( $this->path.$context, 0770 );
			$this->context = $context;
		}
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

	//  --  PROTECTED  --  //

	/**
	 *	...
	 *	@access		protected
	 *	@param		string		$folder			...
	 *	@return		void
	 */
	protected function createFolder( string $folder )
	{
		if( file_exists( $this->path.$this->context.$folder ) )
			return;
		$parts	= explode( "/", $folder );
		if( count( $parts ) > 1 )
			$this->createFolder( implode( '/', array_slice( $parts, 0, -1 ) ) );
		mkdir( $this->path.$this->context.$folder );
	}

	/**
	 *	Indicates whether a Cache File is existing and not expired.
	 *	@access		protected
	 *	@param		string		$uri			URI of Cache File
	 *	@return		boolean
	 */
	protected function isValidFile( string $uri ): bool
	{
		return !$this->isExpired( $uri );
	}

	/**
	 *	Indicates whether a Cache File is expired.
	 *	@access		protected
	 *	@param		string		$uri			URI of Cache File
	 *	@return		boolean
	 */
	protected function isExpired( string $uri ): bool
	{
		if( 0 === $this->expiration )
			return FALSE;
		if( !file_exists( $uri ) )
			return TRUE;
		$edge	= time() - $this->expiration;
		clearstatcache();
		return filemtime( $uri ) <= $edge;
	}

	/**
	 *	Removes folder and its files recursively.
	 *	@access		protected
	 *	@param		string		$folder		Path name of folder to remove
	 *	@return		void
	 */
	protected function rrmdir( string $folder )
	{
		$index	= new DirectoryIterator( $folder );
		foreach( $index as $entry ){
			if( $entry->isDot() )
				continue;
			if( $entry->isDir() )
				$this->rrmdir( $entry->getPathname() );
			else
				@unlink( $entry->getPathname() );
		}
		unset( $entry );
		unset( $index );
		rmdir( $folder );
	}
}
