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
use FS_File_Editor as FileEditor;
use FS_Folder_Editor as FolderEditor;
use FS_Folder_RecursiveIterator as RecursiveFolderIterator;
use DirectoryIterator;
use InvalidArgumentException;

/**
 *	....
 *	Supports context.
 *	@category		Library
 *	@package		CeusMedia_Cache_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 */
class Folder extends AbstractAdapter implements AdapterInterface
{
	/**	@var		string		$path			Path to Cache Files */
	protected $path;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		string			$resource		Path name of folder for cache files, eg. 'cache/'
	 *	@param		string|NULL		$context		Internal prefix for keys for separation
	 *	@param		integer|NULL	$expiration		Data life time in seconds or expiration timestamp
	 *	@return		void
	 */
	public function __construct( $resource, ?string $context = NULL, ?int $expiration = NULL )
	{
		$resource	= preg_replace( "@(.+)/$@", "\\1", $resource )."/";
		if( !file_exists( $resource ) )
			FolderEditor::createFolder( $resource, 0770 );
		$this->path		= $resource;
		if( $context !== NULL )
			$this->setContext( $context );
		if( $expiration !== NULL )
			$this->setExpiration( $expiration );
	}

	/**
	 *	Removes all expired Cache Files.
	 *	@access		public
	 *	@param		integer		$expires		Cache File Lifetime in Seconds
	 *	@return		integer
	 */
	public function cleanUp( int $expires = 0 ): int
	{
		$expires	= $expires ? $expires : $this->expiration;
		if( !$expires )
			throw new InvalidArgumentException( 'No expire time given or set on construction.' );

		$number	= 0;
		$index	= new DirectoryIterator( $this->path.$this->context );
		foreach( $index as $entry ){
			if( $entry->isDot() || $entry->isDir() )
				continue;
			$pathName	= $entry->getPathname();
			if( substr( $pathName, -7 ) !== ".serial" )												//  @todo: why ?
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
		$index	= new DirectoryIterator( $this->path.$this->context );
		foreach( $index as $entry ){
			if( $entry->isDot() )
				continue;
			if( $entry->isDir() )
				$this->rrmdir( $entry->getPathname() );
			else
				@unlink( $entry->getPathname() );
		}
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
		$uri		= $this->path.$this->context.$key;
		if( !$this->isValidFile( $uri ) )
			return NULL;
		return FileEditor::load( $uri );
	}

	/**
	 *	Indicates whether a data pair is stored by its key.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		boolean
	 */
	public function has( string $key ): bool
	{
		return $this->isValidFile( $this->path.$this->context.$key );
	}

	/**
	 *	Returns a list of all data pair keys.
	 *	@access		public
	 *	@return		array
	 */
	public function index(): array
	{
		$list	= array();
		$index	= new RecursiveFolderIterator( $this->path.$this->context, TRUE, FALSE, FALSE );
		$length	= strlen( $this->path.$this->context );
		foreach( $index as $entry ){
			$name	= str_replace( '\\', '/', $entry->getPathname() );
			$list[]	= substr( $name, $length );
		}
		ksort( $list );
		return $list;
	}

	/**
	 *	Removes data pair from storage by its key.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		boolean		Result state of operation
	 */
	public function remove( string $key ): bool
	{
		if( !$this->has( $key ) )
			return FALSE;
		return @unlink( $this->path.$this->context.$key );
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
		if( is_object( $value ) || is_resource( $value ) )
			throw new InvalidArgumentException( 'Value must not be an object or resource' );
		$uri	= $this->path.$this->context.$key;
		if( dirname( $key ) != '.' )
			$this->createFolder( dirname( $key ) );
		return (bool) FileEditor::save( $uri, $value );
	}

	/**
	 *	Sets context folder within storage.
	 *	If folder is not existing, it will be created.
	 *	@access		public
	 *	@param		string|NULL		$context		Context folder within storage
	 *	@return		self
	 */
	public function setContext( ?string $context = NULL ): self
	{
		if( $context === NULL || !strlen( trim( $context ) ) ){
			$this->context	= NULL;
		}
		else {
			$context	= preg_replace( "@(.+)/$@", "\\1", $context )."/";
			if( !file_exists( $this->path.$context ) )
				FolderEditor::createFolder( $this->path.$context, 0770 );
			$this->context = $context;
		}
		return $this;
	}

	//  --  PROTECTED  --  //

	/**
	 *	...
	 *	@access		protected
	 *	@param		string		$folder			...
	 *	@return		void
	 */
	protected function createFolder( string $folder )
	{
		if( file_exists( $this->path.$this->context.$folder ) )
			return;
		$parts	= explode( "/", $folder );
		if( count( $parts ) > 1 )
			$this->createFolder( implode( '/', array_slice( $parts, 0, -1 ) ) );
		mkdir( $this->path.$this->context.$folder );
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

	/**
	 *	Indicates whether a Cache File is expired.
	 *	@access		protected
	 *	@param		string		$uri			URI of Cache File
	 *	@return		boolean
	 */
	protected function isExpired( string $uri ): bool
	{
		if( !$this->expiration )
			return FALSE;
		if( !file_exists( $uri ) )
			return TRUE;
		$edge	= time() - $this->expiration;
		clearstatcache();
		return filemtime( $uri ) <= $edge;
	}

	/**
	 *	Removes folder and its files recursively.
	 *	@access		protected
	 *	@param		string		$folder		Path name of folder to remove
	 *	@return		void
	 */
	protected function rrmdir( string $folder )
	{
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
}
