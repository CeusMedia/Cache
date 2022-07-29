<?php
declare(strict_types=1);

/**
 *	Encoder adapter for MessagePack.
 *	@category		Library
 *	@package		CeusMedia_Cache_Encoder
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@link			https://github.com/msgpack/msgpack-php
 *	@link			https://msgpack.org/
 */
namespace CeusMedia\Cache\Encoder;

/**
 *	Encoder adapter for MessagePack.
 *	@category		Library
 *	@package		CeusMedia_Cache_Encoder
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@link			https://github.com/msgpack/msgpack-php
 *	@link			https://msgpack.org/
 */
class Msgpack
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
		if( class_exists( '\\Msgpack' ) )
			return TRUE;
		if( !$strict )
			return FALSE;
		throw new SupportException( 'Msgpack not supported (PHP extension not installed)' );
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
		return msgpack_unpack( $value );
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
		return msgpack_pack( $value );
	}
}
