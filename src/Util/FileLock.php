<?php
/**
 *	....
 *	@category		Library
 *	@package		CeusMedia_Cache_Util
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
namespace CeusMedia\Cache\Util;

/**
 *	....
 *	@category		Library
 *	@package		CeusMedia_Cache_Util
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@todo			code doc
 */
class FileLock
{
	/**	@var	string		$fileName */
	protected $fileName;

	/**	@var	integer		$expiration */
	protected $expiration;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		string		$fileName		...
	 *	@param		integer		$expiration		...
	 *	@return		void
	 */
	public function __construct( string $fileName, int $expiration = 60 )
	{
		$this->fileName		= $fileName;
		$this->expiration	= abs( $expiration );
	}

	/**
	 *	...
	 *	@access		public
	 *	@return		boolean
	 */
	public function isLocked(): bool
	{
		if( file_exists( $this->fileName ) ){
			if( filemtime( $this->fileName ) >= time() - $this->expiration )
				return TRUE;
			unlink( $this->fileName );
		}
		return FALSE;
	}

	/**
	 *	...
	 *	@access		public
	 *	@return		void
	 */
	public function lock()
	{
		while( $this->isLocked() );
		touch( $this->fileName );
	}

	/**
	 *	...
	 *	@access		public
	 *	@return		void
	 */
	public function unlock()
	{
		if( $this->isLocked() )
			unlink( $this->fileName );
	}
}
