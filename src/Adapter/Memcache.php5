<?php
/**
 *	Cache storage adapter for memcache.
 *	Supports context.
 *	@category		Library
 *	@package		CeusMedia_Cache_Adapter
 *	@extends		CMM_SEA_Adapter_Abstract
 *	@implements		CMM_SEA_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 */
namespace CeusMedia\Cache\Adapter;
/**
 *	Cache storage adapter for memcache.
 *	Supports context.
 *	@category		Library
 *	@package		CeusMedia_Cache_Adapter
 *	@extends		CMM_SEA_Adapter_Abstract
 *	@implements		CMM_SEA_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 */
class Memcache extends \CeusMedia\Cache\AdapterAbstract implements \CeusMedia\Cache\AdapterInterface{
	protected $resource;
	protected $host			= 'localhost';
	protected $port			= 11211;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		string		$resource		Memcache server hostname and port, eg. 'localhost:11211' (default)
	 *	@param		string		$context		Internal prefix for keys for separation
	 *	@param		integer		$expiration		Data life time in seconds or expiration timestamp
	 *	@return		void
	 */
	public function __construct( $resource = 'localhost:11211', $context = NULL, $expiration = NULL ){
		$parts	= explode( ":", trim( (string) $resource ) );
		if( isset( $parts[0] ) && trim( $parts[0] ) )
			$this->host	= $parts[0];
		if( isset( $parts[1] ) && trim( $parts[1] ) )
			$this->port	= $parts[1];
		$this->resource = new Memcache;
		$this->resource->addServer( $this->host, $this->port );
		if( $context )
			$this->setContext( $context );
		if( $expiration )
			$this->setExpiration( $expiration );
	}

	/**
	 *	Removes all data pairs from storage.
	 *	@access		public
	 *	@return		void
	 */
	public function flush(){
		if( !$this->context )
			$this->resource->flush();
		else{
			foreach( $this->index() as $key )
				$this->remove( $key );
		}
	}

	/**
	 *	Returns a data pair value by its key or NULL if pair not found.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		mixed
	 */
	public function get( $key ){
		$data	= $this->resource->get( $this->context.$key );
		if( $data )
			return unserialize( $data );
		return NULL;
	}

	/**
	 *	Indicates whether a data pair is stored by its key.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		boolean
	 */
	public function has( $key ){
		return $this->get( $key ) !== NULL;
	}

	/**
	 *	Returns a list of all data pair keys.
	 *	@access		public
	 *	@return		array
	 */
	public function index(){
		$list	= array();
		$string	= $this->sendMemcacheCommand( "stats items" );
		$lines	= explode( "\r\n", $string );
		$slabs	= array();
		foreach( $lines as $line ){
			if( preg_match( "/STAT items:([\d]+):/", $line, $matches ) == 1 ){
				if( isset( $matches[1] ) ){
					if( !in_array( $matches[1], $slabs ) ){
						$slabs[]	= $matches[1];
						$string		= $this->sendMemcacheCommand( "stats cachedump ".$matches[1]." 100" );
						preg_match_all( "/ITEM (.*?) /", $string, $matches );
						$list		= array_merge( $list, $matches[1] );
					}
				}
			}
		}
		if( $this->context )
			foreach( $list as $nr => $item )
				if( substr( $item, 0, strlen( $this->context ) ) == $this->context )
					$list[$nr]	= substr( $list[$nr], strlen( $this->context ) );
				else
					unset( $list[$nr] );

		return array_values( $list );
	}

	/**
	 *	Removes data pair from storage by its key.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		boolean
	 */
	public function remove( $key ){
		return $this->resource->delete( $this->context.$key, 0 );
	}

	/**
	 *	Sends command to memache daemon using a socket connection.
	 *	Taken directly from memcache PECL source
	 *	@access		protected
	 *	@param		string		$command		Memcache command to send directly
	 *	@return		string
	 *	@see		http://pecl.php.net/package/memcache
	 */
	protected function sendMemcacheCommand( $command ){
		$socket = @fsockopen( $this->host, $this->port );
		if( !$socket )
			die( "Cant connect to:".$this->host.':'.$this->port );
		fwrite( $socket, $command."\r\n" );
		$buffer	= '';
		while( ( !feof( $socket ) ) ){
			$buffer .= fgets( $socket, 256 );
			if( preg_match( '/(END|DELETED|NOT_FOUND|OK)\r\n/s', $buffer ) )
				break;
		}
		fclose( $socket );
		return( $buffer );
	}

	/**
	 *	Adds or updates a data pair.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@param		string		$value		Data pair value
	 *	@param		integer		$expiration	Data life time in seconds or expiration timestamp
	 *	@see		http://www.php.net/manual/en/memcached.expiration.php Expiration Times
	 *	@return		void
	 */
	public function set( $key, $value, $expiration = NULL ){
		$expiration	= $expiration === NULL ? $this->expiration : $expiration;
		$this->resource->set( $this->context.$key, serialize( $value ), 0, $expiration );
	}

	/**
	 *	Sets context within storage.
	 *	@access		public
	 *	@param		string		$context		Context within storage
	 *	@return		void
	 *	@todo		remove inner delimiter
	 */
	public function setContext( $context ){
		if( strlen( trim( $context ) ) )
			$this->context = $context.':';
	}
}
?>
