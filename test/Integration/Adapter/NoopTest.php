<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

namespace CeusMedia\CacheTest\Integration\Adapter;

use CeusMedia\Cache\Adapter\Noop as NoopAdapter;
use CeusMedia\Cache\SimpleCacheInvalidArgumentException;
use CeusMedia\CacheTest\TestCase;
use function _PHPStan_de1c07ea6\RingCentral\Psr7\parse_request;

class NoopTest extends AdapterTestCase
{
	protected string $path;
	protected string $filePath;

	public function test_construct(): void
	{
		$adapter	= new NoopAdapter( '', 'context', 120 );
		self::assertEquals( 'context', $adapter->getContext() );
		self::assertEquals( 120, $adapter->getExpiration() );
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function test_clear(): void
	{
		$this->adapter->setMultiple( ['key1' => 'value1'] );
		self::assertTrue( $this->adapter->clear() );
		self::assertEquals( [], $this->adapter->index() );
		self::assertTrue( $this->adapter->clear() );

		$this->adapter->setContext( 'a' );
		$this->adapter->setMultiple( ['key3' => 'value3', 'key4' => 'value4'] );
		self::assertTrue( $this->adapter->clear() );
		self::assertEquals( [], $this->adapter->index() );
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function test_delete(): void
	{
		$this->adapter->set( 'key1', 'value1' );
		self::assertTrue( $this->adapter->delete( 'key1' ) );
		self::assertFalse( $this->adapter->has( 'key1' ) );
		self::assertEquals( [], $this->adapter->index() );
		self::assertTrue( $this->adapter->delete( 'key1' ) );

		$this->adapter->setContext( 'key' );
		$this->adapter->set( 'key2', 'value2' );
		self::assertTrue( $this->adapter->delete( '1' ) );
		self::assertEquals( [], $this->adapter->index() );
		self::assertTrue( $this->adapter->delete( '1' ) );
	}

	public function test_delete_withException1(): void
	{
		parent::testDeleteWithExceptionInvalidKey();
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function test_deleteMultiple(): void
	{
		$this->adapter->setMultiple( ['key1' => 'value1', 'key2' => 'value2'] );
		self::assertEquals( [], $this->adapter->index() );
		self::assertTrue( $this->adapter->deleteMultiple( ['key1', 'key2'] ) );
		self::assertEquals( [], $this->adapter->index() );

		$this->adapter->setMultiple( ['key3' => 'value3', 'key4' => 'value4'] );
		$this->adapter->setContext( 'key' );
		self::assertTrue( $this->adapter->deleteMultiple( ['key3', 'key4'] ) );
		self::assertEquals( [], $this->adapter->index() );
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function test_get(): void
	{
//		self::assertNull( $this->adapter->get( 'key1' ) );

		$this->adapter->setMultiple( ['key1' => 'value1'] );
		self::assertNull( $this->adapter->get( 'key1' ) );

		$this->adapter->setMultiple( ['key1' => 'value1', 'key2' => 'value2'] );
		self::assertNull( $this->adapter->get( 'key1' ) );
		self::assertNull( $this->adapter->get( 'key2' ) );

		self::assertNull( $this->adapter->get( 'notExistingKey' ) );

		$this->adapter->setContext( 'key' );
		$this->adapter->setMultiple( ['key3' => 'value3', 'key4' => 'value4'] );
		self::assertNull( $this->adapter->get( 'key3' ) );
		self::assertNull( $this->adapter->get( 'key4' ) );

		self::assertNull( $this->adapter->get( 'notExistingKey' ) );
	}

	public function test_get_withDefault(): void
	{
		parent::testGetWithDefault();
	}

	public function test_get_withException1(): void
	{
		parent::testGetWithExceptionInvalidKey();
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function test_getMultiple(): void
	{
		self::assertEquals( [], $this->adapter->index() );

		$this->adapter->setMultiple( ['key1' => 'value1'] );
		self::assertEquals( [], $this->adapter->index() );

		$this->adapter->setMultiple( ['key1' => 'value1', 'key2' => 'value2'] );
		self::assertEquals( [], $this->adapter->index() );
		self::assertEquals( [], $this->adapter->getMultiple( ['key1', 'key2'] ) );

		$this->adapter->setContext( 'key' );
		$this->adapter->setMultiple( ['key3' => 'value3', 'key4' => 'value4'] );
		self::assertEquals( [], $this->adapter->index() );
		self::assertEquals( [], $this->adapter->getMultiple( ['key3', 'key4'] ) );
	}

	public function test_has(): void
	{
		$this->adapter->set( 'key1', 'value1' );
		self::assertFalse( $this->adapter->has( 'key1' ) );
	}

	public function test_has_byMagic(): void
	{
		$this->adapter->set( 'key1', 'value1' );
		self::assertFalse( isset( $this->adapter->key1 ) );
	}

	public function test_has_byOffset(): void
	{
		$this->adapter->set( 'key1', 'value1' );
		self::assertFalse( isset( $this->adapter['key1'] ) );
	}


	/** @noinspection PhpUnhandledExceptionInspection */
	public function test_index(): void
	{
		$this->adapter->setMultiple( ['key1' => 'value1', 'key2' => 'value2'] );
		self::assertEquals( [], $this->adapter->getMultiple( ['key1', 'key2'] ) );
		self::assertEquals( [], $this->adapter->getMultiple( ['key1'] ) );
		self::assertEquals( [], $this->adapter->getMultiple( ['key2'] ) );

		$this->adapter->setContext( 'key' );
		$this->adapter->setMultiple( ['key3' => 'value3', 'key4' => 'value4'] );
		self::assertEquals( [], $this->adapter->getMultiple( ['key3', 'key4'] ) );
		self::assertEquals( [], $this->adapter->getMultiple( ['key3'] ) );
		self::assertEquals( [], $this->adapter->getMultiple( ['key4'] ) );
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function test_set(): void
	{
//		self::assertNull( $this->adapter->get( 'key1' ) );

		$this->adapter->set( 'key1', 'value1' );
		self::assertEquals( [], $this->adapter->index() );
		self::assertNull( $this->adapter->get( 'key1' ) );

		$this->adapter->set( 'key2', 'value2' );
		self::assertEquals( [], $this->adapter->index() );
		self::assertNull( $this->adapter->get( 'key1' ) );
		self::assertNull( $this->adapter->get( 'key2' ) );

		$this->adapter->setContext( 'key' );
		$this->adapter->set( 'key3', 'value3' );
		self::assertEquals( [], $this->adapter->index() );
		self::assertNull( $this->adapter->get( 'key2' ) );
		self::assertNull( $this->adapter->get( 'key3' ) );
	}

	public function test_setByMagic(): void
	{
		$this->adapter->key1	= 'value1';
		self::assertNull( $this->adapter->get( 'key1' ) );
	}

	public function test_setByOffset(): void
	{
		$this->adapter['key1']	= 'value1';
		self::assertNull( $this->adapter->get( 'key1' ) );
	}

	public function test_set_withException(): void
	{
		parent::testSetWithExceptionInvalidKey();
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function test_setMultiple(): void
	{
		$data	= ['key1' => 'value1', 'key2' => 'value2'];
		self::assertTrue( $this->adapter->setMultiple( $data ) );
		self::assertEquals( [], $this->adapter->getMultiple( ['key1', 'key2'] ) );
		self::assertEquals( [], $this->adapter->getMultiple( ['key1'] ) );
		self::assertEquals( [], $this->adapter->getMultiple( ['key2'] ) );

		$this->adapter->setContext( 'key' );
		$this->adapter->setMultiple( $data );
		self::assertEquals( [], $this->adapter->getMultiple( ['key1', 'key2'] ) );
		self::assertEquals( [], $this->adapter->getMultiple( ['key1'] ) );
		self::assertEquals( [], $this->adapter->getMultiple( ['key2'] ) );
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
		$this->adapter	= new NoopAdapter( '' );
	}

	protected function tearDown(): void
	{
	}
}