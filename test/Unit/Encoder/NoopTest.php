<?php

namespace CeusMedia\CacheTest\Unit\Encoder;

use CeusMedia\Cache\Encoder\Noop as NoopEncoder;
use CeusMedia\CacheTest\TestCase;

class NoopTest extends TestCase
{
	public function test_decode(): void
	{
		$code	= 'test';
		self::assertEquals( $code, NoopEncoder::decode( $code ) );
	}

	public function test_encode(): void
	{
		$code	= 'test';
		self::assertEquals( $code, NoopEncoder::encode( $code ) );
	}
}