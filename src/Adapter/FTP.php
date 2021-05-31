<?php
/**
 *	Storage adapter for files via FTP.
 *	Supports context.
 *	@category		Library
 *	@package		CeusMedia_Cache_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			16.09.2011
 */
namespace CeusMedia\Cache\Adapter;

use CeusMedia\Cache\AbstractAdapter;
use CeusMedia\Cache\AdapterInterface;
use FS_File_Reader as FileReader;
use FS_File_Writer as FileWriter;
use Net_FTP_Client as FtpClient;
use InvalidArgumentException;

/**
 *	Storage adapter for files via FTP.
 *	Supports context.
 *	@category		Library
 *	@package		CeusMedia_Cache_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			16.09.2011
 */
class FTP extends AbstractAdapter implements AdapterInterface
{
	/**	@var		FtpClient		$client		FTP Client */
	protected $client;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		FtpClient|string		$resource		FTP client or FTP access string as [USERNAME][:PASSWORT]@HOST[:PORT]/[PATH]
	 *	@return		void
	 *	@throws		InvalidArgumentException	if neither client object nor access string are valid
	 */
	public function __construct( $resource, string $context = NULL, int $expiration = NULL )
	{
		if( $resource instanceof FtpClient )
			$this->client	= $resource;
		else if( is_string( $resource ) ){
			$matches	= array();
			preg_match_all('/^(([^:]+)(:(.+))?@)?([^\/]+)(:\d+)?\/(.+)?$/', $resource, $matches );
			if( !$matches[0] )
				throw new InvalidArgumentException( 'Invalid FTP resource given' );
			$this->client	= new FtpClient(
				$matches[5][0],																		//  host
				empty( $matches[6][0] ) ? 21 : $matches[6][0],										//  port
				$matches[7][0],																		//  base path
				empty( $matches[2][0] ) ? NULL : $matches[2][0],									//  username
				empty( $matches[4][0] ) ? NULL : $matches[4][0]										//  password
			);
		}
		else
			throw new InvalidArgumentException( 'Invalid FTP resource given' );
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
		foreach( $this->index() as $file )
			$this->remove( $file );
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
		if( !$this->has( $key ) )
			return NULL;
		$tmpFile	= tempnam( './', 'ftp_'.uniqid().'_' );
		$this->client->getFile( $this->context.$key, $tmpFile );
		$content	= FileReader::load( $tmpFile );
		@unlink( $tmpFile );
		return $content;
	}

	/**
	 *	Indicates whether a data pair is stored by its key.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		boolean
	 */
	public function has( string $key ): bool
	{
		return in_array( $key, $this->index() );
	}

	/**
	 *	Returns a list of all data pair keys.
	 *	@access		public
	 *	@return		array
	 */
	public function index(): array
	{
		$list	= array();
		foreach( $this->client->getFileList( $this->context, TRUE ) as $item )
			$list[]	= $item['name'];
		return $list;
	}

	/**
	 *	Removes data pair from storage by its key.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		boolean
	 */
	public function remove( string $key ): bool
	{
		return $this->client->removeFile( $this->context.$key );
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
		$tmpFile	= tempnam( './', 'ftp_'.uniqid().'_' );
		FileWriter::save( $tmpFile, $value );
		$result	= $this->client->putFile( $tmpFile, $this->context.$key );
		@unlink( $tmpFile );
		return $result;
	}

	/**
	 *	Sets context folder within storage.
	 *	If folder is not existing, it will be created.
	 *	@access		public
	 *	@param		string		$context		Context folder within storage
	 *	@return		self
	 */
	public function setContext( string $context ): self
	{
		if( !strlen( trim( $context ) ) ){
			$this->context	= NULL;
			return $this;
		}
//		if( !$this->client->hasFolder( $context ) )
			$this->client->createFolder( $context );
		$context	= preg_replace( "@(.+)/$@", "\\1", $context )."/";
		$this->context = $context;
		return $this;
	}
}
