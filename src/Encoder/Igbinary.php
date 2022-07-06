<?php
declare(strict_types=1);

/**
 *	Encoder adapter for igbinary.
 *	@category		Library
 *	@package		CeusMedia_Cache_Encoder
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@link			https://github.com/igbinary/igbinary
 */
namespace CeusMedia\Cache\Encoder;

/**
 *	Encoder adapter for igbinary.
 *	@category		Library
 *	@package		CeusMedia_Cache_Encoder
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@link			https://github.com/igbinary/igbinary
 */
class Igbinary
{
	/**
	 *	Evaluates if needed requirements are met (like: extension installed).
	 *	@access		public
	 *	@static
	 *	@param		boolean		$strict		Flag: throw exception if not supported, default: yes
	 *	@return		boolean
	 *	@throws		SupportException		if PHP extension is not installed in strict mode
	 */
	public static function checkSupport( bool $strict = TRUE ): bool
	{
		if( function_exists( 'igbinary_serialize' ) )
			return TRUE;
		if( !$strict )
			return FALSE;
		throw new SupportException( 'Igbinary not supported (PHP extension not installed)' );
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
		return igbinary_unserialize( $value );
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
		return igbinary_serialize( $value ) ?? '';
	}
}
