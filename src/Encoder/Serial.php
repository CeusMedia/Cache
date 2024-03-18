<?php
declare(strict_types=1);

/**
 *	Encoder adapter for PHP serialize.
 *	@category		Library
 *	@package		CeusMedia_Cache_Encoder
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
namespace CeusMedia\Cache\Encoder;

/**
 *	Encoder adapter for PHP serialize.
 *	@category		Library
 *	@package		CeusMedia_Cache_Encoder
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
class Serial extends AbstractEncoder implements EncoderInterface
{
	/**
	 *	Decode value, coming from cache storage.
	 *	@access		public
	 *	@static
	 *	@param		string		$content		Encoded value
	 *	@return		mixed		Decoded value
	 */
	public static function decode( string $content ): mixed
	{
		return unserialize( $content );
	}

	/**
	 *	Encode value, going into cache storage.
	 *	@access		public
	 *	@static
	 *	@param		mixed		$content		Decoded value
	 *	@return		string		Encoded value
	 */
	public static function encode( mixed $content ): string
	{
		return serialize( $content );
	}
}
