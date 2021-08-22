<?php
/**
 *	Storage implementation using a database table via a PDO connection.
 *	Supports context. Does not support expiration, yet.
 *	@category		Library
 *	@package		CeusMedia_Cache_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 */
namespace CeusMedia\Cache\Adapter;

use CeusMedia\Cache\AbstractAdapter;
use CeusMedia\Cache\AdapterInterface;
use PDO;
use InvalidArgumentException;
use RuntimeException;

/**
 *	Storage implementation using a database table via a PDO connection.
 *	Supports context. Does not support expiration, yet.
 *	@category		Library
 *	@package		CeusMedia_Cache_Adapter
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 *	@todo			implement expiration and cleanup
 */
class Database extends AbstractAdapter implements AdapterInterface
{
	/** @var		string		$tableName		... */
	protected $tableName		= 'cache';

	/**	@var	PDO				$resource		PDO database connection */
	protected $resource;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		array			$resource		List of PDO database connection and table name
	 *	@param		string|NULL		$context		Name of context in table
	 *	@param		integer|NULL	$expiration		Number of seconds until data sets expire
	 *	@return		void
	 */
	public function __construct( $resource, ?string $context = NULL, ?int $expiration = NULL )
	{
		$this->resource		= $resource[0];
		$this->tableName	= $resource[1];
		if( !( $this->resource instanceof PDO ) )
			throw new InvalidArgumentException( 'No PDO database connection set' );
		if( !$this->tableName )
			throw new InvalidArgumentException( 'No table name set' );
		if( $context !== NULL )
			$this->setContext( $context );
		if( $expiration !== NULL )
			$this->setExpiration( $expiration );
	}

	/**
	 *	Removes all data pairs from storage.
	 *	@access		public
	 *	@return		self
	 */
	public function flush(): self
	{
		$query	= 'DELETE FROM '.$this->tableName.' WHERE context="'.$this->context.'"';
		$this->resource->exec( $query );
		return $this;
	}

	/**
	 *	Returns a data pair value by its key or NULL if pair not found.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		mixed
	 */
	public function get( string $key )
	{
		$query	= 'SELECT value FROM '.$this->tableName.' WHERE context="'.$this->context.'" AND hash="'.$key.'"';
		$result	= $this->resource->query( $query );
		if( $result === FALSE )																		//  query was not successful
			throw new RuntimeException( 'Table "'.$this->tableName.'" not found or invalid' );		//  inform about invalid table
		$result	= $result->fetch( PDO::FETCH_OBJ );													//  fetch row object
		if( $result === FALSE )																		//  no row found
			return NULL;																			//  quit with empty result
		return $result->value;																		//  return value
	}

	/**
	 *	Indicates whether a data pair is stored by its key.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		boolean		Result state of operation
	 */
	public function has( string $key ): bool
	{
		$query	= 'SELECT COUNT(value) as count FROM '.$this->tableName.' WHERE context="'.$this->context.'" AND hash="'.$key.'"';
		$result	= $this->resource->query( $query );
		if( $result === FALSE )																		//  query was not successful
			throw new RuntimeException( 'Table "'.$this->tableName.'" not found or invalid' );		//  inform about invalid table
		return (bool) $result->fetch( PDO::FETCH_OBJ )->count;
	}

	/**
	 *	Returns a list of all data pair keys.
	 *	@access		public
	 *	@return		array
	 */
	public function index(): array
	{
		$query	= 'SELECT hash FROM '.$this->tableName.' WHERE context="'.$this->context.'"';
		$result	= $this->resource->query( $query );
		if( $result === FALSE )																		//  query was not successful
			throw new RuntimeException( 'Table "'.$this->tableName.'" not found or invalid' );		//  inform about invalid table
		$list	= array();
		$rows	= $result->fetchAll( PDO::FETCH_OBJ );
		if( $rows !== FALSE )
			foreach( $rows as $row )
				$list[]	= $row->hash;
		return $list;
	}

	/**
	 *	Removes data pair from storage by its key.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@return		boolean		Result state of operation
	 */
	public function remove( string $key ): bool
	{
		$query	= 'DELETE FROM '.$this->tableName.' WHERE context="'.$this->context.'" AND hash="'.$key.'"';
		return (bool) $this->resource->exec( $query );
	}

	/**
	 *	Adds or updates a data pair.
	 *	@access		public
	 *	@param		string		$key		Data pair key
	 *	@param		mixed		$value		Data pair value
	 *	@param		integer		$expiration	Data life time in seconds or expiration timestamp
	 *	@return		boolean		Result state of operation
	 */
	public function set( string $key, $value, int $expiration = NULL ): bool
	{
		if( is_object( $value ) || is_resource( $value ) )
			throw new InvalidArgumentException( 'Value must not be an object or resource' );
		if( $value === NULL || $value === '' )
			return $this->remove( $key );
		if( $this->has( $key ) )
			$query	= 'UPDATE '.$this->tableName.' SET value="'.addslashes( $value ).'", timestamp="'.time().'", expiration='.(int) $expiration.' WHERE context="'.$this->context.'" AND hash="'.$key.'"';
		else
			$query	= 'INSERT INTO '.$this->tableName.' (context, hash, value, timestamp, expiration) VALUES ("'.$this->context.'", "'.$key.'", "'.addslashes( $value ).'", "'.time().'", '.(int) $expiration.')';
		return (bool) $this->resource->exec( $query );
	}
}
