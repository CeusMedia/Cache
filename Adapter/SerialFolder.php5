<?php
/**
 *	....
 *	@category		cmModules
 *	@package		SEA
 *	@extends		CMM_SEA_Adapter_Abstract
 *	@implements		CMM_SEA_Adapter_Interface
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 *	@version		$Id$
 */
/**
 *	....
 *	@category		cmModules
 *	@package		SEA
 *	@extends		CMM_SEA_Adapter_Abstract
 *	@implements		CMM_SEA_Adapter_Interface
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 *	@version		$Id$
 */
class CMM_SEA_Adapter_SerialFolder extends CMM_SEA_Adapter_Abstract implements CMM_SEA_Adapter_Interface
{
	/**	@var		array		$data			Memory Cache */
	protected $data				= array();

	/**	@var		string		$path			Path to Cache Files */
	protected $path;
	
	/**	@var		int			$expires		Cache File Lifetime in Seconds */
	protected $expires			= 0;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		string		$path			Path to Cache Files
	 *	@param		int			$expires		Seconds until Pairs will be expired
	 *	@return		void
	 */
	public function __construct( $resource )
	{
		$resource	.= substr( $resource, -1 ) == "/" ? "" : "/";
		if( !file_exists( $resource ) )
			throw new RuntimeException( 'Path "'.$resource.'" is not existing.' );
		$this->path		= $resource;
	}
	
	/**
	 *	Removes all expired Cache Files.
	 *	@access		public
	 *	@param		int			$expires		Cache File Lifetime in Seconds
	 *	@return		bool
	 */
	public function cleanUp( $expires = 0 )
	{
		$expires	= $expires ? $expires : $this->expires;
		if( !$expires )
			throw new InvalidArgumentException( 'No expire time given or set on construction.' );

		$number	= 0;
		$index	= new DirectoryIterator( $this->path );
		foreach( $index as $entry )
		{
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

	/**
	 *	Removes all data pairs from storage.
	 *	@access		public
	 *	@return		void
	 */
	public function flush()
	{
		$index	= new DirectoryIterator( $this->path );
		foreach( $index as $entry )
			if( !$entry->isDot() && !$entry->isDir() )
				if( substr( $entry->getFilename(), -7 ) == ".serial" )
					@unlink( $entry->getPathname() );
		$this->data	= array();
	}

	/**
	 *	Returns a data pair value by its key or NULL if pair not found.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		mixed
	 */
	public function get( $key )
	{
		$uri		= $this->getUriForKey( $key );
		if( !$this->isValidFile( $uri ) )
			return NULL;
		if( isset( $this->data[$key] ) )
			return $this->data[$key];
		$content	= File_Editor::load( $uri );
		$value		= unserialize( $content );
		$this->data[$key]	= $value;
		return $value;
	}

	/**
	 *	Returns URI of Cache File from its Key.
	 *	@access		protected
	 *	@param		string		$key			Key of Cache File
	 *	@return		string
	 */
	protected function getUriForKey( $key )
	{
		return $this->path.base64_encode( $key ).".serial";
	}

	/**
	 *	Indicates whether a data pair is stored by its key.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		boolean
	 */
	public function has( $key )
	{
		$uri	= $this->getUriForKey( $key );
		return $this->isValidFile( $uri );
	}

	/**
	 *	Returns a list of all data pair keys.
	 *	@access		public
	 *	@return		array
	 *	@todo		implement
	 */
	public function index()
	{
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
	 *	@todo		implement return value
	 */
	public function remove( $key )
	{
		$uri	= $this->getUriForKey( $key );
		unset( $this->data[$key] );
		@unlink( $uri );
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
		$uri		= $this->getUriForKey( $key );
		$this->data[$key]	= $value;
		File_Writer::save( $uri, serialize( $value ) );
	}
}
?>