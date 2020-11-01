<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2020
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Common\Product\Import\Csv\Processor\Stock;


/**
 * Product stock processor for CSV imports
 *
 * @package Controller
 * @subpackage Common
 */
class Standard
	extends \Aimeos\Controller\Common\Product\Import\Csv\Processor\Base
	implements \Aimeos\Controller\Common\Product\Import\Csv\Processor\Iface
{
	/** controller/common/product/import/csv/processor/stock/name
	 * Name of the stock processor implementation
	 *
	 * Use "Myname" if your class is named "\Aimeos\Controller\Common\Product\Import\Csv\Processor\Stock\Myname".
	 * The name is case-sensitive and you should avoid camel case names like "MyName".
	 *
	 * @param string Last part of the processor class name
	 * @since 2015.10
	 * @category Developer
	 */


	/**
	 * Saves the product stock related data to the storage
	 *
	 * @param \Aimeos\MShop\Product\Item\Iface $product Product item with associated items
	 * @param array $data List of CSV fields with position as key and data as value
	 * @return array List of data which hasn't been imported
	 */
	public function process( \Aimeos\MShop\Product\Item\Iface $product, array $data ) : array
	{
		$manager = \Aimeos\MShop::create( $this->getContext(), 'stock' );
		$manager->begin();

		try
		{
			$map = $this->getMappedChunk( $data, $this->getMapping() );
			$items = $this->getStockItems( $product->getCode() );

			foreach( $map as $pos => $list )
			{
				if( !array_key_exists( 'stock.stocklevel', $list ) ) {
					continue;
				}

				$list['stock.productcode'] = $product->getCode();
				$list['stock.dateback'] = $this->getValue( $list, 'stock.dateback' );
				$list['stock.stocklevel'] = $this->getValue( $list, 'stock.stocklevel' );
				$list['stock.type'] = $this->getValue( $list, 'stock.type', 'default' );

				$this->addType( 'stock/type', 'product', $list['stock.type'] );

				if( ( $item = $items->pop() ) === null ) {
					$item = $manager->createItem();
				}

				$manager->saveItem( $item->fromArray( $list ), false );
			}

			$manager->deleteItems( $items->toArray() );

			$data = $this->getObject()->process( $product, $data );

			$manager->commit();
		}
		catch( \Exception $e )
		{
			$manager->rollback();
			throw $e;
		}

		return $data;
	}


	/**
	 * Returns the stock items for the given product code
	 *
	 * @param string $code Unique product code
	 * @return \Aimeos\Map List of stock items implementing \Aimeos\MShop\Stock\Item\Iface
	 */
	protected function getStockItems( $code ) : \Aimeos\Map
	{
		$manager = \Aimeos\MShop::create( $this->getContext(), 'stock' );

		$search = $manager->createSearch();
		$search->setConditions( $search->compare( '==', 'stock.productcode', $code ) );

		return $manager->searchItems( $search );
	}
}
