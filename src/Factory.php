<?php
/**
 *	....
 *	@category		Library
 *	@package		CeusMedia_Cache
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 */
namespace CeusMedia\Cache;

use InvalidArgumentException;
use ReflectionClass;
use RuntimeException;

/**
 *	....
 *	@category		Library
 *	@package		CeusMedia_Cache
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 */
class Factory
{
	/**	@var		string		$context		Name of context to set on new storage engines */
	protected $context;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		string		$context		Name of context to set on new storage engines
	 *	@return		void
	 */
	public function __construct( string $context = NULL )
	{
		if( $context !== NULL )
			$this->setContext( $context );
	}

	/**
	 *	Statically creates and returns new cache storage engine.
	 *	@access		public
	 *	@static
	 *	@param		string		$type			Storage type
	 *	@param		string		$resource		Resource for storage engine
	 *	@param		string		$context		Name of context to set on new storage engines
	 *	@param		integer		$expiration		Data life time in seconds or expiration timestamp
	 *	@param		array		$data			Data to store immediately
	 *	@return		AbstractAdapter
	 */
	public static function createStorage( string $type, $resource = NULL, string $context = NULL, int $expiration = 0, array $data = array() ): AbstractAdapter
	{
		$className	= "\\CeusMedia\\Cache\\Adapter\\".$type;
		if( !class_exists( $className ) )
			throw new RuntimeException( 'Storage engine "'.$type.'" not registered' );
		$reflection	= new ReflectionClass( $className );
		$args		= [$resource];

		/** @var AbstractAdapter $storage */
		$storage	= $reflection->newInstanceArgs( $args );
		if( $context !== NULL )
			$storage->setContext( $context );
		if( $expiration !== NULL )
			$storage->setExpiration( $expiration );
		if( $data && is_array( $data ) )
			foreach( $data as $key => $value )
				$storage->set( $key, $value );
		return $storage;
	}

	/**
	 *	Creates and returns new cache storage engine.
	 *	@access		public
	 *	@param		string		$type			Storage type
	 *	@param		string		$resource		Resource for storage engine
	 *	@param		string		$context		Name of context to set on new storage engines
	 *	@param		integer		$expiration		Data life time in seconds or expiration timestamp
	 *	@param		array		$data			Data to store immediately
	 *	@return		AbstractAdapter
	 */
	public function newStorage( string $type, $resource = NULL, string $context = NULL, int $expiration = 0, array $data = array() ): AbstractAdapter
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
		if( !is_string( $context ) )
			throw new InvalidArgumentException( 'Context must be a string' );
		$this->context	= $context;
		return $this;
	}
}
