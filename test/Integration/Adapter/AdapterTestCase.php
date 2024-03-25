<?php
/** @noinspection PhpMultipleClassDeclarationsInspection */

namespace CeusMedia\CacheTest\Integration\Adapter;

use CeusMedia\Cache\Encoder\Igbinary as IgbinaryEncoder;
use CeusMedia\Cache\Encoder\JSON as JsonEncoder;
use CeusMedia\Cache\Encoder\Msgpack as MsgpackEncoder;
use CeusMedia\Cache\Encoder\Noop as NoopEncoder;
use CeusMedia\Cache\Encoder\Serial as SerialEncoder;
use CeusMedia\Cache\Encoder\SupportException;
use CeusMedia\Cache\SimpleCacheInterface;
use CeusMedia\Cache\SimpleCacheInvalidArgumentException;
use CeusMedia\CacheTest\TestCase;

class AdapterTestCase extends TestCase
{
	protected SimpleCacheInterface $adapter;

	protected function testDelete()
	{
		$data1	= ['key1' => 'value1', 'key2' => 'value2'];
		$this->adapter->setMultiple( $data1 );
		self::assertTrue( $this->adapter->delete( 'key1' ) );
		self::assertFalse( $this->adapter->has( 'key1' ) );
		self::assertEquals( ['key2'], $this->adapter->index() );
		self::assertFalse( $this->adapter->delete( 'key1' ) );

		$data2	= ['key3' => 'value3', 'key4' => 'value4'];
		$this->adapter->setContext( 'ctx1' );
		$this->adapter->setMultiple( $data2 );
		self::assertTrue( $this->adapter->delete( 'key4' ) );
		self::assertEquals( ['key3'], $this->adapter->index() );
		self::assertFalse( $this->adapter->delete( 'key4' ) );
	}

	protected function testDeleteByMagic(): void
	{
		$this->adapter->set( 'key1', 'value1' );
		self::assertEquals( 'value1', $this->adapter->get( 'key1' ) );
		unset( $this->adapter->key1 );
		self::assertNull( $this->adapter->get( 'key1' ) );
	}

	protected function testDeleteByOffset(): void
	{
		$this->adapter->setMultiple( ['key1' => 'value1', 'key2' => 'value2'] );
		unset( $this->adapter['key1'] );
		self::assertNull( $this->adapter->get( 'key1' ) );
		self::assertEquals( 'value2', $this->adapter->get( 'key2' ) );
	}

