<?php
/**
 *	....
 *	Supports context.
 *	@category		Library
 *	@package		CeusMedia_Cache_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 */
namespace CeusMedia\Cache\Adapter;

use CeusMedia\Cache\AbstractAdapter;
use CeusMedia\Cache\AdapterInterface;
use Exception;
use RuntimeException;
use FS_File_Editor as FileEditor;
use CeusMedia\Cache\Util\FileLock;

/**
 *	....
 *	Supports context.
 *	@category		Library
 *	@package		CeusMedia_Cache_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 */
class JsonFile extends AbstractAdapter implements AdapterInterface
{
	/**	@var	FileEditor		$file */
	protected $file;

	/**	@var	FileLock		$lock */
	protected $lock;

	/**	@var	string			$resource */
	protected $resource;

	public function __construct( $resource, ?string $context = NULL, ?int $expiration = NULL )
	{
		$this->resource	= $resource;
		if( !file_exists( $resource ) )
			file_put_contents( $resource, json_encode( array() ) );
		$this->file	= new FileEditor( $resource );
		$this->lock	= new FileLock( $resource.'.lock' );
		$this->setContext( $context ? $context : 'default' );
		if( $expiration !== NULL )
			$this->setExpiration( $expiration );
	}

	/**
	 *
	 *	@return		void
	 */
	public function cleanup()
	{
		$this->lock->lock();
		try{
			$changed	= FALSE;
			$contexts	= json_decode( $this->file->readString(), TRUE );
			foreach( $contexts as $context => $entries ){
				foreach( $entries as $key => $entry ){
					if( $this->isExpiredEntry( $entry ) ){
						unset( $contexts[$context][$key] );
						$changed	= TRUE;
					}
				}
			}
			if( $changed )
				file_put_contents( $this->resource, json_encode( $contexts ) );
			$this->lock->unlock();
		}
		catch( Exception $e ){
			$this->lock->unlock();
			throw new RuntimeException( 'Cleanup failed: '.$e->getMessage() );
		}
	}

	/**
	 *	Removes all data pairs from storage.
	 *	@access		public
	 *	@return		self
	 */
	public function flush(): self
	{
		$this->lock->lock();
		$entries	= json_decode( $this->file->readString(), TRUE );
		if( isset( $entries[$this->context] ) ){
			foreach( array_keys( $entries[$this->context] ) as $key ){
				unset( $entries[$this->context][$key] );
			}
		}
		file_put_contents( $this->resource, json_encode( $entries ) );
		$this->lock->unlock();
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
		$entries	= json_decode( $this->file->readString(), TRUE );
		if( !isset( $entries[$this->context][$key] ) )
			return NULL;
		$entry	= $entries[$this->context][$key];
		if( $this->isExpiredEntry( $entry ) ){
			$this->remove( $key );
			return NULL;
		}
		return unserialize( $entry['value'] );
	}

	/**
	 *	Indicates whether a data pair is stored by its key.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		boolean
	 */
	public function has( string $key ): bool
	{
		$entries	= json_decode( $this->file->readString(), TRUE );
		if( !isset( $entries[$this->context][$key] ) )
			return FALSE;
		$entry	= $entries[$this->context][$key];
		if( $this->isExpiredEntry( $entry ) ){
			$this->remove( $key );
			return FALSE;
		}
		return TRUE;
	}

	/**
	 *	Returns a list of all data pair keys.
	 *	@access		public
	 *	@return		array
	 */
	public function index(): array
	{
		$entries	= json_decode( $this->file->readString(), TRUE );
		if( !isset( $entries[$this->context] ) )
			return array();
		if( $this->expiration ){
			$now	= time();
			foreach( $entries[$this->context] as $key => $entry ){
				if( $this->isExpiredEntry( $entry ) ){
					$this->remove( $key );
					unset( $entries[$this->context][$key] );
				}
			}
		}
		return array_keys( $entries[$this->context] );
	}

	/**
	 *	Removes data pair from storage by its key.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		boolean
	 */
	public function remove( string $key ): bool
	{
		$entries	= json_decode( $this->file->readString(), TRUE );
		if( !isset( $entries[$this->context][$key] ) )
			return FALSE;
		$this->lock->lock();
		try{
			unset( $entries[$this->context][$key] );
			$this->file->writeString( json_encode( $entries ) );
			$this->lock->unlock();
		}
		catch( Exception $e ){
			$this->lock->unlock();
			throw new RuntimeException( 'Removing cache key failed: '.$e->getMessage() );
		}
		return TRUE;
	}

	/**
	 *	@param		array		$tags
	 *	@return		void
	 */
	public function removeByTags( array $tags )
	{

	}

	/**
	 *	Adds or updates a data pair.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@param		mixed		$value		Data pair value
	 *	@param		integer		$expiration	Data life time in seconds or expiration timestamp
	 *	@return		boolean
	 */
	public function set( string $key, $value, int $expiration = NULL ): bool
	{
		$this->lock->lock();
		try{
			$expiration	= $expiration ? $expiration : $this->expiration;
			$entries	= json_decode( $this->file->readString(), TRUE );
			if( !isset( $entries[$this->context] ) )
				$entries[$this->context]	= array();
			$entries[$this->context][$key] = array(
				'value'		=> serialize( $value ),
				'timestamp'	=> time(),
				'expires'	=> time() + (int) $expiration,
	//			'tags'		=> $tags,
			);
			$this->file->writeString( json_encode( $entries ) );
			$this->lock->unlock();
			return TRUE;
		}
		catch( Exception $e ){
			$this->lock->unlock();
			throw new RuntimeException( 'Setting cache key failed: '.$e->getMessage() );
		}
	}

	protected function isExpiredEntry( array $entry ): bool
	{
		if( $this->expiration ){
			$now	= time();
			$age	= (int) $entry['timestamp'] + $this->expiration;
			if( $age <= $now || $entry['expires'] <= $now )
				return TRUE;
		}
		return FALSE;
	}
}
