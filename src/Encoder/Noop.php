<?php
declare(strict_types=1);

/**
 *	Pasive encoder adapter.
 *	@category		Library
 *	@package		CeusMedia_Cache_Encoder
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
namespace CeusMedia\Cache\Encoder;

/**
*	Pasive encoder adapter.
 *	@category		Library
 *	@package		CeusMedia_Cache_Encoder
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
class Noop
{
	/**
	*	Evaluates if needed requirements are met (like: extension installed).
	 *	@access		public
	 *	@static
	 *	@param		boolean		$strict		Flag: throw exception if not supported, default: yes
	 *	@return		boolean
	 */
	public static function checkSupport( bool $strict = TRUE ): bool
	{
		return TRUE;
	}

	/**
	*	Decode value, coming from cache storage.
	 *	@access		public
	 *	@static
	 *	@param		string		$value		Encoded value
	 *	@return		mixed		Decoded value
	 */
	public static function decode( string $value )
	{
		return $value;
	}

	/**
	 *	Encode value, going into cache storage.
	 *	@access		public
	 *	@static
	 *	@param		mixed		$value		Decoded value
	 *	@return		string		Encoded value
	 */
	public static function encode( $value ): string
	{
		return $value;
	}
}
