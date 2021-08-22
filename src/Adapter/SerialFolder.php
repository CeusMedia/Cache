<?php
/**
 *	....
 *	@category		Library
 *	@package		CeusMedia_Cache_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 */
namespace CeusMedia\Cache\Adapter;

use CeusMedia\Cache\AbstractAdapter;
use CeusMedia\Cache\AdapterInterface;
use DirectoryIterator;
use RuntimeException;
use InvalidArgumentException;
use FS_Folder_Editor as FolderEditor;
use FS_File_Editor as FileEditor;

/**
 *	....
 *	@category		Library
 *	@package		CeusMedia_Cache_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 */
class SerialFolder extends AbstractAdapter implements AdapterInterface
{
	/**	@var		array		$data			Memory Cache */
	protected $data				= array();

	/**	@var		string		$path			Path to Cache Files */
	protected $path;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		string			$resource		Path to Cache Files
	 *	@param		string|NULL		$context		Internal prefix for keys for separation
	 *	@param		integer|NULL	$expiration		Seconds until Pairs will be expired
	 *	@return		void
	 */
	public function __construct( $resource, ?string $context = NULL, ?int $expiration = NULL )
	{
		if( is_null( $resource ) )
			throw new RuntimeException( 'Path to folder must be given as resource' );
		$resource	.= substr( $resource, -1 ) == "/" ? "" : "/";
		if( !file_exists( $resource ) ){
			FolderEditor::createFolder( $resource, 0770 );
//			throw new RuntimeException( 'Path "'.$resource.'" is not existing' );
		}
		$this->path		= $resource;
		if( $context !== NULL )
			$this->setContext( $context );
		if( $expiration !== NULL )
			$this->setExpiration( $expiration );
	}

	/**
	 *	Removes all expired Cache Files.
	 *	@access		public
	 *	@param		int			$expires		Cache File Lifetime in Seconds
	 *	@return		integer
	 */
	public function cleanUp( $expires = 0 ){
		$expires	= $expires ? $expires : $this->expiration;
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
			if( $this->isExpired( $pathName ) )
				$number	+= (int) @unlink( $pathName );
		}
		return $number;
	}

	/**
	 *	Removes all data pairs from storage.
	 *	@access		public
	 *	@return		self
	 */
	public function flush(): self
	{
		$index	= new DirectoryIterator( $this->path );
		foreach( $index as $entry )
			if( !$entry->isDot() && !$entry->isDir() )
				if( substr( $entry->getFilename(), -7 ) == ".serial" )
					@unlink( $entry->getPathname() );
		$this->data	= array();
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
		$uri		= $this->getUriForKey( $key );
		if( !$this->isValidFile( $uri ) )
			return NULL;
		if( isset( $this->data[$key] ) )
			return $this->data[$key];
		$content	= FileEditor::load( $uri );
		$value		= unserialize( $content );
		$this->data[$key]	= $value;
		return $value;
	}

	/**
	 *	Indicates whether a data pair is stored by its key.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		boolean
	 */
	public function has( string $key ): bool
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
	public function index(): array
	{
		return [];
	}

	/**
	 *	Removes data pair from storage by its key.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		boolean
	 *	@todo		implement return value
	 */
	public function remove( string $key ): bool
	{
		$uri	= $this->getUriForKey( $key );
		unset( $this->data[$key] );
		@unlink( $uri );
		return TRUE;
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
		$uri		= $this->getUriForKey( $key );
		$this->data[$key]	= $value;
		return (bool) FileEditor::save( $uri, serialize( $value ) );
	}

	//  --  PROTECTED  --  //

	/**
	 *	Returns URI of Cache File from its Key.
	 *	@access		protected
	 *	@param		string		$key			Key of Cache File
	 *	@return		string
	 */
	protected function getUriForKey( string $key ): string
	{
		return $this->path.base64_encode( $key ).".serial";
	}

	/**
	 *	Indicates whether a Cache File is expired.
	 *	@access		protected
	 *	@param		string		$uri			URI of Cache File
	 *	@return		boolean
	 */
	protected function isExpired( string $uri): bool
	{
		if( !file_exists( $uri ) )
			return FALSE;
		if( !$this->expiration )
			return TRUE;
		$edge	= time() - $this->expiration;
		clearstatcache();
		return filemtime( $uri ) <= $edge;
	}

	/**
	 *	Indicates whether a Cache File is existing and not expired.
	 *	@access		protected
	 *	@param		string		$uri			URI of Cache File
	 *	@return		boolean
	 */
	protected function isValidFile( string $uri ): bool
	{
		return !$this->isExpired( $uri );
	}
}
