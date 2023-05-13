<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

namespace CeusMedia\Cache\Test;

use CeusMedia\Common\UI\DevOutput;
use PHPUnit\Framework\TestCase as PhpUnitTestCase;

class TestCase extends PhpUnitTestCase
{
	/** @var string $pathLibrary */
	protected string $pathLibrary;

	/** @var string $pathTests */
	protected string $pathTests;

	/**
	 * @param string $name
	 */
	public function __construct( string $name )
	{
		new DevOutput();
		parent::__construct( $name );
		$this->pathLibrary		= dirname( __DIR__ ).'/';
		$this->pathTests		= __DIR__.'/';
	}

	//  --  PROTECTED  --  //
}
