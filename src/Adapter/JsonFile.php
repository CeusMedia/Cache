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
/**
 *	....
 *	Supports context.
 *	@category		Library
 *	@package		CeusMedia_Cache_Adapter
 *	@extends		\CeusMedia\Cache\AdapterAbstract
 *	@implements		\CeusMedia\Cache\AdapterInterface
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 */
class JsonFile extends \CeusMedia\Cache\AdapterAbstract implements \CeusMedia\Cache\AdapterInterface{

	protected $file;
	protected $lock;
	protected $resource;

	public function __construct( $resource = NULL, $context = NULL, $expiration = NULL ){
		$this->resource	= $resource;
		$this->setContext( $context ? $context : 'default' );
		$this->setExpiration( $expiration );
		if( !file_exists( $resource ) )
			file_put_contents( $resource, json_encode( array() ) );
		$this->file	= new \FS_File_Editor( $resource );
		$this->lock	= new \CeusMedia\Cache\Util\FileLock( $resource.'.lock' );
	}

	/**
	 *	Removes all data pairs from storage.
	 *	@access		public
	 *	@return		void
	 */
	public function flush(){
		$this->lock->lock();
		$entries	= json_decode( $this->file->readString(), TRUE );
		if( isset( $entries[$this->context] ) ){
			foreach( array_keys( $entries[$this->context] ) as $key ){
				unset( $entries[$this->context][$key] );
			}
		}
		file_put_contents( $this->resource, json_encode( $entries ) );
		$this->lock->unlock();
	}

	/**
	 *	Returns a data pair value by its key or NULL if pair not found.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		mixed
	 */
	public function get( $key ){
		$entries	= json_decode( $this->file->readString(), TRUE );
		if( !isset( $entries[$this->context][$key] ) )
			return NULL;
		$entry	= $entries[$this->context][$key];
		if( $this->expiration ){
			$now	= time();
			$age	= (int) $entry['timestamp'] + $this->expiration;
			if( $age <= $now || $entry['expires'] <= $now ){
				$this->remove( $key );
				return NULL;
			}
		}
		return unserialize( $entry['value'] );
	}

	/**
	 *	Indicates whether a data pair is stored by its key.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		boolean
	 */
	public function has( $key ){
		$entries	= json_decode( $this->file->readString(), TRUE );
		if( !isset( $entries[$this->context][$key] ) )
			return FALSE;
		$entry	= $entries[$this->context][$key];
		if( $this->expiration ){
			$now	= time();
			$age	= (int) $entry['timestamp'] + $this->expiration;
			if( $age <= $now || $entry['expires'] <= $now ){
				$this->remove( $key );
				return FALSE;
			}

		}
		return TRUE;
	}

	/**
	 *	Returns a list of all data pair keys.
	 *	@access		public
	 *	@return		array
	 */
	public function index(){
		$entries	= json_decode( $this->file->readString(), TRUE );
		if( !isset( $entry[$this->context] ) )
			return array();
		if( $this->expiration ){
			$now	= time();
			foreach( $entries[$this->context] as $key => $entry ){
				$age	= (int) $entry['timestamp'] + $this->expiration;
				if( $age <= $now || $entry['expires'] <= $now ){
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
	public function remove( $key ){
		$entries	= json_decode( $this->file->readString(), TRUE );
		if( !isset( $entries[$this->context][$key] ) )
			return FALSE;
		$this->lock->lock();
		unset( $entries[$this->context][$key] );
		$this->file->writeString( json_encode( $entries ) );
		$this->lock->unlock();
		return TRUE;
	}

	/**
	 *	Adds or updates a data pair.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@param		string		$value		Data pair value
	 *	@param		integer		$expiration	Data life time in seconds or expiration timestamp
	 *	@return		void
	 */
	public function set( $key, $value, $expiration = NULL ){
		$this->lock->lock();
		$expiration	= $expiration ? $expiration : $this->expiration;
		$entries	= json_decode( $this->file->readString(), TRUE );
		$entries[$this->context][$key] = array(
			'value'		=> serialize( $value ),
			'timestamp'	=> time(),
			'expires'	=> time() + (int) $expiration,
//			'tags'		=> $tags,
		);
		$this->file->writeString( json_encode( $entries ) );
		$this->lock->unlock();
	}
}
?>
