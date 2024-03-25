<?php
declare(strict_types=1);

/**
 *	....
 *	@category		Library
 *	@package		CeusMedia_Cache
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
namespace CeusMedia\Cache;

use InvalidArgumentException;
use Psr\SimpleCache\InvalidArgumentException as InvalidCacheArgumentException;
use ReflectionClass;
use ReflectionException;
use RuntimeException;

/**
 *	....
 *	@category		Library
 *	@package		CeusMedia_Cache
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 */
class SimpleCacheFactory
{
	/**	@var		string|NULL		$context		Name of context to set on new storage engines */
	protected ?string $context		= NULL;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		string|NULL		$context		Name of context to set on new storage engines
	 *	@return		void
	 */
	public function __construct( ?string $context = NULL )
	{
		if( $context !== NULL )
			$this->setContext( $context );
	}

	/**
	 *	Statically creates and returns new cache storage engine.
	 *	@access		public
	 *	@static
	 *	@param		string			$type			Storage type
	 *	@param		mixed|NULL		$resource		Resource for storage engine
	 *	@param		string|NULL		$context		Name of context to set on new storage engines
	 *	@param		integer			$expiration		Data lifetime in seconds or expiration timestamp
	 *	@param		array			$data			Data to store immediately
	 *	@return		SimpleCacheInterface
	 *	@throws		ReflectionException
	 *	@throws		InvalidCacheArgumentException
	 */
	public static function createStorage( string $type, $resource = NULL, string $context = NULL, int $expiration = 0, array $data = [] ): SimpleCacheInterface
	{
		$namespace	= '';
		if( !str_starts_with( $type, "\\" ) ){
			if( str_starts_with( $type, "CeusMedia\\Cache\\Adapter\\" ) )
				$namespace	= "\\";
			else
				$namespace	= "\\CeusMedia\\Cache\\Adapter\\";
		}
//		$namespace	= !str_starts_with( $type, "\\" ) ? "\\CeusMedia\\Cache\\Adapter\\" : '';
		$className	= $namespace.$type;

		if( !class_exists( $className ) )
			throw new RuntimeException( 'Cache adapter "'.$type.'" not registered' );
		$reflection	= new ReflectionClass( $className );
		$args		= [$resource];

		/** @var SimpleCacheInterface $storage */
		$storage	= $reflection->newInstanceArgs( $args );
		if( NULL !== $context  )
			$storage->setContext( $context );
		if( 0 !== $expiration  )
			$storage->setExpiration( $expiration );
		foreach( $data as $key => $value )
			$storage->set( $key, $value );
		return $storage;
	}

	/**
	 *	Creates and returns new cache storage engine.
	 *	@access		public
	 *	@param		string			$type			Storage type
	 *	@param		mixed|NULL		$resource		Resource for storage engine
	 *	@param		string|NULL		$context		Name of context to set on new storage engines
	 *	@param		integer			$expiration		Data lifetime in seconds or expiration timestamp
	 *	@param		array			$data			Data to store immediately
	 *	@return		SimpleCacheInterface
	 *	@throws		ReflectionException
	 *	@throws		InvalidCacheArgumentException
	 */
	public function newStorage( string $type, $resource = NULL, ?string $context = NULL, int $expiration = 0, array $data = [] ): SimpleCacheInterface
	{
		if( $context === NULL && $this->context !== NULL )
			$context	= $this->context;
		return self::createStorage( $type, $resource, $context, $expiration, $data );
	}

	/**
	 *	Sets default context to set on new storage engines.
	 *	@access		public
	 *	@param		string		$context		Name of context to set on new storage engines
	 *	@return		self
	 *	@throws		InvalidArgumentException	if context is not a string
	 */
	public function setContext( string $context ): self
	{
		$this->context	= $context;
		return $this;
	}
}
