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
class DB1_AnyMarket_Model_Anymarketproducts_Api extends Mage_Api_Model_Resource_Abstract
{


    /**
     * init anymarket products
     *
     * @access protected
     * @param $anymarketproductsId
     * @return DB1_AnyMarket_Model_Anymarketproducts
    
     */
    protected function _initAnymarketproducts($anymarketproductsId)
    {
        $anymarketproducts = Mage::getModel('db1_anymarket/anymarketproducts')->load($anymarketproductsId);
        if (!$anymarketproducts->getId()) {
            $this->_fault('anymarketproducts_not_exists');
        }
        return $anymarketproducts;
    }

    /**
     * get anymarket products
     *
     * @access public
     * @param mixed $filters
     * @return array
     * 
     */
    public function items($filters = null)
    {
        $collection = Mage::getModel('db1_anymarket/anymarketproducts')->getCollection();
        $apiHelper = Mage::helper('api');
        $filters = $apiHelper->parseFilters($filters);
        try {
            foreach ($filters as $field => $value) {
                $collection->addFieldToFilter($field, $value);
            }
        } catch (Mage_Core_Exception $e) {
            $this->_fault('filters_invalid', $e->getMessage());
        }
        $result = array();
        foreach ($collection as $anymarketproducts) {
            $result[] = $this->_getApiData($anymarketproducts);
        }
        return $result;
    }

    /**
     * Add anymarket products
     *
     * @access public
     * @param array $data
     * @return array
     * 
     */
    public function add($data)
    {
        try {
            if (is_null($data)) {
                throw new Exception(Mage::helper('db1_anymarket')->__("Data cannot be null"));
            }

            $ret = "";
            $anymarketlog = Mage::getModel('db1_anymarket/anymarketlog');
            $anymarketlog->setLogDesc( 'Callback received - Transmission (API)');
            $anymarketlog->setLogJson( json_encode($data) );
            $anymarketlog->setStatus("0");
            $anymarketlog->save();

            $allStores = Mage::helper('db1_anymarket')->getTokenByOi( $data['oi'] );
            if( !empty($allStores) ) {
                foreach ($allStores as $store) {
                    $storeID = $store['storeID'];
                    $TOKEN = $store['token'];

                    if ($TOKEN != '') {
                        $sincMode = Mage::getStoreConfig('anymarket_section/anymarket_general_group/anymarket_operation_type_imp_field', $storeID);
                        if( $sincMode == "1" ) {
                            $typeSincProd = Mage::getStoreConfig('anymarket_section/anymarket_integration_prod_group/anymarket_type_prod_sync_field', $storeID);
                            if($typeSincProd == "1") {
                                Mage::helper('db1_anymarket/queue')->addQueue($storeID, $data['id'], 'IMP', 'PRODUCT');
                            }else{
                                $typeSincOrder = Mage::getStoreConfig('anymarket_section/anymarket_integration_order_group/anymarket_type_order_sync_field', $storeID);
                                if( $typeSincOrder == "0" ){
                                    Mage::helper('db1_anymarket/queue')->addQueue($storeID, $data['id'], 'IMP', 'STOCK');
                                }
                            }

                            $ret = "Adicionado na fila Magento.";
                        }else{
                            $HOST = Mage::getStoreConfig('anymarket_section/anymarket_acesso_group/anymarket_host_field', $storeID);

                            $headers = array(
                                "Content-type: application/json",
                                "Accept: */*",
                                "gumgaToken: " . $TOKEN
                            );

                            $listTransmissions = array();
                            array_push($listTransmissions, array(
                                    "id" => $data['id'],
                                    "token" => "notoken"
                                )
                            );

                            $JSON = json_encode($listTransmissions);
                            $ret = Mage::helper('db1_anymarket/product')->getSpecificFeedProduct($storeID, json_decode($JSON), $headers, $HOST);
                        }
                    }
                }
            }


        } catch (Mage_Core_Exception $e) {
            $this->_fault('data_invalid', $e->getMessage());
        } catch (Exception $e) {
            $this->_fault('data_invalid', $e->getMessage());
        }
        return $ret;
    }

    /**
     * Change existing anymarket products information
     *
     * @access public
     * @param int $anymarketproductsId
     * @param array $data
     * @return bool
     * 
     */
    public function update($anymarketproductsId, $data)
    {
        $anymarketproducts = $this->_initAnymarketproducts($anymarketproductsId);
        try {
            $data = (array)$data;
            $anymarketproducts->addData($data);
            $anymarketproducts->save();
        }
        catch (Mage_Core_Exception $e) {
            $this->_fault('save_error', $e->getMessage());
        }

        return true;
    }

    /**
     * remove anymarket products
     *
     * @access public
     * @param int $anymarketproductsId
     * @return bool
     * 
     */
    public function remove($anymarketproductsId)
    {
        $anymarketproducts = $this->_initAnymarketproducts($anymarketproductsId);
        try {
            $anymarketproducts->delete();
        } catch (Mage_Core_Exception $e) {
            $this->_fault('remove_error', $e->getMessage());
        }
        return true;
    }

    /**
     * get info
     *
     * @access public
     * @param int $anymarketproductsId
     * @return array
     * 
     */
    public function info($anymarketproductsId)
    {
        $result = array();
        $anymarketproducts = $this->_initAnymarketproducts($anymarketproductsId);
        $result = $this->_getApiData($anymarketproducts);
        return $result;
    }

    /**
     * get data for api
     *
     * @access protected
     * @param DB1_AnyMarket_Model_Anymarketproducts $anymarketproducts
     * @return array()
     * 
     */
    protected function _getApiData(DB1_AnyMarket_Model_Anymarketproducts $anymarketproducts)
    {
        return $anymarketproducts->getData();
    }
}
