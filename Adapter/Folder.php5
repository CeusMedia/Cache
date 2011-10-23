<?php
/**
 *	....
 *	Supports context.
 *	@category		cmModules
 *	@package		SEA
 *	@extends		CMM_SEA_Adapter_Abstract
 *	@implements		CMM_SEA_Adapter_Interface
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 *	@version		$Id: SerialFolder.php5 31 2011-06-02 12:49:43Z christian.wuerker $
 */
/**
 *	....
 *	Supports context.
 *	@category		cmModules
 *	@package		SEA
 *	@extends		CMM_SEA_Adapter_Abstract
 *	@implements		CMM_SEA_Adapter_Interface
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 *	@version		$Id: SerialFolder.php5 31 2011-06-02 12:49:43Z christian.wuerker $
 */
class CMM_SEA_Adapter_Folder extends CMM_SEA_Adapter_Abstract implements CMM_SEA_Adapter_Interface
{
	/**	@var		string		$path			Path to Cache Files */
	protected $path;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		string		$resource		Memcache server hostname and port, eg. 'localhost:11211' (default)
	 *	@param		string		$context		Internal prefix for keys for separation
	 *	@param		integer		$expiration		Data life time in seconds or expiration timestamp
	 *	@return		void
	 */
	public function __construct( $resource = NULL, $context = NULL, $expiration = NULL )
	{
		$resource	= preg_replace( "@(.+)/$@", "\\1", $resource )."/";
		if( !file_exists( $resource ) )
			throw new RuntimeException( 'Storage folder "'.$resource.'" is not existing' );
		$this->path		= $resource;
		if( $context !== NULL )
			$this->setContext( $context );
	}
	
	/**
	 *	Removes all expired Cache Files.
	 *	@access		public
	 *	@param		int			$expires		Cache File Lifetime in Seconds
	 *	@return		bool
	 */
	public function cleanUp( $expires = 0 ){
		$expires	= $expires ? $expires : $this->expires;
		if( !$expires )
			throw new InvalidArgumentException( 'No expire time given or set on construction.' );

		$number	= 0;
		$index	= new DirectoryIterator( $this->path.$this->context );
		foreach( $index as $entry ){
			if( $entry->isDot() || $entry->isDir() )
				continue;
			$pathName	= $entry->getPathname();
			if( substr( $pathName, -7 ) !== ".serial" )
				continue;
			if( $this->isExpired( $pathName, $expires ) )
				$number	+= (int) @unlink( $pathName );
		}
		return $number;
	}

	protected function rrmdir( $folder ){
		$index	= new DirectoryIterator( $folder );
		foreach( $index as $entry ){
			if( $entry->isDot() )
				continue;
			if( $entry->isDir() )				
				$this->rrmdir( $entry->getPathname() );
			else
				@unlink( $entry->getPathname() );
		}
		unset( $entry );
		unset( $index );
		rmdir( $folder );
	}

	/**
	 *	Removes all data pairs from storage.
	 *	@access		public
	 *	@return		void
	 */
	public function flush(){
		$index	= new DirectoryIterator( $this->path.$this->context );
		foreach( $index as $entry ){
			if( $entry->isDot() )
				continue;
			if( $entry->isDir() )
				$this->rrmdir( $entry->getPathname() );
			else
				@unlink( $entry->getPathname() );
		}
	}

	/**
	 *	Returns a data pair value by its key or NULL if pair not found.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		mixed
	 */
	public function get( $key )
	{
		$uri		= $this->path.$this->context.$key;
		if( !$this->isValidFile( $uri ) )
			return NULL;
		return File_Editor::load( $uri );
	}

	/**
	 *	Indicates whether a data pair is stored by its key.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		boolean
	 */
	public function has( $key )
	{
		return $this->isValidFile( $this->path.$this->context.$key );
	}

	/**
	 *	Returns a list of all data pair keys.
	 *	@access		public
	 *	@return		array
	 *	@todo		implement
	 */
	public function index()
	{
		$list	= array();
		$index	= new Folder_RecursiveIterator( $this->path.$this->context, TRUE, FALSE, FALSE );
		$length	= strlen( $this->path.$this->context );
		foreach( $index as $entry ){
			$name			= str_replace( '\\', '/', $entry->getPathname() );
			$list[$name]	= substr( $name, $length );
		}
		ksort( $list );
		return $list;
	}

	/**
	 *	Indicates whether a Cache File is existing and not expired.
	 *	@access		protected
	 *	@param		string		$uri			URI of Cache File
	 *	@return		boolean
	 */
	protected function isValidFile( $uri )
	{
		if( !file_exists( $uri ) )
			return FALSE;
		if( !$this->expires )
			return TRUE;
		return !$this->isExpired( $uri, $this->expires );
	}

	/**
	 *	Indicates whether a Cache File is expired.
	 *	@access		protected
	 *	@param		string		$uri			URI of Cache File
	 *	@return		boolean
	 */
	protected function isExpired( $uri, $expires )
	{
		$edge	= time() - $expires;
		clearstatcache();
		return filemtime( $uri ) <= $edge;
	}

	/**
	 *	Removes data pair from storage by its key.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		boolean
	 */
	public function remove( $key )
	{
		if( !$this->has( $key ) )
			return FALSE;
		return @unlink( $this->path.$this->context.$key );
	}

	protected function createFolder( $folder ){
		if( file_exists( $this->path.$this->context.$folder ) )
			return;
		$parts	= explode( "/", $folder );
		if( count( $parts ) > 1 )
			$this->createFolder( implode( '/', array_slice( $parts, 0, -1 ) ) );
		mkdir( $this->path.$this->context.$folder );
	}

	/**
	 *	Adds or updates a data pair.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@param		string		$value		Data pair value
	 *	@param		integer		$expiration	Data life time in seconds or expiration timestamp
	 *	@return		void
	 */
	public function set( $key, $value, $expiration = NULL )
	{
		$uri	= $this->path.$this->context.$key;
		if( dirname( $key ) != '.' )
			$this->createFolder( dirname( $key ) );
		File_Writer::save( $uri, $value );
	}

	/**
	 *	Sets context folder within storage.
	 *	If folder is not existing, it will be created.
	 *	@access		public
	 *	@param		string		$context		Context folder within storage
	 *	@return		void
	 */
	public function setContext( $context ){
		if( !strlen( trim( $context ) ) ){
			$this->context	= NULL;
			return;
		}
		$context	= preg_replace( "@(.+)/$@", "\\1", $context )."/";
		if( !file_exists( $this->path.$context ) )
			mkdir( $this->path.$context );
		$this->context = $context;
	}
}
?>
