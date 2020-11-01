<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2020
 */


namespace Aimeos\Client\Html\Catalog\Price;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $object;


	protected function setUp() : void
	{
		$this->object = new \Aimeos\Client\Html\Catalog\Price\Standard( \TestHelperHtml::getContext() );
		$this->object->setView( \TestHelperHtml::getView() );
	}


	protected function tearDown() : void
	{
		unset( $this->object );
	}


	public function testGetBody()
	{
		$this->object->setView( $this->object->addData( $this->object->getView() ) );
		$output = $this->object->getBody();

		$this->assertStringContainsString( '<section class="aimeos catalog-filter"', $output );
		$this->assertStringContainsString( '<section class="catalog-filter-price', $output );
	}


	public function testGetSubClient()
	{
		$client = $this->object->getSubClient( 'price', 'Standard' );
		$this->assertInstanceOf( '\\Aimeos\\Client\\HTML\\Iface', $client );
	}
}
