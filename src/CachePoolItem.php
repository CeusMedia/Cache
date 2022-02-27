<?php
/**
 *	....
 *	@category		Library
 *	@package		CeusMedia_Cache
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
namespace CeusMedia\Cache;

use Psr\Cache\CacheItemInterface;
use DateInterval;
use DateTimeInterface;

/**
 *	....
 *	@category		Library
 *	@package		CeusMedia_Cache
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
class CachePoolItem implements CacheItemInterface
{
	/**	@var		SimpleCacheInterface		$adapter */
	protected $adapter;

	/**	@var		DateTimeInterface|NULL		$expiration */
	protected $expiration;

	/**	@var		string						$key */
	protected $key;

	/**	@var		DateInterval|int|NULL		$ttl */
	protected $ttl;

	/**	@var		mixed						$value */
	protected $value			= NULL;

	/**	@var		boolean						$isHit */
	protected $isHit			= FALSE;

	/**
	 *	Constructor.
	 *
	 *	@access		public
	 *	@param		SimpleCacheInterface	$adapter		...
	 *	@param		string					$key			...
	 *	@return		void
	 */
	public function __construct( SimpleCacheInterface $adapter, string $key )
	{
		$this->adapter	= $adapter;
		$this->key		= $key;
		print( 'Key: '.$key.PHP_EOL );
		if( $this->adapter->has( $key ) ){
			$this->value	= $this->decode( $this->adapter->get( $key ) );
			$this->isHit	= TRUE;
		}
	}

	/**
	 *	Returns the key for the current cache item.
	 *
	 *	The key is loaded by the Implementing Library, but should be available to
	 *	the higher level callers when needed.
	 *
	 *	@access		public
	 *	@return		string		The key string for this cache item.
	 */
	public function getKey()
	{
		return $this->key;
	}

	/**
	 *	Retrieves the value of the item from the cache associated with this object's key.
	 *
	 *	The value returned must be identical to the value originally stored by set().
	 *
	 *	If isHit() returns false, this method MUST return null. Note that null
	 *	is a legitimate cached value, so the isHit() method SHOULD be used to
	 *	differentiate between "null value was found" and "no value was found."
	 *
	 *	@access		public
	 *	@return		mixed		The value corresponding to this cache item's key, or null if not found.
	 */
	public function get()
	{
		return $this->value;
	}

	/**
	 *	Confirms if the cache item lookup resulted in a cache hit.
	 *
	 *	Note: This method MUST NOT have a race condition between calling isHit()
	 *	and calling get().
	 *
	 *	@access		public
	 *	@return		bool		True if the request resulted in a cache hit. False otherwise.
	 */
	public function isHit()
	{
		return $this->isHit;
	}

	/**
	 *	Sets the value represented by this cache item.
	 *
	 *	The $value argument may be any item that can be serialized by PHP,
	 *	although the method of serialization is left up to the Implementing
	 *	Library.
	 *
	 *	@access		public
	 *	@param		mixed		$value		The serializable value to be stored.
	 *	@return		static		The invoked object.
	 */
	public function set( $value )
	{
		$this->value	= $value;
		return $this;
	}

	/**
	 *	Sets the expiration time for this cache item.
	 *
	 *	@access		public
	 *	@param		DateTimeInterface|null		$expiration
	 *   The point in time after which the item MUST be considered expired.
	 *   If null is passed explicitly, a default value MAY be used. If none is set,
	 *   the value should be stored permanently or for as long as the
	 *   implementation allows.
	 *	@return		static		The called object.
	 */
	public function expiresAt( $expiration )
	{
		$this->expiration	= $expiration;
		return $this;
	}

	/**
	 *	Sets the expiration time for this cache item.
	 *
	 *	@access		public
	 *	@param		int|DateInterval|null		$time
	 *   The period of time from the present after which the item MUST be considered
	 *   expired. An integer parameter is understood to be the time in seconds until
	 *   expiration. If null is passed explicitly, a default value MAY be used.
	 *   If none is set, the value should be stored permanently or for as long as the
	 *   implementation allows.
	 *	@return		static		The called object.
	 */
	public function expiresAfter( $time )
	{
		$this->ttl	= $time;
		return $this;
	}

	//  --  PROTECTED  --  //

	/**
	 *	...
	 *
	 *	@access		protected
	 *	@param		string		$encodedValue		...
	 *	@return		mixed
	 */
	protected function decode( $encodedValue )
	{
		return $encodedValue;
//		return unserialize( $encodedValue );
	}

	/**
	 *	...
	 *
	 *	@access		protected
	 *	@param		mixed		$value		...
	 *	@return		string
	 */
	protected function encode( $value ): string
	{
		return $value;
//		return serialize( $value );
	}
}
