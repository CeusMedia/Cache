<?php
/**
 *	....
 *	@category		cmModules
 *	@package		SEA
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 *	@version		$Id$
 */
/**
 *	....
 *	@category		cmModules
 *	@package		SEA
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 *	@version		$Id$
 */
class CMM_SEA_Factory{

	/**	@var		string		$context		Name of context to set on new storage engines */
	protected $context;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		string		$context		Name of context to set on new storage engines
	 *	@return		void
	 */
	public function __construct( $context = NULL ){
		if( $context !== NULL )
			$this->setContext( $context );
	}

	/**
	 *	Sets default context to set on new storage engines.
	 *	@access		public
	 *	@param		string		$context		Name of context to set on new storage engines
	 *	@return		void
	 *	@throws		InvalidArgumentException	if context is not a string
	 */
	public function setContext( $context ){
		if( !is_string( $context ) )
			throw new InvalidArgumentException( 'Context must be a string' );
		$this->context	= $context;
	}

	/**
	 *	Creates and returns new cache storage engine.
	 *	@access		public
	 *	@param		string		$type			Storage type
	 *	@param		string		$resource		Resource for storage engine
	 *	@param		string		$context		Name of context to set on new storage engines
	 *	@param		integer		$expiration		Data life time in seconds or expiration timestamp
	 *	@param		array		$data			Data to store immediately
	 *	@return		CMM_SEA_Adapter_Abstract
	 */
	public function newStorage( $type, $resource = NULL, $context = NULL, $expiration = 0, $data = array() ){
		if( $context === NULL && $this->context !== NULL )
			$context	= $this->context;
		return self::createStorage( $type, $resource, $context, $expiration, $data );
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
	 *	@return		CMM_SEA_Adapter_Abstract
	 */
	static public function createStorage( $type, $resource = NULL, $context = NULL, $expiration = 0, $data = array() ){
		$className	= 'CMM_SEA_Adapter_'.$type;
		if( !class_exists( $className ) )
			throw new RuntimeException( 'Storage engine "'.$type.'" not registered' );
		$reflection	= new ReflectionClass( $className );
		$args		= $resource ? array( $resource ) : array();
		$storage	= $reflection->newInstanceArgs( $args );
		if( $context !== NULL )
			$storage->setContext( $context );
		if( $expiration !== NULL )
			$storage->setExpiration( $expiration );
		if( $data && is_array( $data ) )
			foreach( $data as $key => $value )
				$storate->set( $key, $value );
		return $storage;
	}
}
?>
