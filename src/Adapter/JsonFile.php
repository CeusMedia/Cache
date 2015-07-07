<?php
namespace CeusMedia\Cache\Adapter;

class JsonFile extends \CeusMedia\Cache\AdapterAbstract implements \CeusMedia\Cache\AdapterInterface{

	protected $file;
	protected $resource;

	public function __construct( $resource = NULL, $context = NULL, $expiration = NULL ){
		$this->resource	= $resource;
		if( !file_exists( $resource ) )
			file_put_contents( $resource, json_encode( array() ) );
		$this->file = new \FS_File_Editor( $resource );
	}

	/**
	 *	Removes all data pairs from storage.
	 *	@access		public
	 *	@return		void
	 */
	public function flush(){
		file_put_contents( $this->resource, json_encode( array() ) );
	}

	/**
	 *	Returns a data pair value by its key or NULL if pair not found.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		mixed
	 */
	public function get( $key ){
		$data	= json_decode( $this->file->readString(), TRUE );
		if( isset( $data[$key] ) )
			return unserialize( $data[$key] );
		return NULL;
	}

	/**
	 *	Indicates whether a data pair is stored by its key.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		boolean
	 */
	public function has( $key ){
		$data	= json_decode( $this->file->readString(), TRUE );
		return isset( $data[$key] );
	}

	/**
	 *	Returns a list of all data pair keys.
	 *	@access		public
	 *	@return		array
	 */
	public function index(){
		$data	= json_decode( $this->file->readString(), TRUE );
		return array_keys( $data );
	}

	/**
	 *	Removes data pair from storage by its key.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		boolean
	 */
	public function remove( $key ){
		$data	= json_decode( $this->file->readString(), TRUE );
		if( !isset( $data[$key] ) )
			return FALSE;
		unset( $data[$key] );
		$this->file->writeString( json_encode( $data ) );
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
		$data	= json_decode( $this->file->readString(), TRUE );
		$data[$key] = serialize( $value );
		$this->file->writeString( json_encode( $data ) );
	}
}
?>
