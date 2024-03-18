<?php
namespace CeusMedia\Cache\Encoder;

interface EncoderInterface
{
	/**
	 *	Evaluates if needed requirements are met (like: extension installed).
	 *	@access		public
	 *	@static
	 *	@param		boolean		$strict		Flag: throw exception if not supported, default: yes
	 *	@return		boolean
	 */
	public static function checkSupport( bool $strict = TRUE ): bool;

	public static function decode( string $content ): mixed;

	public static function encode( mixed $content ): string;
}
