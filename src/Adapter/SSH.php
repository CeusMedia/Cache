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
use InvalidArgumentException;
use Exception;
use RuntimeException;

/**
 *	....
 *	Supports context.
 *	@category		Library
 *	@package		CeusMedia_Cache_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
class SSH extends AbstractAdapter implements AdapterInterface
{
	protected $client;

	protected $connection;

	protected $host;

	protected $mode;

	protected $port;

	protected $privateKey;

	protected $username;

	protected $scp;

	protected $verbose				= self::VERBOSE_QUIET;

	const VERBOSE_QUIET				= 0;
	const VERBOSE_NORMAL			= 1;
	const VERBOSE_DEBUG				= 2;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		\phpseclib\Net\SSH2|string	$resource		SSH client or SSH access string as [USERNAME][:PRIVATEKEY(FILE)]@HOST[:PORT]
	 *	@return		void
	 *	@throws		InvalidArgumentException	if neither client object nor access string are valid
	 */
	public function __construct( $resource, string $context = NULL, int $expiration = NULL )
	{
		if( $resource instanceof \phpseclib\Net\SSH2 )
			$this->client	= $resource;
		else if( is_string( $resource ) ){
			$matches	= array();
			preg_match_all('/^(([^:]+)(:(.+))?@)?([^\/]+)(:\d+)?$/s', $resource, $matches );
			if( !$matches[0] )
				throw new InvalidArgumentException( 'Invalid SSH resource given' );
			$this->host			= $matches[5][0];
			$this->port			= empty( $matches[6][0] ) ? 22 : $matches[6][0];
			$this->username		= empty( $matches[2][0] ) ? NULL : $matches[2][0];
			$this->privateKey	= empty( $matches[4][0] ) ? NULL : $matches[4][0];
		}
		else
			throw new InvalidArgumentException( 'Invalid FTP resource given' );
		if( $context !== NULL )
			$this->setContext( $context );
		if( $expiration !== NULL )
			$this->setExpiration( $expiration );
	}

	public function pwd(): string
	{
		return $this->_exec( 'pwd' );
	}

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

	public function flush(): self
	{
		throw new Exception( 'Not implemented yet' );
//		return $this;
	}

	public function has( string $path ): bool
	{
//		if( $this->verbose > 0 )
//			remark( "SSH: has: ".$this->context.$path );
		return (bool) $this->_exec( 'test -e '.$this->context.$path.' && echo 1' );
	}

	public function get( string $path )
	{
		$this->_initScp();
//		if( $this->verbose > 0 )
//			remark( "SSH: get: ".$this->context.$path );
		return $this->scp->get( $this->context.$path );
	}

	public function remove( string $path ): bool
	{
//		if( $this->verbose > 0 )
//			remark( "SSH: remove: ".$this->context.$path );
		if( !$this->has( $path ) )
			return FALSE;
		return (bool) $this->_exec( 'rm '.$this->context.$path.' && echo 1' );
	}

	public function set( string $key, $value, int $expiration = NULL ): bool
	{
		$this->_initScp();
//		if( $this->verbose > 0 )
//			remark( "SSH: set: ".$this->context.$key );
		return $this->scp->put( $this->context.$key, $value );
	}

	public function setContext( string $path ): self
	{
		$this->context	= rtrim( trim( $path ), '/' ).'/';
		return $this;
	}

	public function setVerbose( $verbose = self::VERBOSE_QUIET ): self
	{
		$this->verbose	= (int) $verbose;
		return $this;
	}

	//  --  PROTECTED  --  //

	protected function _connect( bool $forceReconnect = FALSE )
	{
		if( $this->connection && !$forceReconnect )
			return;
		$key = new \phpseclib\Crypt\RSA();
		if( substr( $this->privateKey, 0, 10 ) === '-----BEGIN' )
			$key->loadKey( $this->privateKey );
		else if( file_exists( $this->privateKey ) )
			$key->loadKey( file_get_contents( $this->privateKey ) );
		else
			throw new Exception( 'Neither valid key string nor key file given' );

		$connection = new \phpseclib\Net\SSH2( $this->host, $this->port );
		if( !$connection->login( $this->username, $key ) )
			throw new RuntimeException( sprintf( 'Login as %s failed', $this->username ) );
		$this->connection	= $connection;
	}

	protected function _exec( string $command )
	{
		$this->_connect();
		return $this->connection->exec( $command );
	}

	protected function _initScp()
	{
		if( $this->scp )
			return;
		$this->_connect();
		$this->scp	= new \phpseclib\Net\SCP( $this->connection );
	}
}
