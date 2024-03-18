<?php

namespace CeusMedia\CacheTest\Unit\Encoder;

use CeusMedia\Cache\Encoder\JSON as JsonEncoder;
use CeusMedia\CacheTest\TestCase;

class JsonTest extends TestCase
{
	/** @noinspection PhpUnhandledExceptionInspection */
	public function test_decode(): void
	{
		self::assertNull( JsonEncoder::decode( (string) json_encode( NULL ) ) );
		self::assertEquals( 1, JsonEncoder::decode( (string) json_encode( 1 ) ) );
		self::assertEquals( -1.234, JsonEncoder::decode( (string) json_encode( -1.234 ) ) );
		self::assertEquals( 'ABCÄÖÜ', JsonEncoder::decode( (string) json_encode( 'ABCÄÖÜ' ) ) );

		$array	= ['a' => 1, 'b' => [1, 2, 3]];
		self::assertEquals( $array, JsonEncoder::decode( (string) json_encode( $array ) ) );

		$object	= (object) $array;
		self::assertEquals( $array, JsonEncoder::decode( (string) json_encode( $object ) ) );
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function test_encode(): void
	{
		self::assertNull( json_decode( JsonEncoder::encode( NULL ) ) );
		self::assertTrue( json_decode( JsonEncoder::encode( TRUE ) ) );
		self::assertFalse( json_decode( JsonEncoder::encode( FALSE ) ) );
		self::assertEquals( 1, json_decode( JsonEncoder::encode( 1 ) ) );
		self::assertEquals( -1.234, json_decode( JsonEncoder::encode( -1.234 ) ) );
		self::assertEquals( 'ABCÄÖÜ', json_decode( JsonEncoder::encode( 'ABCÄÖÜ' ) ) );

		$array	= ['a' => 1, 'b' => [1, 2, 3]];
		self::assertEquals( $array, json_decode( JsonEncoder::encode( $array ), TRUE ) );

		$object	= (object) $array;
		self::assertEquals( $array, json_decode( JsonEncoder::encode( $object ), TRUE ) );
	}
}