<?php /** @noinspection PhpMultipleClassDeclarationsInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace CeusMedia\CacheTest\Integration\Adapter;

use CeusMedia\Cache\Adapter\Database as DatabaseAdapter;
use CeusMedia\Cache\SimpleCacheException;
use InvalidArgumentException;
use PDO;

class DatabaseTest extends AdapterTestCase
{
	protected string $path;
	protected string $filePath;

	/** @noinspection PhpUnhandledExceptionInspection */
	/** @noinspection PhpComposerExtensionStubsInspection */
	public function test_construct(): void
	{
		$dbFile		= 'not_existing.sqlite';
		$pdo		= new PDO( 'sqlite:'.$dbFile );
		$resource	= [$pdo, 'test'];
		$adapter	= new DatabaseAdapter( $resource, 'context:', 120 );

		$this->adapter->setMultiple( ['key1' => 'value1'] );

		self::assertEquals( 'context:', $adapter->getContext() );
		self::assertEquals( 120, $adapter->getExpiration() );
		unlink($dbFile);
	}

	public function test_construct_withException_noPdo(): void
	{
		$this->expectException( InvalidArgumentException::class );
		$resource	= ['notPDO', 'test'];
		$adapter	= new DatabaseAdapter( $resource, 'context:', 120 );
	}

	/** @noinspection PhpComposerExtensionStubsInspection */
	public function test_construct_withException_noTableName(): void
	{
		$this->expectException( InvalidArgumentException::class );
		$pdo		= new PDO( 'sqlite:not_existing.sqlite' );
		$adapter	= new DatabaseAdapter( [$pdo, NULL], 'context:', 120 );
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function test_clear(): void
	{
		$this->adapter->set( 'key1', 'value1' );
		self::assertEquals( 'value1', $this->adapter->get( 'key1') );
		self::assertTrue( $this->adapter->clear() );
		self::assertNull( $this->adapter->get( 'key1') );
		self::assertTrue( $this->adapter->clear() );

//		self::assertEquals( ['a1', 'b1'], $this->adapter->index() );
		$this->adapter->setMultiple( ['a1' => 'value1', 'b1' => 'value2'] );
		$this->adapter->setContext( 'ctx1:' );
		$this->adapter->setMultiple( ['c1' => 'value3', 'd1' => 'value4'] );
		self::assertEquals( 'value3', $this->adapter->get( 'c1') );
		self::assertTrue( $this->adapter->clear() );
		self::assertNull( $this->adapter->get( 'c1') );
		self::assertNull( $this->adapter->get( 'd1') );
		$this->adapter->setContext();
		self::assertEquals( 'value1', $this->adapter->get( 'a1') );
	}

	public function test_delete(): void
	{
		parent::testDelete();
	}

	public function test_delete_byMagic(): void
	{
		parent::testDeleteByMagic();
	}

	public function test_delete_byOffset(): void
	{
		parent::testDeleteByOffset();
	}

	public function test_delete_withException1(): void
	{
		parent::testDeleteWithExceptionInvalidKey();
	}

	public function test_deleteMultiple(): void
	{
		parent::testDeleteMultiple();
	}

	public function test_get(): void
	{
		parent::testGet();
	}

	public function test_get_byMagic(): void
	{
		parent::testGetByMagic();
	}

	public function test_get_byOffset(): void
	{
		parent::testGetByOffset();
	}

	public function test_get_withDefault(): void
	{
		parent::testGetWithDefault();
	}

	public function test_get_withException1(): void
	{
		parent::testGetWithExceptionInvalidKey();
	}

	public function test_get_withException2(): void
	{
		$this->expectException( SimpleCacheException::class );
		$filePath	= $this->pathTests.'data/test.sqlite';
		$pdo	= new PDO( 'sqlite:'.$filePath );
		$adapter	= new DatabaseAdapter( [$pdo, 'invalidTableName'], NULL, 120 );
		$adapter->get( 'notExisting' );
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function test_getMultiple(): void
	{
		self::assertEquals( [], $this->adapter->index() );

		$data1	= ['key1' => 'value1', 'key2' => 'value2'];
		$this->adapter->setMultiple( $data1 );

		self::assertTrue( $this->adapter->setMultiple( $data1 ) );
		self::assertEqualsCanonicalizing( array_keys( $data1 ), $this->adapter->index() );
		self::assertEquals( $data1, $this->adapter->getMultiple( array_keys( $data1 ) ) );

		$this->adapter->setContext( 'ctx1:' );

		$data2	= ['key3' => 'value3', 'key4' => 'value4'];
		$this->adapter->setMultiple( $data2 );

		self::assertTrue( $this->adapter->setMultiple( $data2 ) );
		self::assertEqualsCanonicalizing( array_keys( $data2 ), $this->adapter->index() );
		self::assertEquals( $data2, $this->adapter->getMultiple( array_keys( $data2 ) ) );
	}

	public function test_has(): void
	{
		parent::testHas();
	}

	public function test_has_byMagic(): void
	{
		parent::testHasByMagic();
	}

	public function test_has_byOffset(): void
	{
		parent::testHasByOffset();
	}

	public function test_has_withException2(): void
	{
		$this->expectException( SimpleCacheException::class );
		$filePath	= $this->pathTests.'data/test.sqlite';
		$pdo	= new PDO( 'sqlite:'.$filePath );
		$adapter	= new DatabaseAdapter( [$pdo, 'invalidTableName'], NULL, 120 );
		$adapter->has( 'notExisting' );
	}

	public function test_index(): void
	{
		parent::testIndex();
	}

	public function test_index_withException(): void
	{
		$this->expectException( SimpleCacheException::class );
		$filePath	= $this->pathTests.'data/test.sqlite';
		$pdo	= new PDO( 'sqlite:'.$filePath );
		$adapter	= new DatabaseAdapter( [$pdo, 'invalidTableName'], NULL, 120 );
		$adapter->index();
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function test_set(): void
	{
//		self::assertNull( $this->adapter->get( 'key1' ) );

		$this->adapter->set( 'key1', 'value1' );
		self::assertEquals( 'value1', $this->adapter->get( 'key1' ) );

		$this->adapter->set( 'key2', 'value2' );
		self::assertEquals( 'value2', $this->adapter->get( 'key2' ) );
		self::assertEqualsCanonicalizing( ['key1', 'key2'], $this->adapter->index() );

		$this->adapter->setContext( 'ctx' );
		$this->adapter->set( 'key3', 'value3' );
		self::assertEquals( 'value3', $this->adapter->get( 'key3' ) );
		self::assertNull( $this->adapter->get( 'key2' ) );
	}

	public function test_setByMagic(): void
	{
		parent::testSetByMagic();
	}

	public function test_setByOffset(): void
	{
		parent::testSetByOffset();
	}

	public function test_set_withException1(): void
	{
		parent::testSetWithExceptionInvalidKey();
	}

	public function test_set_withException2(): void
	{
		$this->expectException( SimpleCacheException::class );
		$filePath	= $this->pathTests.'data/test.sqlite';
		$pdo	= new PDO( 'sqlite:'.$filePath );
		$adapter	= new DatabaseAdapter( [$pdo, 'invalidTableName'], NULL, 120 );
		$adapter->set( 'notExisting', 'notWorking' );
	}

	public function test_setMultiple(): void
	{
		parent::testSetMultiple();
	}

	public function test_setEncoder(): void
	{
		parent::testSetEncoder();
	}

	public function test_setEncoder_withException(): void
	{
		parent::testSetEncoder_withException();
	}

	//  --  PROTECTED  --  //

	/** @noinspection PhpUnhandledExceptionInspection */
	protected function setUp(): void
	{
		$filePath	= $this->pathTests.'data/test.sqlite';
		$pdo	= new PDO( 'sqlite:'.$filePath );
		$this->adapter	= new DatabaseAdapter( [$pdo, 'cache'], NULL, 120 );
	}

	protected function tearDown(): void
	{
		$this->adapter->clear();
		$this->adapter->setContext();
		$this->adapter->clear();
		@unlink( 'not_existing.sqlite' );
	}
}