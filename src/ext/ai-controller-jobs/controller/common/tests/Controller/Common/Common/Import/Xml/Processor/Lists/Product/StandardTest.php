<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019-2020
 */


namespace Aimeos\Controller\Common\Common\Import\Xml\Processor\Lists\Product;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $object;


	protected function setUp() : void
	{
		$this->context = \TestHelperCntl::getContext();
		$this->object = new \Aimeos\Controller\Common\Common\Import\Xml\Processor\Lists\Product\Standard( $this->context );
	}


	protected function tearDown() : void
	{
		unset( $this->object, $this->context );
	}


	public function testProcess()
	{
		$dom = new \DOMDocument();
		$manager = \Aimeos\MShop::create( $this->context, 'product' );

		$refId1 = $manager->findItem( 'CNC' )->getId();
		$refId2 = $manager->findItem( 'CNE' )->getId();

		$dom->loadXML( '<?xml version="1.0" encoding="UTF-8" standalone="no" ?>
<product>
	<productitem ref="CNC" lists.type="default" />
	<productitem ref="CNE" lists.type="test" />
</product>' );

		$product = $this->object->process( $manager->createItem(), $dom->firstChild );

		$this->assertEquals( 2, count( $product->getListItems() ) );
		$this->assertNotNull( $product->getListItem( 'product', 'default', $refId1 ) );
		$this->assertNotNull( $product->getListItem( 'product', 'test', $refId2 ) );
	}


	public function testProcessUpdate()
	{
		$dom = new \DOMDocument();
		$manager = \Aimeos\MShop::create( $this->context, 'product' );
		$listManager = \Aimeos\MShop::create( $this->context, 'product/lists' );

		$product = $manager->createItem();
		$refId1 = $manager->findItem( 'CNC' )->getId();
		$refId2 = $manager->findItem( 'CNE' )->getId();

		$product->addListItem( 'product',
			$listManager->createItem()->setType( 'default' )->setId( 1 )->setRefId( $refId1 )
		);
		$product->addListItem( 'product',
			$listManager->createItem()->setType( 'test' )->setId( 2 )->setRefId( $refId2 )
		);

		$dom->loadXML( '<?xml version="1.0" encoding="UTF-8" standalone="no" ?>
<product>
	<productitem ref="CNE" lists.type="test" />
	<productitem ref="CNC" lists.type="default" />
</product>' );

		$product = $this->object->process( $product, $dom->firstChild );

		$this->assertEquals( 2, count( $product->getListItems() ) );
		$this->assertNotNull( $product->getListItem( 'product', 'test', $refId2 ) );
		$this->assertNotNull( $product->getListItem( 'product', 'default', $refId1 ) );
	}


	public function testProcessDelete()
	{
		$dom = new \DOMDocument();
		$manager = \Aimeos\MShop::create( $this->context, 'product' );
		$listManager = \Aimeos\MShop::create( $this->context, 'product/lists' );

		$product = $manager->createItem();
		$refId1 = $manager->findItem( 'CNC' )->getId();
		$refId2 = $manager->findItem( 'CNE' )->getId();

		$product->addListItem( 'product',
			$listManager->createItem()->setType( 'default' )->setId( 1 )->setRefId( $refId1 )
		);
		$product->addListItem( 'product',
			$listManager->createItem()->setType( 'test' )->setId( 2 )->setRefId( $refId2 )
		);

		$dom->loadXML( '<?xml version="1.0" encoding="UTF-8" standalone="no" ?>
<product>
	<productitem ref="CNE" lists.type="default" />
</product>' );

		$product = $this->object->process( $product, $dom->firstChild );

		$this->assertEquals( 1, count( $product->getListItems() ) );
		$this->assertNotNull( $product->getListItem( 'product', 'default', $refId2 ) );
	}
}
