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
use CeusMedia\Cache\AdapterInterface;

use FS_File_Reader as FileReader;

use Exception;
use InvalidArgumentException;
use RuntimeException;

use phpseclib\Crypt\RSA;
use phpseclib\Net\SSH2;
use phpseclib\Net\SCP;

/**
 *	....
 *	Supports context.
 *	@category		Library
 *	@package		CeusMedia_Cache_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
class SSH extends AbstractAdapter implements AdapterInterface
{
	/**	@var	SSH2			$resource */
	protected $resource;

	/**	@var	string|NULL		$host */
	protected $host;

	/**	@var	int|NULL		$port */
	protected $port;

	/**	@var	string|NULL		$privateKey */
	protected $privateKey;

	/**	@var	string|NULL		$username */
	protected $username;

	/**	@var	SCP				$scp */
	protected $scp;

	/**	@var	int				$verbose */
	protected $verbose				= self::VERBOSE_QUIET;

	const VERBOSE_QUIET				= 0;
	const VERBOSE_NORMAL			= 1;
	const VERBOSE_DEBUG				= 2;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		SSH2|string	$resource		SSH client or SSH access string as [USERNAME][:PRIVATEKEY(FILE)]@HOST[:PORT]
	 *	@return		void
	 *	@throws		InvalidArgumentException	if neither client object nor access string are valid
	 */
	public function __construct( $resource = NULL, ?string $context = NULL, ?int $expiration = NULL )
	{
		if( $resource instanceof SSH2 )
			$this->resource	= $resource;
		else if( is_string( $resource ) ){
			$matches	= array();
			preg_match_all('/^(([^:]+)(:(.+))?@)?([^\/]+)(:\d+)?$/s', $resource, $matches );
			if( !$matches[0] )
				throw new InvalidArgumentException( 'Invalid SSH resource given' );
			$this->host			= $matches[5][0];
			$this->port			= empty( $matches[6][0] ) ? 22 : (int) $matches[6][0];
			$this->username		= empty( $matches[2][0] ) ? NULL : $matches[2][0];
			$this->privateKey	= empty( $matches[4][0] ) ? NULL : $matches[4][0];
		}
		else
			throw new InvalidArgumentException( 'Invalid SSH resource given' );
		if( $context !== NULL )
			$this->setContext( $context );
		if( $expiration !== NULL )
			$this->setExpiration( $expiration );
	}

	public function pwd(): string
	{
		return $this->_exec( 'pwd' );
	}

	public function flush(): self
	{
		throw new Exception( 'Not implemented yet' );
//		return $this;
	}





	/**
	 *	Wipes clean the entire cache's keys.
	 *
	 *	@access		public
	 *	@return		bool		True on success and false on failure.
	 */
	public function clear(): bool
	{
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
	public function delete( $key ): bool
	{
//		if( $this->verbose > 0 )
//			remark( "SSH: remove: ".$this->context.$path );
		if( !$this->has( $path ) )
			return FALSE;
		return (bool) $this->_exec( 'rm '.$this->context.$path.' && echo 1' );
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
		$this->_initScp();
//		if( $this->verbose > 0 )
//			remark( "SSH: get: ".$this->context.$path );
		return $this->scp->get( $this->context.$path );
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
//		if( $this->verbose > 0 )
//			remark( "SSH: has: ".$this->context.$path );
		return (bool) $this->_exec( 'test -e '.$this->context.$path.' && echo 1' );
	}

	/**
	 *	Returns a list of all data pair keys.
	 *	@access		public
	 *	@return		array
	 */
	public function index(): array
	{
		$options	= array();
		$options[]	= 'a';														//  show all files/folders
		$options[]	= 'h';														//  show hidden files/folders
		$command	= sprintf( 'ls %s %s', join( $options ), $this->context );	//  render shell command
		$list		= explode( PHP_EOL, trim( $this->_exec( $command ) ) );		//  execute command and split resulting lines
		foreach( $list as $nr => $item )										//  iterate resulting lines
			if( in_array( $item, array( '.', '..' ) ) )							//  if line is current or parent folder
				unset( $list[$nr] );											//  remove from resulting lines
		$list	= array_values( $list );										//  re-index resulting lines
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
	 *	@throws		SimpleCacheInvalidArgumentException		if the $key string is not a legal value.
	 */
	public function set( $key, $value, $ttl = NULL )
	{
		$this->_initScp();
//		if( $this->verbose > 0 )
//			remark( "SSH: set: ".$this->context.$key );
		return $this->scp->put( $this->context.$key, $value );
	}

	public function setContext( ?string $context = NULL ): self
	{
		$this->context	= rtrim( trim( (string) $context ), '/' ).'/';
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

	public function setVerbose( int $verbose = self::VERBOSE_QUIET ): self
	{
		$this->verbose	= (int) $verbose;
		return $this;
	}


	//  --  PROTECTED  --  //

	/**
	 *	@param		boolean		$forceReconnect		Flag: reconnect, default: no
	 *	@return		void
	 */
	protected function _connect( bool $forceReconnect = FALSE )
	{
		if( $this->resource !== NULL && !$forceReconnect )
			return;

		if( $forceReconnect && $this->host === NULL )
			throw new RuntimeException( 'Cannot reconnect, given resource already was a connection' );
		if( $this->privateKey === NULL )
			throw new RuntimeException( 'No private key given' );
		if( $this->port === NULL )
			throw new RuntimeException( 'No port given' );
		if( $this->username === NULL )
			throw new RuntimeException( 'No username given' );

		$key = new RSA();
		if( substr( $this->privateKey, 0, 10 ) === '-----BEGIN' )
			$key->loadKey( $this->privateKey );
		else if( file_exists( $this->privateKey ) )
			$key->loadKey( FileReader::load( $this->privateKey ) );
		else
			throw new Exception( 'Neither valid key string nor key file given' );

		$connection = new SSH2( $this->host, $this->port );
		if( !$connection->login( $this->username, $key ) )
			throw new RuntimeException( sprintf( 'Login as %s failed', $this->username ) );
		$this->resource	= $connection;
	}

	protected function _exec( string $command ): string
	{
		$this->_connect();
		return $this->resource->exec( $command );
	}

	/**
	 *	Use SSH connection to establish a SCP client.
	 *	@param		boolean		$forceReconnect		Flag: reconnect, default: no
	 *	@return		void
	 */
	protected function _initScp( bool $forceReconnect = FALSE )
	{
		if( $this->scp !== NULL && !$forceReconnect )
			return;
		$this->_connect( $forceReconnect );
		$this->scp	= new SCP( $this->resource );
	}
}
