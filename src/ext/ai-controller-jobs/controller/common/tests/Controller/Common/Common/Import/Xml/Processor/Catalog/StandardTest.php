<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019-2020
 */


namespace Aimeos\Controller\Common\Common\Import\Xml\Processor\Catalog;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $object;


	protected function setUp() : void
	{
		$this->context = \TestHelperCntl::getContext();
		$this->object = new \Aimeos\Controller\Common\Common\Import\Xml\Processor\Catalog\Standard( $this->context );
	}


	protected function tearDown() : void
	{
		unset( $this->object, $this->context );
	}


	public function testProcess()
	{
		$dom = new \DOMDocument();
		$product = \Aimeos\MShop::create( $this->context, 'product' )->findItem( 'IJKL' );

		$dom->loadXML( '<?xml version="1.0" encoding="UTF-8" standalone="no" ?>
<catalog>
	<catalogitem ref="root" />
</catalog>' );

		$this->object->process( $product, $dom->firstChild );

		$catItem = \Aimeos\MShop::create( $this->context, 'catalog' )->findItem( 'root', ['product'] );
		$listItem = $catItem->getListItem( 'product', 'default', $product->getId() );

		$this->assertNotNull( $listItem );

		\Aimeos\MShop::create( $this->context, 'catalog/lists' )->deleteItem( $listItem->getId() );
	}


	public function testProcessUpdate()
	{
		$dom = new \DOMDocument();
		$product = \Aimeos\MShop::create( $this->context, 'product' )->findItem( 'IJKL' );

		$dom->loadXML( '<?xml version="1.0" encoding="UTF-8" standalone="no" ?>
<catalog>
	<catalogitem ref="root" />
</catalog>' );

		$this->object->process( $product, $dom->firstChild );

		$dom->loadXML( '<?xml version="1.0" encoding="UTF-8" standalone="no" ?>
<catalog>
	<catalogitem ref="root" />
	<catalogitem ref="categories" />
</catalog>' );

		$this->object->process( $product, $dom->firstChild );

		$listIds = [];
		$manager = \Aimeos\MShop::create( $this->context, 'catalog' );

		$catItem = $manager->findItem( 'root', ['product'] );
		$listItem = $catItem->getListItem( 'product', 'default', $product->getId() );
		$this->assertNotNull( $listItem );
		$listIds[] = $listItem->getId();

		$catItem = $manager->findItem( 'categories', ['product'] );
		$listItem = $catItem->getListItem( 'product', 'default', $product->getId() );
		$this->assertNotNull( $listItem );
		$listIds[] = $listItem->getId();

		\Aimeos\MShop::create( $this->context, 'catalog/lists' )->deleteItems( $listIds );
	}


	public function testProcessDelete()
	{
		$dom = new \DOMDocument();
		$product = \Aimeos\MShop::create( $this->context, 'product' )->findItem( 'IJKL' );

		$dom->loadXML( '<?xml version="1.0" encoding="UTF-8" standalone="no" ?>
<catalog>
	<catalogitem ref="root" />
</catalog>' );

		$this->object->process( $product, $dom->firstChild );

		$dom->loadXML( '<?xml version="1.0" encoding="UTF-8" standalone="no" ?>
<catalog>
</catalog>' );

		$this->object->process( $product, $dom->firstChild );

		$catItem = \Aimeos\MShop::create( $this->context, 'catalog' )->findItem( 'root', ['product'] );
		$listItem = $catItem->getListItem( 'product', 'default', $product->getId() );

		$this->assertNull( $listItem );
	}
}
