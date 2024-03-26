<?php /** @noinspection PhpComposerExtensionStubsInspection */

/** @noinspection PhpMultipleClassDeclarationsInspection */

namespace CeusMedia\CacheTest\Integration\Adapter;

use CeusMedia\Cache\Adapter\Memcache as MemcacheAdapter;
use CeusMedia\Cache\Adapter\Noop as NoopAdapter;

class MemcacheTest extends AdapterTestCase
{
	protected string $path;
	protected string $filePath;
	protected bool $supported = false;

	public function test_construct(): void
	{
		if( !$this->supported )
			self::markTestSkipped( 'Memcache extension not available' );
		$adapter	= new MemcacheAdapter( NULL, 'context', 120 );

		$this->adapter->setMultiple( ['key1' => 'value1'] );

		self::assertEquals( 'context:', $adapter->getContext() );
		self::assertEquals( 120, $adapter->getExpiration() );
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function test_clear(): void
	{
		if( !$this->supported )
			self::markTestSkipped( 'Memcache extension not available' );
		$this->adapter->set( 'key1', 'value1' );
		self::assertEquals( 'value1', $this->adapter->get( 'key1') );
		self::assertTrue( $this->adapter->clear() );
		self::assertNull( $this->adapter->get( 'key1') );
		self::assertTrue( $this->adapter->clear() );

//		self::assertEquals( ['a1', 'b1'], $this->adapter->index() );
		$this->adapter->setMultiple( ['a1' => 'value1', 'b1' => 'value2'] );
		$this->adapter->setContext( 'ctx' );
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
		if( !$this->supported )
			self::markTestSkipped( 'Memcache extension not available' );
		parent::testDelete();
	}

	public function test_delete_byMagic(): void
	{
		if( !$this->supported )
			self::markTestSkipped( 'Memcache extension not available' );
		parent::testDeleteByMagic();
	}

	public function test_delete_byOffset(): void
	{
		if( !$this->supported )
			self::markTestSkipped( 'Memcache extension not available' );
		parent::testDeleteByOffset();
	}

	public function test_delete_withException1(): void
	{
		if( !$this->supported )
			self::markTestSkipped( 'Memcache extension not available' );
		parent::testDeleteWithExceptionInvalidKey();
	}

	public function test_deleteMultiple(): void
	{
		if( !$this->supported )
			self::markTestSkipped( 'Memcache extension not available' );
		parent::testDeleteMultiple();
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function test_get(): void
	{
		if( !$this->supported )
			self::markTestSkipped( 'Memcache extension not available' );
//		self::assertNull( $this->adapter->get( 'key1' ) );

		$this->adapter->setMultiple( ['key1' => 'value1'] );
		self::assertEquals( 'value1', $this->adapter->get( 'key1' ) );

		$this->adapter->setMultiple( ['key1' => 'value1', 'key2' => 'value2'] );
		self::assertEquals( 'value1', $this->adapter->get( 'key1' ) );
		self::assertEquals( 'value2', $this->adapter->get( 'key2' ) );

		self::assertNull( $this->adapter->get( 'notExistingKey' ) );

		$this->adapter->setContext( 'ctx' );
		$this->adapter->setMultiple( ['key3' => 'value3', 'key4' => 'value4'] );
//		self::assertEquals( ['key3', 'key4'], $this->adapter->index() );
		self::assertEquals( 'value3', $this->adapter->get( 'key3' ) );

		self::assertNull( $this->adapter->get( 'notExistingKey' ) );
	}

	public function test_get_byMagic(): void
	{
		if( !$this->supported )
			self::markTestSkipped( 'Memcache extension not available' );
		parent::testGetByMagic();
	}

	public function test_get_byOffset(): void
	{
		if( !$this->supported )
			self::markTestSkipped( 'Memcache extension not available' );
		parent::testGetByOffset();
	}

	public function test_get_withDefault(): void
	{
		if( !$this->supported )
			self::markTestSkipped( 'Memcache extension not available' );
		parent::testGetWithDefault();
	}

	public function test_get_withException1(): void
	{
		if( !$this->supported )
			self::markTestSkipped( 'Memcache extension not available' );
		parent::testGetWithExceptionInvalidKey();
	}


	/** @noinspection PhpUnhandledExceptionInspection */
	public function test_getMultiple(): void
	{
		if( !$this->supported )
			self::markTestSkipped( 'Memcache extension not available' );
		self::assertEquals( [], $this->adapter->index() );

		$data1	= ['key1' => 'value1', 'key2' => 'value2'];
		$this->adapter->setMultiple( $data1 );

		self::assertTrue( $this->adapter->setMultiple( $data1 ) );
		self::assertEqualsCanonicalizing( array_keys( $data1 ), $this->adapter->index() );
		self::assertEquals( $data1, $this->adapter->getMultiple( array_keys( $data1 ) ) );

		$this->adapter->setContext( 'ctx' );

		$data2	= ['key3' => 'value3', 'key4' => 'value4'];
		$this->adapter->setMultiple( $data2 );

		self::assertTrue( $this->adapter->setMultiple( $data2 ) );
		self::assertEqualsCanonicalizing( array_keys( $data2 ), $this->adapter->index() );
		self::assertEquals( $data2, $this->adapter->getMultiple( array_keys( $data2 ) ) );
	}

	public function test_has(): void
	{
		if( !$this->supported )
			self::markTestSkipped( 'Memcache extension not available' );
		parent::testHas();
	}

	public function test_has_byMagic(): void
	{
		if( !$this->supported )
			self::markTestSkipped( 'Memcache extension not available' );
		parent::testHasByMagic();
	}

	public function test_has_byOffset(): void
	{
		if( !$this->supported )
			self::markTestSkipped( 'Memcache extension not available' );
		parent::testHasByOffset();
	}

	public function test_index(): void
	{
		if( !$this->supported )
			self::markTestSkipped( 'Memcache extension not available' );
		parent::testIndex();
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function test_set(): void
	{
		if( !$this->supported )
			self::markTestSkipped( 'Memcache extension not available' );
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
		if( !$this->supported )
			self::markTestSkipped( 'Memcache extension not available' );
		parent::testSetByMagic();
	}

	public function test_setByOffset(): void
	{
		if( !$this->supported )
			self::markTestSkipped( 'Memcache extension not available' );
		parent::testSetByOffset();
	}

	public function test_set_withException(): void
	{
		if( !$this->supported )
			self::markTestSkipped( 'Memcache extension not available' );
		parent::testSetWithExceptionInvalidKey();
	}

	public function test_setMultiple(): void
	{
		if( !$this->supported )
			self::markTestSkipped( 'Memcache extension not available' );
		parent::testSetMultiple();
	}

	public function test_setEncoder(): void
	{
		if( !$this->supported )
			self::markTestSkipped( 'Memcache extension not available' );
		parent::testSetEncoder();
	}

	public function test_setEncoder_withException(): void
	{
		if( !$this->supported )
			self::markTestSkipped( 'Memcache extension not available' );
		parent::testSetEncoder_withException();
	}

	//  --  PROTECTED  --  //

	/** @noinspection PhpUnhandledExceptionInspection */
	protected function setUp(): void
	{
		$this->adapter		= new NoopAdapter();
		$this->supported	= class_exists( 'Memcache', FALSE );
		if( $this->supported )
			$this->adapter		= new MemcacheAdapter( NULL );
	}

	protected function tearDown(): void
	{
		$this->adapter->clear();
		$this->adapter->setContext();
		$this->adapter->clear();
	}
}