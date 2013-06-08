<?php
/**
 *	Storage adapter for files via FTP.
 *	Supports context.
 *	@category		cmModules
 *	@package		SEA
 *	@extends		CMM_SEA_Adapter_Abstract
 *	@implements		CMM_SEA_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			16.09.2011
 *	@version		$Id$
 */
/**
 *	Storage adapter for files via FTP.
 *	Supports context.
 *	@category		cmModules
 *	@package		SEA
 *	@extends		CMM_SEA_Adapter_Abstract
 *	@implements		CMM_SEA_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			16.09.2011
 *	@version		$Id$
 */
class CMM_SEA_Adapter_FTP extends CMM_SEA_Adapter_Abstract implements CMM_SEA_Adapter{

	/**	@var		Net_FTP_Client	$client		FTP Client */
	protected $client;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		Net_FTP_Client|string		$resource		FTP client or FTP access string as [USERNAME][:PASSWORT]@HOST[:PORT]/[PATH]
	 *	@return		void
	 *	@throws		InvalidArgumentException	if neither client object nor access string are valid
	 */
	public function __construct( $resource = NULL, $context = NULL, $expiration = NULL ){
		if( $resource instanceof "Net_FTP_Client" )
			$this->client	= $resource;
		else if( is_string( $resource ) ){
			$matches	= array();
			preg_match_all('/^(([^:]+)(:(.+))?@)?([^\/]+)(:\d+)?\/(.+)?$/', $resource, $matches );
			if( !$matches[0] )
				throw new InvalidArgumentException( 'Invalid FTP resource given' );
			$host			= $matches[5][0];
			$port			= empty( $matches[6][0] ) ? 21 : $matches[6][0];
			$path			= $matches[7][0];
			$username		= empty( $matches[2][0] ) ? NULL : $matches[2][0];
			$password		= empty( $matches[4][0] ) ? NULL : $matches[4][0];
			$this->client	= new Net_FTP_Client( $host, $port, $path, $username, $password );
		}
		else
			throw new InvalidArgumentException( 'Invalid FTP resource given' );
		if( $context )
			$this->setContext();
	}

	/**
	 *	Removes all data pairs from storage.
	 *	@access		public
	 *	@return		void
	 */
	public function flush(){
		foreach( $this->index() as $file )
			$this->remove( $file );
	}

	/**
	 *	Returns a data pair value by its key or NULL if pair not found.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		mixed
	 */
	public function get( $key ){
		if( !$this->has( $key ) )
			return NULL;
		$tmpFile	= tempnam( './', 'ftp_'.uniqid().'_' );
		$this->client->getFile( $this->context.$key, $tmpFile );
		$content	= File_Reader::load( $tmpFile );
		@unlink( $tmpFile );
		return $content;
	}

	/**
	 *	Indicates whether a data pair is stored by its key.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		boolean
	 */
	public function has( $key ){
		return in_array( $key, $this->index() );
	}

	/**
	 *	Returns a list of all data pair keys.
	 *	@access		public
	 *	@return		array
	 */
	public function index(){
		$list	= array();	
		foreach( $this->client->getFileList( $this->context, TRUE ) as $item )
			$list[]	= $item['name'];
		return $list;
	}

	/**
	 *	Removes data pair from storage by its key.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		void
	 */
	public function remove( $key ){
		$this->client->removeFile( $this->context.$key );
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
		$tmpFile	= tempnam( './', 'ftp_'.uniqid().'_' );
		File_Writer::save( $tmpFile, $value );
		$this->client->putFile( $tmpFile, $this->context.$key );
		@unlink( $tmpFile );
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
		if( !file_exists( $this->path.$context ) )
			$this->client->createFolder( $context );
		$context	= preg_replace( "@(.+)/$@", "\\1", $context )."/";
		$this->context = $context;
	}
}
?>
