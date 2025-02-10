<?php
declare(strict_types=1);

/**
 *	Adapter abstraction, adding some magic to the storage engine instance.
 *	@category		Library
 *	@package		CeusMedia_Cache
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
namespace CeusMedia\Cache\Encoder;


/**
 *	Adapter abstraction, adding some magic to the storage engine instance.
 *	@category		Library
 *	@package		CeusMedia_Cache
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
abstract class AbstractEncoder implements EncoderInterface
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
}