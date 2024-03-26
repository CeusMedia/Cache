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

use CeusMedia\Cache\Encoder\Igbinary as IgbinaryEncoder;
use CeusMedia\Cache\Encoder\JSON as JsonEncoder;
use CeusMedia\Cache\Encoder\Msgpack as MsgpackEncoder;
use CeusMedia\Cache\Encoder\Serial as SerialEncoder;
use CeusMedia\Cache\SimpleCacheException;
use CeusMedia\Cache\SimpleCacheInterface;
use CeusMedia\Cache\SimpleCacheInvalidArgumentException;
use CeusMedia\Common\Exception\Deprecation as DeprecationException;
use CeusMedia\Common\FS\File\Editor as FileEditor;
use CeusMedia\Common\FS\Folder\Editor as FolderEditor;
use DateInterval;
use DirectoryIterator;
use InvalidArgumentException;
use Throwable;

/**
 *	....
 *	@category		Library
 *	@package		CeusMedia_Cache_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
class SerialFolder extends AbstractAdapter implements SimpleCacheInterface
{
	/**	@var		array				$data			Memory Cache */
	protected array $data				= [];

	/**	@var	array					$enabledEncoders	List of allowed encoder classes */
	protected array $enabledEncoders	= [
		IgbinaryEncoder::class,
		JsonEncoder::class,
		MsgpackEncoder::class,
		SerialEncoder::class,
	];

	/**	@var	string|NULL				$encoder */
	protected ?string $encoder			= SerialEncoder::class;

	/**	@var		string				$path			Path to Cache Files */
	protected string $path;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		string			$resource		Path to Cache Files
	 *	@param		string|NULL		$context		Internal prefix for keys for separation
	 *	@param		integer|NULL	$expiration		Seconds until Pairs will be expired
	 *	@return		void
	 */
	public function __construct( $resource, ?string $context = NULL, ?int $expiration = NULL )
	{
		$resource	.= str_ends_with( $resource, "/" ) ? "" : "/";
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
	 *	@param		int			$expires		Cache File Lifetime in Seconds
	 *	@return		integer
	 *	@codeCoverageIgnore
	 */
	public function cleanUp( int $expires = 0 ): int
	{
		$expires	= 0 !== $expires ? $expires : $this->expiration;
		if( 0 === $expires )
			throw new InvalidArgumentException( 'No expire time given or set on construction.' );

		$number	= 0;
		$index	= new DirectoryIterator( $this->path );
		foreach( $index as $entry )
		{
			if( $entry->isDot() || $entry->isDir() )
				continue;
			$pathName	= $entry->getPathname();
			if( !str_ends_with( $pathName, ".serial" ) )
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
		$index	= new DirectoryIterator( $this->path );
		foreach( $index as $entry )
			if( !$entry->isDot() && !$entry->isDir() )
				if( str_ends_with( $entry->getFilename(), ".serial" ) )
					@unlink( $entry->getPathname() );
		$this->data	= [];
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
	public function delete( string $key ): bool
	{
		$this->checkKey( $key );
		if( !$this->has( $key ) )
			return FALSE;
		$uri	= $this->getUriForKey( $key );
		unset( $this->data[$this->context.$key] );
		@unlink( $uri );
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
	 *	@throws		SimpleCacheInvalidArgumentException		if the $key string is not a legal value
	 *	@throws		SimpleCacheException					if reading data failed
	 */
	public function get( string $key, mixed $default = NULL ): mixed
	{
		$this->checkKey( $key );
		$uri		= $this->getUriForKey( $key );
		if( !$this->isValidFile( $uri ) )
			return $default;
		if( isset( $this->data[$this->context.$key] ) )
			return $this->data[$this->context.$key];
		try{
			$content	= FileEditor::load( $uri );
		}
		catch( Throwable $t ){
			throw new SimpleCacheException( 'Reading data failed: '.$t->getMessage(), 0, $t );
		}
		$value		= 0 !== strlen( $content ?? '' ) ? $this->decodeValue( $content ) : NULL;
		$this->data[$this->context.$key]	= $value;
		return $value;
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
	public function has( string $key ): bool
	{
		$this->checkKey( $key );
		$uri	= $this->getUriForKey( $key );
		return $this->isValidFile( $uri );
	}

	/**
	 *	Returns a list of all data pair keys.
	 *	@access		public
	 *	@return		array
	 */
	public function index(): array
	{
		if( NULL !== $this->context ){
			$list	= [];
			$length	= strlen( $this->context );
			foreach( $this->data as $key => $value )
				if( substr( $key, 0, $length ) == $this->context )
					$list[]	= substr( $key, $length );
			return $list;
		}
		return array_keys( $this->data );
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
	 *	@throws		SimpleCacheInvalidArgumentException		if the $key string is not a legal value.
	 */
	public function set( string $key, mixed $value, DateInterval|int $ttl = NULL ): bool
	{
		$this->checkKey( $key );
		$this->data[$this->context.$key]	= $value;
		$uri	= $this->getUriForKey( $key );
		return (bool) FileEditor::save( $uri, $this->encodeValue( $value ) );
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
	 *	Returns URI of Cache File from its Key.
	 *	@access		protected
	 *	@param		string		$key			Key of Cache File
	 *	@return		string
	 */
	protected function getUriForKey( string $key ): string
	{
		return $this->path.base64_encode( $this->context.$key ).".serial";
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
	 *	Indicates whether a Cache File is existing and not expired.
	 *	@access		protected
	 *	@param		string		$uri			URI of Cache File
	 *	@return		boolean
	 */
	protected function isValidFile( string $uri ): bool
	{
		return !$this->isExpired( $uri );
	}
}
