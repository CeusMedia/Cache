<?php
/** @noinspection PhpMultipleClassDeclarationsInspection */
declare(strict_types=1);

/**
 *	Encoder adapter for JSON.
 *	@category		Library
 *	@package		CeusMedia_Cache_Encoder
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
namespace CeusMedia\Cache\Encoder;

use JsonException;

/**
 *	Encoder adapter for JSON.
 *	@category		Library
 *	@package		CeusMedia_Cache_Encoder
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
class JSON extends AbstractEncoder implements EncoderInterface
{
	/**
	 *	Decode value, coming from cache storage.
	 *	@access		public
	 *	@static
	 *	@param		string		$content		Encoded value
	 *	@return		mixed		Decoded value
	 *	@throws		JsonException				if decoding JSON failed
	 */
	public static function decode( string $content ): mixed
	{
		return json_decode( $content, TRUE, 512, JSON_THROW_ON_ERROR );
	}

	/**
	 *	Encode value, going into cache storage.
	 *	@access		public
	 *	@static
	 *	@param		mixed		$content		Decoded value
	 *	@return		string		Encoded value
	 *	@throws		JsonException				if encoding JSON failed
	 */
	public static function encode( mixed $content ): string
	{
		return json_encode( $content, JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR );
	}
}
