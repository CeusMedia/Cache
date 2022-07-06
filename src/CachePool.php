<?php
declare(strict_types=1);

/**
 *	....
 *	@category		Library
 *	@package		CeusMedia_Cache
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
namespace CeusMedia\Cache;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use CeusMedia\Cache\CachePoolInvalidArgumentException as InvalidArgumentException;
use Traversable;

/**
 *	....
 *	@category		Library
 *	@package		CeusMedia_Cache
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
class CachePool
{
	/**	@var		SimpleCacheInterface		$adapter */
	protected $adapter;

	public function __construct( SimpleCacheInterface $adapter )
	{
		$this->adapter	= $adapter;
	}

	/**
	 * Returns a Cache Item representing the specified key.
	 *
	 * This method must always return a CacheItemInterface object, even in case of
	 * a cache miss. It MUST NOT return null.
	 *
	 *	@param		string		$key		The key for which to return the corresponding Cache Item.
	 *	@return		CacheItemInterface		The corresponding Cache Item.
	 *	@throws		InvalidArgumentException		If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException MUST be thrown.
	 */
	public function getItem( $key )
	{
		$item	= new CachePoolItem( $this->adapter, $key );
		return $item;
	}

	/**
	 *	Returns a traversable set of cache items.
	 *
	 *	@param		string[]		$keys		An indexed array of keys of items to retrieve.
	 *	@return		array|Traversable
	 *		A traversable collection of Cache Items keyed by the cache keys of
	 *		each item. A Cache item will be returned for each key, even if that
	 *		key is not found. However, if no keys are specified then an empty
	 *		traversable MUST be returned instead.
	 *	@throws		InvalidArgumentException	If any of the keys in $keys are not a legal value a \Psr\Cache\InvalidArgumentException MUST be thrown.
	 */
	public function getItems( array $keys = array() )
	{
		return $this->adapter->getMultiple( $keys );
	}

	/**
	 * Confirms if the cache contains specified cache item.
	 *
	 * Note: This method MAY avoid retrieving the cached value for performance reasons.
	 * This could result in a race condition with CacheItemInterface::get(). To avoid
	 * such situation use CacheItemInterface::isHit() instead.
	 *
	 *	@param		string		$key		The key for which to check existence.
	 *	@return		bool		True if item exists in the cache, false otherwise.
	 *	@throws		InvalidArgumentException		If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException MUST be thrown.
	 */
	public function hasItem( $key )
	{
		return $this->adapter->has( $key );
	}

	/**
	 *	Deletes all items in the pool.
	 *
	 *	@return		bool		True if the pool was successfully cleared. False if there was an error.
	 */
	public function clear()
	{
		return $this->adapter->clear();
	}

	/**
	 *	Removes the item from the pool.
	 *
	 *	@param		string		$key		The key to delete.
	 *	@return		bool		True if the item was successfully removed. False if there was an error.
	 *	@throws		InvalidArgumentException	If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException MUST be thrown.
	 */
	public function deleteItem( $key )
	{
		return $this->adapter->delete( $key );
	}

	/**
	 *	Removes multiple items from the pool.
	 *
	 *	@param		string[]		$keys		An array of keys that should be removed from the pool.
	 *	@return		bool		True if the items were successfully removed. False if there was an error.
	 *	@throws		InvalidArgumentException		If any of the keys in $keys are not a legal value a \Psr\Cache\InvalidArgumentException MUST be thrown.
	 */
	public function deleteItems( array $keys )
	{
		return $this->adapter->deleteMultiple( $keys );
	}

	/**
	 *	Persists a cache item immediately.
	 *
	 *	@param		CacheItemInterface		$item		The cache item to save.
	 *	@return		bool		True if the item was successfully persisted. False if there was an error.
	 */
	public function save( CacheItemInterface $item )
	{
		return $this->adapter->set( $item->getKey(), $item->get() );
	}

	/**
	 * Sets a cache item to be persisted later.
	 *
	 *	@param		CacheItemInterface		$item		The cache item to save.
	 *	@return		bool		False if the item could not be queued or if a commit was attempted and failed. True otherwise.
	 *	@todo		implement!
	 */
	public function saveDeferred( CacheItemInterface $item )
	{
		return TRUE;
	}

	/**
	 * Persists any deferred cache items.
	 *
	 *	@return		bool		True if all not-yet-saved items were successfully saved or there were none. False otherwise.
	 *	@todo		implement!
	 */
	public function commit()
	{
		return TRUE;
	}
}
