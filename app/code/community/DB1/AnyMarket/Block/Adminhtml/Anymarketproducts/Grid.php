<?php
/**
 * DB1_AnyMarket extension
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the MIT License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/mit-license.php
 * 
 * @category       DB1
 * @package        DB1_AnyMarket
 * @copyright      Copyright (c) 2015
 * @license        http://opensource.org/licenses/mit-license.php MIT License
 */
/**
 * Anymarket Products admin grid block
 *
 * @category    DB1
 * @package     DB1_AnyMarket

 */
class DB1_AnyMarket_Block_Adminhtml_Anymarketproducts_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * constructor
     *
     * @access public
     * 
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('anymarketproductsGrid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    /**
     * get current store scope
     *
     * @access protected
     * @return store view
     
     */
    protected function _getStore()
    {
        $storeId = (int) $this->getRequest()->getParam('store', 0);
        return Mage::app()->getStore($storeId);
    }

    /**
     * prepare collection
     *
     * @access protected
     * @return DB1_AnyMarket_Block_Adminhtml_Anymarketproducts_Grid
     * 
     */
    protected function _prepareCollection()
    {
        $store_id = $this->_getStore();
        Mage::app()->setCurrentStore($store_id);
        $store_id = Mage::app()->getStore()->getId();
        Mage::getSingleton('core/session')->setStoreListProdVariable($store_id);

        $collection = Mage::getModel('db1_anymarket/anymarketproducts')
            ->getCollection()
            ->setOrder('entity_id','DESC');

        $productIntegrate = Mage::getSingleton('eav/config')->getAttribute('catalog_product','integra_anymarket');
        $idAttr = $productIntegrate->getId();
        $productCategTable = Mage::getSingleton('core/resource')->getTableName('catalog/category_product');
        $selCollection = $collection->getSelect();

        $selCollection->joinLeft(
                array('product_category' => $productCategTable),
                'product_category.product_id = main_table.nmp_id',
                array('product_category.product_id')
            );
        if( $idAttr ) {
            $selCollection->join(
                array('product_attribute' => $productIntegrate->getBackendTable()),
                'product_attribute.entity_id = main_table.nmp_id AND product_attribute.attribute_id = ' . $idAttr,
                array('product_attribute.value')
            );
        }
        $selCollection->group('main_table.entity_id');

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * prepare grid collection
     *
     * @access protected
     * @return DB1_AnyMarket_Block_Adminhtml_Anymarketproducts_Grid
     * 
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'nmp_sku',
            array(
                'header' => Mage::helper('db1_anymarket')->__('Product SKU'),
                'index'  => 'nmp_sku',
                'type'=> 'text',

            )
        );
        $this->addColumn(
            'nmp_name',
            array(
                'header' => Mage::helper('db1_anymarket')->__('Product Name'),
                'index'  => 'nmp_name',
                'type'=> 'text',

            )
        );
        $this->addColumn(
            'category',
            array(
                'header' => Mage::helper('db1_anymarket')->__('Category'),
                'index' => 'category_id',
                'filter_index' => 'category_id',
                'sortable'	=> false,
    			'width' => '250px',
				'type'  => 'options',
                'options'	=> Mage::getSingleton('db1_anymarket/system_config_source_categories_category')->toOptionArray(),
                'renderer'	=> 'db1_anymarket/Adminhtml_Anymarketproducts_grid_render_category'
            )
        );
        if( Mage::getSingleton('eav/config')->getAttribute('catalog_product','integra_anymarket')->getId() ){
            $this->addColumn(
                'value',
                array(
                    'header' => Mage::helper('db1_anymarket')->__('Will be integrated'),
                    'index' => 'value',
                    'type' => 'options',
                    'options' => array(
                        '1' => Mage::helper('db1_anymarket')->__('Yes'),
                        '0' => Mage::helper('db1_anymarket')->__('No'),
                    )
                )
            );
        }
        $this->addColumn(
            'nmp_status_int',
            array(
                'header'    => Mage::helper('db1_anymarket')->__('Integration status'),
                'align'     => 'left',
                'index'     => 'nmp_status_int',
            )
        );
        $this->addColumn(
            'nmp_desc_error',
            array(
                'header'    => Mage::helper('db1_anymarket')->__('Integration message'),
                'align'     => 'left',
                'index'     => 'nmp_desc_error',
            )
        );
        if (!Mage::app()->isSingleStoreMode() && !$this->_isExport) {
            $this->addColumn(
                'store_id',
                array(
                    'header'     => Mage::helper('db1_anymarket')->__('Store Views'),
                    'index'      => 'store_id',
                    'type'       => 'store',
                    'store_all'  => true,
                    'store_view' => true,
                    'sortable'   => false,
                    'filter_condition_callback'=> array($this, '_filterStoreCondition'),
                )
            );
        }
        $this->addColumn(
            'created_at',
            array(
                'header' => Mage::helper('db1_anymarket')->__('Created at'),
                'index'  => 'created_at',
                'width'  => '120px',
                'type'   => 'datetime',
            )
        );
        $this->addColumn(
            'updated_at',
            array(
                'header'    => Mage::helper('db1_anymarket')->__('Updated at'),
                'index'     => 'updated_at',
                'width'     => '120px',
                'type'      => 'datetime',
            )
        );
        $this->addExportType('*/*/exportCsv', Mage::helper('db1_anymarket')->__('CSV'));
        $this->addExportType('*/*/exportExcel', Mage::helper('db1_anymarket')->__('Excel'));
        $this->addExportType('*/*/exportXml', Mage::helper('db1_anymarket')->__('XML'));
        return parent::_prepareColumns();
    }

    /**
     * prepare mass action
     *
     * @access protected
     * @return DB1_AnyMarket_Block_Adminhtml_Anymarketproducts_Grid
     * 
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('anymarketproducts');

        $this->getMassactionBlock()->addItem(
            'delete',
            array(
                'label'=> Mage::helper('db1_anymarket')->__('Delete'),
                'url'  => $this->getUrl('*/*/massDelete'),
                'confirm'  => Mage::helper('db1_anymarket')->__('Are you sure?')
            )
        );

        $this->getMassactionBlock()->addItem(
            'sincronizar',
            array(
                'label'=> Mage::helper('db1_anymarket')->__('Synchronize'),
                'url'  => $this->getUrl('*/*/massSincProduct'),
                'confirm'  => Mage::helper('db1_anymarket')->__('Are you sure you want to sync?')
            )
        );

        $this->getMassactionBlock()->addItem(
            'status',
            array(
                'label'      => Mage::helper('db1_anymarket')->__('Status change Integration'),
                'url'        => $this->getUrl('*/*/massStatus', array('_current'=>true)),
                'additional' => array(
                    'status' => array(
                        'name'   => 'status',
                        'type'   => 'select',
                        'class'  => 'required-entry',
                        'label'  => Mage::helper('db1_anymarket')->__('Status'),
                        'values' => array(
                            '1' => Mage::helper('db1_anymarket')->__('Yes'),
                            '0' => Mage::helper('db1_anymarket')->__('No'),
                        )
                    )
                )
            )
        );
        return $this;
    }

    /**
     * get the row url
     *
     * @access public
     * @param DB1_AnyMarket_Model_Anymarketproducts
     * @return string
     * 
     */
    public function getRowUrl($row)
    {
        /*
        $_pullProduct = Mage::getModel('catalog/product')->loadByAttribute('sku', $row->getNmpSku());
        if($_pullProduct != null){
            return Mage::helper('adminhtml')->getUrl('adminhtml/catalog_product/edit', array('id' => $_pullProduct->getId()));
        }else{
            return null;
        }
        */
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }

    /**
     * get the grid url
     *
     * @access public
     * @return string
     * 
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }

    /**
     * after collection load
     *
     * @access protected
     * @return DB1_AnyMarket_Block_Adminhtml_Anymarketproducts_Grid
     * 
     */
    protected function _afterLoadCollection()
    {
        $this->getCollection()->walk('afterLoad');
        parent::_afterLoadCollection();
    }

    /**
     * filter store column
     *
     * @access protected
     * @param DB1_AnyMarket_Model_Resource_Anymarketproducts_Collection $collection
     * @param Mage_Adminhtml_Block_Widget_Grid_Column $column
     * @return DB1_AnyMarket_Block_Adminhtml_Anymarketproducts_Grid
     * 
     */
    protected function _filterStoreCondition($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return;
        }
        $collection->addStoreFilter($value);
        return $this;
    }
}
