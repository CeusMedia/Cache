<?php
/**
 *	Volatile Memory Storage.
 *	Supports context.
 *	@category		Library
 *	@package		CeusMedia_Cache_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 */
namespace CeusMedia\Cache\Adapter;

use CeusMedia\Cache\AbstractAdapter;
use CeusMedia\Cache\AdapterInterface;
use Net_HTTP_PartitionSession as HttpSession;
use Net_HTTP_Session as HttpPartitionSession;
use InvalidArgumentException;

/**
 *	Volatile Memory Storage.
 *	Supports context.
 *	@category		Library
 *	@package		CeusMedia_Cache_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 */
class Session extends AbstractAdapter implements AdapterInterface
{
	protected $resource;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		array			$resource		Session object or list of partition name (optional) and session name (default: sid) or string PARTITION[@SESSION]
	 *	@param		string			$context		Internal prefix for keys for separation
	 *	@param		integer			$expiration		Data life time in seconds or expiration timestamp
	 *	@return		void
	 */
	public function __construct( $resource, string $context = NULL, int $expiration = NULL )
	{
		if( $resource instanceof HttpSession )
			$this->resource	= $resource;
		else{
			if( is_string( $resource ) )
				$resource		= explode( "@", $resource );
			if( is_array( $resource ) ){
				$partitionName	= $resource[0];
				$sessionName	= isset( $resource[1] ) ? $resource[1] : 'sid';
				if( $partitionName )
					$this->resource		= new HttpPartitionSession( $partitionName, $sessionName );
				else
					$this->resource		= new HttpSession( $sessionName );
			}
			else
				throw new InvalidArgumentException( 'No valid session object or access string set' );
		}
		if( $context !== NULL )
			$this->setContext( $context );
		if( $expiration !== NULL )
			$this->setExpiration( $expiration );
	}

	/**
	 *	Removes all data pairs from storage.
	 *	@access		public
	 *	@return		self
	 */
	public function flush(): self
	{
		$this->resource->clear();
		return $this;
	}

	/**
	 *	Returns a data pair value by its key or NULL if pair not found.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		mixed
	 */
	public function get( string $key )
	{
		if( $this->resource->has( $this->context.$key ) )
			return json_decode( $this->resource->get( $this->context.$key ) );
		return NULL;
	}

	/**
	 *	Indicates whether a data pair is stored by its key.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		boolean
	 */
	public function has( string $key ): bool
	{
		return $this->resource->has( $this->context.$key );
	}

	/**
	 *	Returns a list of all data pair keys.
	 *	@access		public
	 *	@return		array
	 */
	public function index(): array
	{
		return array_keys( $this->resource->getAll( $this->context ) );
	}

	/**
	 *	Removes data pair from storage by its key.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		boolean
	 */
	public function remove( string $key ): bool
	{
		if( !$this->resource->has( $this->context.$key ) )
			return FALSE;
		$this->resource->remove( $this->context.$key );
		return TRUE;
	}

	/**
	 *	Adds or updates a data pair.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@param		mixed		$value		Data pair value
	 *	@param		integer		$expiration	Data life time in seconds or expiration timestamp
	 *	@return		boolean		Result state of operation
	 */
	public function set( string $key, $value, int $expiration = NULL ): bool
	{
		return $this->resource->set( $this->context.$key, json_encode( $value ) );
	}
}