	protected function testDeleteWithExceptionInvalidKey(): void
	{
		$this->expectException( SimpleCacheInvalidArgumentException::class );
		$this->adapter->delete( '__äöü__' );
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	protected function testDeleteMultiple(): void
	{
		$data	= [
			'key1' => 'value1',
			'key2' => 'value2',
			'key3' => 'value3',
			'key4' => 'value4',
		];
		$this->adapter->setMultiple( $data );
		self::assertEquals( ['key1', 'key2', 'key3', 'key4'], $this->adapter->index() );
		self::assertTrue( $this->adapter->deleteMultiple( ['key2', 'key3'] ) );
		self::assertEquals( ['key1', 'key4'], $this->adapter->index() );

		$this->adapter->setContext( 'ctx1:' );
		$this->adapter->setMultiple( $data );
		self::assertEquals( ['key1', 'key2', 'key3', 'key4'], $this->adapter->index() );
		self::assertTrue( $this->adapter->deleteMultiple( ['key1', 'key4'] ) );
		self::assertEquals( ['key2', 'key3'], $this->adapter->index() );
		self::assertTrue( $this->adapter->deleteMultiple( ['key2', 'key3'] ) );
		self::assertEquals( [], $this->adapter->index() );

		$this->adapter->setContext();
		self::assertEquals( ['key1', 'key4'], $this->adapter->index() );
		self::assertTrue( $this->adapter->deleteMultiple( ['key1', 'key4'] ) );
		self::assertEquals( [], $this->adapter->index() );
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	protected function testGet(): void
	{
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

	/** @noinspection PhpUnhandledExceptionInspection */
	protected function testGetByMagic(): void
	{
		$this->adapter->set( 'key1', 'value1' );
		self::assertEquals( 'value1', $this->adapter->key1 );
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	protected function testGetByOffset(): void
	{
		$this->adapter->set( 'key1', 'value1' );
		self::assertEquals( 'value1', $this->adapter['key1'] );
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	protected function testGetWithDefault(): void
	{
		$value	= 'defaultValue';
		self::assertEquals( $value, $this->adapter->get( 'notExisting', $value ) );
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	protected function testGetWithExceptionInvalidKey(): void
	{
		$this->expectException( SimpleCacheInvalidArgumentException::class );
		$this->adapter->get( '__äöü__' );
	}

	protected function testHas(): void
	{
		self::assertFalse( $this->adapter->has( 'key1' ) );
		$this->adapter->set( 'key1', 'value1' );
		self::assertTrue( $this->adapter->has( 'key1' ) );
	}

	protected function testHasByMagic(): void
	{
		$this->adapter->set( 'key1', 'value1' );
		self::assertTrue( isset( $this->adapter->key1 ) );
	}

	protected function testHasByOffset(): void
	{
		$this->adapter->set( 'key1', 'value1' );
		self::assertTrue( isset( $this->adapter['key1'] ) );
	}

	protected function testIndex(): void
	{
		$data1	= ['key1' => 'value1', 'key2' => 'value2'];
		$this->adapter->setMultiple( $data1 );
		self::assertEqualsCanonicalizing( array_keys( $data1 ), $this->adapter->index() );

		$this->adapter->setContext( 'ctx1:' );

		$data2	= ['key3' => 'value3', 'key4' => 'value4'];
		$this->adapter->setMultiple( $data2 );
		self::assertEqualsCanonicalizing( array_keys( $data2 ), $this->adapter->index() );
	}

	/**
	 * @return void
	 * @todo implement!
	 */
	protected function testSet(): void
	{
	}

	protected function testSetByMagic(): void
	{
		$this->adapter->key1	= 'value1';
		self::assertEquals( 'value1', $this->adapter->get( 'key1' ) );
	}

	protected function testSetByOffset(): void
	{
		$this->adapter['key1']	= 'value1';
		self::assertEquals( 'value1', $this->adapter->get( 'key1' ) );
	}

	protected function testSetWithExceptionInvalidKey(): void
	{
		$this->expectException( SimpleCacheInvalidArgumentException::class );
		$this->adapter->set( '__äöü__', 'nothing' );
	}

	protected function testSetMultiple(): void
	{
		$data1	= ['key1' => 'value1', 'key2' => 'value2'];
		self::assertTrue( $this->adapter->setMultiple( $data1 ) );
		self::assertEquals( $data1, $this->adapter->getMultiple( array_keys( $data1 ) ) );

		$data2	= ['key3' => 'value3', 'key4' => 'value4'];
		$this->adapter->setContext( 'ctx1' );
		$this->adapter->setMultiple( $data2 );
		self::assertEquals( $data2, $this->adapter->getMultiple( array_keys( $data2 ) ) );
	}

	protected function testSetEncoder(): void
	{
		$this->adapter->setEncoder( JsonEncoder::class );
		self::assertEquals( JsonEncoder::class, $this->adapter->getEncoder() );

		$this->adapter->setEncoder( SerialEncoder::class );
		self::assertEquals( SerialEncoder::class, $this->adapter->getEncoder() );
	}


	protected function testSetEncoder_withException(): void
	{
		$this->expectException( SupportException::class );
		$this->adapter->setEncoder( NoopEncoder::class );
		$this->adapter->setEncoder( JsonEncoder::class );
		$this->adapter->setEncoder( MsgpackEncoder::class );
		$this->adapter->setEncoder( IgbinaryEncoder::class );
	}
}