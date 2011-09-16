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
	 *	@param		string		$type		Storage type
	 *	@param		string		$resource	Resource for storage engine
	 *	@param		array		$data		Data to store immediately
	 *	@return		CMM_SEA_Adapter_Abstract
	 */
	public function newStorage( $type, $resource = NULL, $data = NULL ){
		$className	= 'CMM_SEA_Adapter_'.$type;
		if( !class_exists( $className ) )
			throw new RuntimeException( 'Storage engine "'.$type.'" not registered' );
		$reflection	= new ReflectionClass( $className );
		if( $resource )
			$storage	= $reflection->newInstanceArgs( array( $resource ) );
		else
			$storage	= $reflection->newInstance();
		if( $this->context )
			$storage->setContext( $this->context );
		if( $data && is_array( $data ) )
			foreach( $data as $key => $value )
				$storate->set( $key, $value );
		return $storage;
	}
}
?>
