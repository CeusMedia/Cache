<?php
declare(strict_types=1);

/**
 *	Passive encoder adapter.
 *	@category		Library
 *	@package		CeusMedia_Cache_Encoder
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
namespace CeusMedia\Cache\Encoder;

/**
*	Passive encoder adapter.
 *	@category		Library
 *	@package		CeusMedia_Cache_Encoder
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
class Noop extends AbstractEncoder implements EncoderInterface
{
	/**
	 *	Decode value, coming from cache storage.
	 *	@access		public
	 *	@static
	 *	@param		string		$content		Encoded value
	 *	@return		string		Decoded value
	 */
	public static function decode( string $content ): string
	{
		return $content;
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
		return $content;
	}
}
