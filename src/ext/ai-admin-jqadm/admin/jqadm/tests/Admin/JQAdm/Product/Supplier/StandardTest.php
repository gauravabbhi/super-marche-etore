<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2020
 */


namespace Aimeos\Admin\JQAdm\Product\Supplier;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $object;
	private $view;


	protected function setUp() : void
	{
		$this->view = \TestHelperJqadm::getView();
		$this->context = \TestHelperJqadm::getContext();

		$this->object = new \Aimeos\Admin\JQAdm\Product\Supplier\Standard( $this->context );
		$this->object = new \Aimeos\Admin\JQAdm\Common\Decorator\Page( $this->object, $this->context );
		$this->object->setAimeos( \TestHelperJqadm::getAimeos() );
		$this->object->setView( $this->view );
	}


	protected function tearDown() : void
	{
		unset( $this->object, $this->view, $this->context );
	}


	public function testCreate()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'product' );

		$this->view->item = $manager->createItem();
		$result = $this->object->create();

		$this->assertStringContainsString( 'item-supplier', $result );
		$this->assertEmpty( $this->view->get( 'errors' ) );
	}


	public function testCopy()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'product' );

		$this->view->item = $manager->findItem( 'CNC' );
		$result = $this->object->copy();

		$this->assertEmpty( $this->view->get( 'errors' ) );
		$this->assertRegexp( '/&quot;supplier.label&quot;:&quot;unitSupplier001&quot;/', $result );
	}


	public function testDelete()
	{
		$result = $this->object->delete();

		$this->assertEmpty( $this->view->get( 'errors' ) );
		$this->assertEmpty( $result );
	}


	public function testGet()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'product' );

		$this->view->item = $manager->findItem( 'CNC' );
		$result = $this->object->get();

		$this->assertEmpty( $this->view->get( 'errors' ) );
		$this->assertRegexp( '/&quot;supplier.label&quot;:&quot;unitSupplier001&quot;/', $result );
	}


	public function testSave()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'supplier' );
		$productManager = \Aimeos\MShop::create( $this->context, 'product' );

		$item = $manager->findItem( 'unitCode001' );
		$item->setCode( 'jqadm-test-supplier' );
		$item->setId( null );

		$item = $manager->saveItem( $item );


		$param = array(
			'site' => 'unittest',
			'supplier' => [[
				'supplier.lists.id' => '',
				'supplier.lists.type' => 'default',
				'supplier.id' => $item->getId(),
			]]
		);

		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $param );
		$this->view->addHelper( 'param', $helper );
		$this->view->item = $productManager->createItem()->setId( -1 );

		$result = $this->object->save();

		$item = $manager->getItem( $item->getId(), array( 'product' ) );
		$manager->deleteItem( $item->getId() );

		$this->assertEmpty( $this->view->get( 'errors' ) );
		$this->assertEmpty( $result );
		$this->assertEquals( 1, count( $item->getListItems( 'product' ) ) );
	}


	public function testSaveException()
	{
		$object = $this->getMockBuilder( \Aimeos\Admin\JQAdm\Product\Supplier\Standard::class )
			->setConstructorArgs( array( $this->context, \TestHelperJqadm::getTemplatePaths() ) )
			->setMethods( array( 'fromArray' ) )
			->getMock();

		$object->expects( $this->once() )->method( 'fromArray' )
			->will( $this->throwException( new \RuntimeException() ) );

		$this->view = \TestHelperJqadm::getView();
		$this->view->item = \Aimeos\MShop::create( $this->context, 'product' )->createItem();

		$object->setView( $this->view );

		$this->expectException( \RuntimeException::class );
		$object->save();
	}


	public function testSearch()
	{
		$this->assertEmpty( $this->object->search() );
	}


	public function testGetSubClient()
	{
		$this->expectException( \Aimeos\Admin\JQAdm\Exception::class );
		$this->object->getSubClient( 'unknown' );
	}
}
