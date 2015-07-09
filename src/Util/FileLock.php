<?php
/**
 *	....
 *	@category		Library
 *	@package		CeusMedia_Cache_Util
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 */
namespace CeusMedia\Cache\Util;
/**
 *	....
 *	@category		Library
 *	@package		CeusMedia_Cache_Util
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 *	@todo			code doc
 */
class FileLock{

	protected $fileName;
	protected $expiration;

	public function __construct( $fileName, $expiration = 60 ){
		$this->fileName	= $fileName;
		$this->expiration     = abs( (int) $expiration );
	}

	public function lock(){
		while( $this->isLocked() );
		touch( $this->fileName );
	}

	public function unlock(){
		if( $this->isLocked() )
			unlink( $this->fileName );
	}

	public function isLocked(){
		if( file_exists( $this->fileName ) ){
			if( filemtime( $this->fileName ) >= time() - $this->expiration )
				return TRUE;
			unlink( $this->fileName );
		}
		return FALSE;
	}
}
