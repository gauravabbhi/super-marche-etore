<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2020
 */


namespace Aimeos\Admin\JQAdm\Customer\Product;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $object;
	private $view;


	protected function setUp() : void
	{
		$this->view = \TestHelperJqadm::getView();
		$this->context = \TestHelperJqadm::getContext();

		$this->object = new \Aimeos\Admin\JQAdm\Customer\Product\Standard( $this->context );
		$this->object = new \Aimeos\Admin\JQAdm\Common\Decorator\Page( $this->object, $this->context );
		$this->object->setAimeos( \TestHelperJqadm::getAimeos() );
		$this->object->setView( $this->view );
	}


	protected function tearDown() : void
	{
		unset( $this->object, $this->view, $this->context );
	}


	public function testCopy()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'customer' );
		$this->view->item = $manager->findItem( 'test@example.com' );

		$result = $this->object->copy();

		$this->assertStringContainsString( 'item-product', $result );
	}


	public function testCreate()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'customer' );
		$this->view->item = $manager->findItem( 'test@example.com' );

		$result = $this->object->create();

		$this->assertStringContainsString( 'item-product', $result );
	}


	public function testGet()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'customer' );
		$this->view->item = $manager->findItem( 'test@example.com' );

		$result = $this->object->get();

		$this->assertStringContainsString( 'item-product', $result );
		$this->assertStringContainsString( 'watch', $result );
	}


	public function testSave()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'customer' );

		$item = $manager->findItem( 'test@example.com' );
		$item->setCode( 'jqadm-test-save' );
		$item->setId( null );

		$item = $manager->saveItem( $item );


		$param = array(
			'site' => 'unittest',
			'product' => array(
				'customer.lists.id' => [0 => ''],
				'customer.lists.status' => [0 => 1],
				'customer.lists.refid' => [0 => 'test'],
				'customer.lists.type' => [0 => 'favorite'],
				'customer.lists.datestart' => [0 => '2000-01-01 00:00:00'],
				'customer.lists.dateend' => [0 => '2100-01-01 00:00:00'],
				'config' => [0 => ['key' => [0 => 'test'], 'val' => [0 => 'value']]],
			),
		);

		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $param );
		$this->view->addHelper( 'param', $helper );
		$this->view->item = $item;

		$result = $this->object->save();

		$item = $manager->getItem( $item->getId(), ['product'] );
		$manager->deleteItem( $item->getId() );

		$this->assertEmpty( $this->view->get( 'errors' ) );
		$this->assertEmpty( $result );
		$this->assertEquals( 1, count( $item->getListItems() ) );

		foreach( $item->getListItems( 'product' ) as $listItem )
		{
			$this->assertEquals( $item->getId(), $listItem->getParentId() );
			$this->assertEquals( 'favorite', $listItem->getType() );
			$this->assertEquals( 'product', $listItem->getDomain() );
			$this->assertEquals( '2000-01-01 00:00:00', $listItem->getDateStart() );
			$this->assertEquals( '2100-01-01 00:00:00', $listItem->getDateEnd() );
			$this->assertEquals( ['test' => 'value'], $listItem->getConfig() );
			$this->assertEquals( 'test', $listItem->getRefId() );
			$this->assertEquals( 1, $listItem->getStatus() );
		}
	}


	public function testSaveException()
	{
		$object = $this->getMockBuilder( \Aimeos\Admin\JQAdm\Customer\Product\Standard::class )
			->setConstructorArgs( array( $this->context, \TestHelperJqadm::getTemplatePaths() ) )
			->setMethods( array( 'fromArray' ) )
			->getMock();

		$object->expects( $this->once() )->method( 'fromArray' )
			->will( $this->throwException( new \RuntimeException() ) );

		$object->setView( $this->getViewNoRender() );

		$this->expectException( \RuntimeException::class );
		$object->save();
	}


	public function testGetSubClient()
	{
		$this->expectException( \Aimeos\Admin\JQAdm\Exception::class );
		$this->object->getSubClient( 'unknown' );
	}


	protected function getViewNoRender()
	{
		$view = $this->getMockBuilder( \Aimeos\MW\View\Standard::class )
			->setConstructorArgs( array( [] ) )
			->setMethods( array( 'render', 'config' ) )
			->getMock();

		$manager = \Aimeos\MShop::create( $this->context, 'customer' );
		$view->item = $manager->findItem( 'test@example.com' );

		return $view;
	}
}
