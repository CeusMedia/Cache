<?php
/**
 *	Storage adapter for files via FTP.
 *	Supports context.
 *	@category		Library
 *	@package		CeusMedia_Cache_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			16.09.2011
 */
namespace CeusMedia\Cache\Adapter;

use CeusMedia\Cache\AbstractAdapter;
use CeusMedia\Cache\AdapterInterface;
use FS_File_Reader as FileReader;
use FS_File_Writer as FileWriter;
use Net_FTP_Client as FtpClient;
use InvalidArgumentException;
use RuntimeException;

/**
 *	Storage adapter for files via FTP.
 *	Supports context.
 *	@category		Library
 *	@package		CeusMedia_Cache_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			16.09.2011
 */
class FTP extends AbstractAdapter implements AdapterInterface
{
	/**	@var		FtpClient		$client		FTP Client */
	protected $client;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		FtpClient|string	$resource		FTP client or FTP access string as [USERNAME][:PASSWORT]@HOST[:PORT]/[PATH]
	 *	@param		string|NULL			$context		Internal prefix for keys for separation
	 *	@param		integer|NULL	$expiration		Data life time in seconds or expiration timestamp
	 *	@return		void
	 *	@throws		InvalidArgumentException	if neither client object nor access string are valid
	 */
	public function __construct( $resource, ?string $context = NULL, ?int $expiration = NULL )
	{
		if( $resource instanceof FtpClient )
			$this->client	= $resource;
		else if( is_string( $resource ) ){
			$matches	= array();
			preg_match_all('/^(([^:]+)(:(.+))?@)?([^\/]+)(:\d+)?\/(.+)?$/', $resource, $matches );
			if( !$matches[0] )
				throw new InvalidArgumentException( 'Invalid FTP resource given' );
			$this->client	= new FtpClient(
				$matches[5][0],																		//  host
				empty( $matches[6][0] ) ? 21 : $matches[6][0],										//  port
				$matches[7][0],																		//  base path
				empty( $matches[2][0] ) ? NULL : $matches[2][0],									//  username
				empty( $matches[4][0] ) ? NULL : $matches[4][0]										//  password
			);
		}
		else
			throw new InvalidArgumentException( 'Invalid FTP resource given' );
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
		foreach( $this->index() as $file )
			$this->remove( $file );
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
		return $this->client->removeFile( $this->context.$key );
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
		if( !$this->has( $key ) )
			return NULL;
		$tmpFile	= tempnam( './', 'ftp_'.uniqid().'_' );
		if( $tmpFile === FALSE )
			throw new RuntimeException( 'Could not create temp file' );
		$this->client->getFile( $this->context.$key, $tmpFile );
		$content	= FileReader::load( $tmpFile );
		@unlink( $tmpFile );
		return $content;
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
		return in_array( $key, $this->index() );
	}

	/**
	 *	Returns a list of all data pair keys.
	 *	@access		public
	 *	@return		array
	 */
	public function index(): array
	{
		$list	= array();
		foreach( $this->client->getFileList( (string) $this->context, TRUE ) as $item )
			$list[]	= $item['name'];
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
	 *	@throws		SimpleCacheInvalidArgumentException		if the $key string is not a legal value.
	 */
	public function set( $key, $value, $ttl = NULL )
	{
		$tmpFile	= tempnam( './', 'ftp_'.uniqid().'_' );
		if( $tmpFile === FALSE )
			throw new RuntimeException( 'Could not create temp file' );
		FileWriter::save( $tmpFile, $value );
		$result	= $this->client->putFile( $tmpFile, $this->context.$key );
		@unlink( $tmpFile );
		return $result;
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
		if( $context === NULL || !strlen( trim( $context ) ) ){
			$this->context	= NULL;
			return $this;
		}
//		if( !$this->client->hasFolder( $context ) )
			$this->client->createFolder( $context );
		$context	= preg_replace( "@(.+)/$@", "\\1", $context )."/";
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
	 *	@throws		SimpleCacheInvalidArgumentException		if $values is neither an array nor a Traversable,
	 *														or if any of the $values are not a legal value.
	 */
	public function setMultiple($values, $ttl = null)
	{
		return TRUE;
	}
}
