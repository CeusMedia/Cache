<?php
declare(strict_types=1);

/**
 *	Encoder adapter for JSON.
 *	@category		Library
 *	@package		CeusMedia_Cache_Encoder
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
namespace CeusMedia\Cache\Encoder;

/**
 *	Encoder adapter for JSON.
 *	@category		Library
 *	@package		CeusMedia_Cache_Encoder
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
class JSON
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
	public function decode( string $value )
	{
		return json_decode( $value, TRUE );
	}

	/**
	 *	Encode value, going into cache storage.
	 *	@access		public
	 *	@static
	 *	@param		mixed		$value		Decoded value
	 *	@return		string		Encoded value
	 */
	public function encode( $value ): string
	{
		return (string) json_encode( $value, JSON_PRETTY_PRINT );
	}
}
