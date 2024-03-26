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

use CeusMedia\Cache\Encoder\Igbinary as IgbinaryEncoder;
use CeusMedia\Cache\Encoder\JSON as JsonEncoder;
use CeusMedia\Cache\Encoder\Msgpack as MsgpackEncoder;
use CeusMedia\Cache\Encoder\Serial as SerialEncoder;
use CeusMedia\Cache\SimpleCacheException;
use CeusMedia\Cache\SimpleCacheInterface;
use CeusMedia\Cache\SimpleCacheInvalidArgumentException;
use CeusMedia\Common\Exception\Deprecation as DeprecationException;
use CeusMedia\Common\Exception\IO as IoException;
use CeusMedia\Common\FS\File\Editor as FileEditor;
use CeusMedia\Common\FS\Folder\Editor as FolderEditor;
use CeusMedia\Common\FS\Folder\RecursiveIterator as RecursiveFolderIterator;
use DateInterval;
use DirectoryIterator;
use InvalidArgumentException;

/**
 *	....
 *	Supports context.
 *	@category		Library
 *	@package		CeusMedia_Cache_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
class Folder extends AbstractAdapter implements SimpleCacheInterface
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

	/**	@var		string				$path			Path to Cache Files */
	protected string $path;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		string			$resource		Path name of folder for cache files, e.g. 'cache/'
	 *	@param		string|NULL		$context		Internal prefix for keys for separation
	 *	@param		integer|NULL	$expiration		Data lifetime in seconds or expiration timestamp
	 *	@return		void
	 */
	public function __construct( $resource, ?string $context = NULL, ?int $expiration = NULL )
	{
		$resource	= preg_replace( "@(.+)/$@", "\\1", $resource )."/";
		if( !file_exists( $resource ) )
			FolderEditor::createFolder( $resource );
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
	 *	@codeCoverageIgnore
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
			if( !str_ends_with( $pathName, ".serial" ) )												//  @todo: why ?
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
				$this->recursiveRemoveDirectory( $entry->getPathname() );
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
	 *	@throws		SimpleCacheInvalidArgumentException		if the $key string is not a legal value
	 *	@throws		SimpleCacheException					if file is not writable
	 */
	public function delete( string $key ): bool
	{
		$this->checkKey( $key );
		if( !$this->has( $key ) )
			return FALSE;
		try{
			return FileEditor::delete( $this->path.$this->context.$key );
		}
		catch( IoException $e ){
			throw new SimpleCacheException( 'Deleting data failed', 0, $e );
		}
	}

	/**
	 *	Deletes multiple cache items in a single operation.
	 *
	 *	@param		iterable	$keys		A list of string-based keys to be deleted.
	 *	@return		boolean		True if the items were successfully removed. False if there was an error.
	 *	@throws		SimpleCacheInvalidArgumentException	if any of the $keys are not a legal value.
	 *	@throws		SimpleCacheException					if file is not writable
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
	 *	@throws		SimpleCacheException				if file is not readable
	 */
	public function get( string $key, mixed $default = NULL ): mixed
	{
		$this->checkKey( $key );
		$uri		= $this->path.$this->context.$key;
		if( !$this->isValidFile( $uri ) )
			return $default;
		try{
			$content	= FileEditor::load( $uri ) ?? '';
		}
		catch( IoException $e ){
			throw new SimpleCacheException( $e->getMessage(), 0, $e );
		}
		return $this->decodeValue( $content );
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
		return $this->isValidFile( $this->path.$this->context.$key );
	}

	/**
	 *	Returns a list of all data pair keys.
	 *	@access		public
	 *	@return		array
	 */
	public function index(): array
	{
		$list	= [];
		$index	= new RecursiveFolderIterator( $this->path.$this->context, TRUE, FALSE, FALSE );
		$length	= strlen( $this->path.$this->context );
		foreach( $index as $entry ){
			$name	= str_replace( '\\', '/', $entry->getPathname() );
			$list[]	= substr( $name, $length );
		}
		sort( $list );
		return $list;
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
#		return $this->delete( $key );
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
	 *	@throws		SimpleCacheException				if file is not writable
	 */
	public function set( string $key, mixed $value, DateInterval|int $ttl = NULL ): bool
	{
		$this->checkKey( $key );
		if( is_resource( $value ) )
			throw new SimpleCacheInvalidArgumentException( 'Value must not be an object or resource' );
		$uri	= $this->path.$this->context.$key;
		if( dirname( $key ) != '.' )
			$this->createFolder( dirname( $key ) );
		try{
			FileEditor::save( $uri, $this->encodeValue( $value ) );
		}
		catch( IoException $e ){
			throw new SimpleCacheException( $e->getMessage(), 0, $e );
		}
		return TRUE;
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
				FolderEditor::createFolder( $this->path.$context );
			$this->context = $context;
		}
		return $this;
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

	/**
	 *	...
	 *	@access		protected
	 *	@param		string		$folder			...
	 *	@return		void
	 *	@codeCoverageIgnore
	 */
	protected function createFolder( string $folder ): void
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
		if( !file_exists( $uri ) )
			return TRUE;
		if( 0 === $this->expiration )
			return FALSE;
		$edge	= time() - $this->expiration;
		clearstatcache();
		return filemtime( $uri ) <= $edge;
	}

	/**
	 *	Removes folder and its files recursively (rrmdir).
	 *	@access		protected
	 *	@param		string		$folder		Path name of folder to remove
	 *	@return		void
	 *	@codeCoverageIgnore
	 */
	protected function recursiveRemoveDirectory(string $folder ): void
	{
		$index	= new DirectoryIterator( $folder );
		foreach( $index as $entry ){
			if( $entry->isDot() )
				continue;
			if( $entry->isDir() )
				$this->recursiveRemoveDirectory( $entry->getPathname() );
			else
				@unlink( $entry->getPathname() );
		}
		unset( $entry );
		unset( $index );
		rmdir( $folder );
	}
}
