<?php
declare(strict_types=1);

namespace CeusMedia\CacheTest\Unit;

use CeusMedia\Cache\Adapter\Memory as MemoryAdapter;
use CeusMedia\Cache\Adapter\Noop as NoopAdapter;
use CeusMedia\Cache\SimpleCacheFactory;
use CeusMedia\CacheTest\TestCase;

class SimpleCacheFactoryTest extends TestCase
{
	/** @noinspection PhpUnhandledExceptionInspection */
	public function test_construct(): void
	{
		$factory	= new SimpleCacheFactory( 'ctx1:' );
		$cache		= $factory->newStorage( NoopAdapter::class );
		self::assertEquals( 'ctx1:', $cache->getContext() );
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function test_createStorage(): void
	{
		$cache		= SimpleCacheFactory::createStorage( 'Noop' );
		self::assertEquals( NoopAdapter::class, get_class( $cache ) );

		$cache		= SimpleCacheFactory::createStorage( NoopAdapter::class );
		self::assertEquals( NoopAdapter::class, get_class( $cache ) );

		$cache		= SimpleCacheFactory::createStorage( 'Memory' );
		self::assertEquals( MemoryAdapter::class, get_class( $cache ) );

		$data		= [
			'key1'	=> 'value1',
			'key2'	=> 'value2',
		];
		$cache		= SimpleCacheFactory::createStorage(
			MemoryAdapter::class,
			NULL,
			'ctx1:',
			10,
			$data
		);
		self::assertEquals( MemoryAdapter::class, get_class( $cache ) );
		self::assertEquals( 'ctx1:', $cache->getContext() );
		self::assertEquals( $data, $cache->getMultiple( array_keys( $data ) ) );
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function test_createStorage_withException(): void
	{
		$this->expectException( \RuntimeException::class );
		SimpleCacheFactory::createStorage( 'NotExistingAdapter' );
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function test_newStorage(): void
	{
		$factory	= new SimpleCacheFactory();

		$cache		= $factory->newStorage( 'Noop' );
		self::assertEquals( NoopAdapter::class, get_class( $cache ) );

		$cache		= $factory->newStorage( NoopAdapter::class );
		self::assertEquals( NoopAdapter::class, get_class( $cache ) );

		$cache		= $factory->newStorage( 'Memory' );
		self::assertEquals( MemoryAdapter::class, get_class( $cache ) );

		$data		= [
			'key1'	=> 'value1',
			'key2'	=> 'value2',
		];
		$cache		= $factory->newStorage(
			MemoryAdapter::class,
			NULL,
			'ctx1:',
			10,
			$data
		);
		self::assertEquals( MemoryAdapter::class, get_class( $cache ) );
		self::assertEquals( 'ctx1:', $cache->getContext() );
		self::assertEquals( $data, $cache->getMultiple( array_keys( $data ) ) );
	}

	public function test_newStorage_withException(): void
	{
		$this->expectException( \RuntimeException::class );
		$factory	= new SimpleCacheFactory();
		$factory->newStorage( 'NotExistingAdapter' );
	}
}