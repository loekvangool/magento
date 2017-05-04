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
 * @copyright      Copyright (c) 25
 * @license        http://opensource.org/licenses/mit-license.php MIT License
 */
/**
 * Anymarket Orders admin controller
 *
 * @category    DB1
 * @package     DB1_AnyMarket
 */
class DB1_AnyMarket_Adminhtml_Anymarket_AnymarketordersController extends DB1_AnyMarket_Controller_Adminhtml_AnyMarket
{
    /**
     * init the anymarket orders
     *
     * @access protected
     * @return DB1_AnyMarket_Model_Anymarketorders
     */
    protected function _initAnymarketorders()
    {
        $anymarketordersId  = (int) $this->getRequest()->getParam('id');
        $anymarketorders    = Mage::getModel('db1_anymarket/anymarketorders');
        if ($anymarketordersId) {
            $anymarketorders->load($anymarketordersId);
        }
        Mage::register('current_anymarketorders', $anymarketorders);
        return $anymarketorders;
    }

    /**
     * default action
     *
     * @access public
     * @return void
     */
    public function indexAction()
    {
        $this->loadLayout();
        $this->_title(Mage::helper('db1_anymarket')->__('AnyMarket'))
             ->_title(Mage::helper('db1_anymarket')->__('Anymarket Orders'));
        $this->renderLayout();
    }

    /**
     * list Orders action
     *
     * @access public
     * @return void
     */
    public function listOrdersAction()
    {
        $storeID = Mage::getSingleton('core/session')->getStoreListOrderVariable();
        $count = Mage::helper('db1_anymarket/order')->listOrdersFromAnyMarketMagento($storeID);

        Mage::getSingleton('adminhtml/session')->addSuccess(
            Mage::helper('db1_anymarket')->__('Total de %d pedidos listados com sucesso..', $count)
        );
        $this->_redirect('*/*/');
    }

    /**
     * mass sincronize anymarket Orders - action
     *
     * @access public
     * @return void
     * 
     */
    public function massSincOrderAction()
    {
        $anymarketOrdersIds = $this->getRequest()->getParam('anymarketorders');
        if (!is_array($anymarketOrdersIds)) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('db1_anymarket')->__('Por favor selecione Orders para sincronizar.')
            );
        } else {
            foreach ($anymarketOrdersIds as $anymarketOrderId) {
                $anymarketorders = Mage::getModel('db1_anymarket/anymarketorders');
                $anymarketorders->load($anymarketOrderId);

                if( is_array($anymarketorders->getData('store_id')) ){
                    $arrStores = $anymarketorders->getData('store_id');
                    $storeID = reset($arrStores);
                }

                if($anymarketorders->getData('nmo_status_int') == 'ERROR 01'){
                    Mage::helper('db1_anymarket/queue')->addQueue($storeID, $anymarketorders->getNmoIdSeqAnymarket(), 'IMP', 'ORDER');
                }else if($anymarketorders->getData('nmo_status_int') == 'ERROR 02'){
                    Mage::helper('db1_anymarket/queue')->addQueue($storeID, $anymarketorders->getNmoIdOrder(), 'EXP', 'ORDER');
                }else{
                    $ConfigOrder = Mage::getStoreConfig('anymarket_section/anymarket_integration_order_group/anymarket_type_order_sync_field', Mage::app()->getStore()->getId());
                    if($ConfigOrder == 0){ //IMPORT
                        if($anymarketorders->getNmoIdAnymarket() != ''){
                            Mage::helper('db1_anymarket/queue')->addQueue($storeID, $anymarketorders->getNmoIdSeqAnymarket(), 'IMP', 'ORDER');
                        }
                    }else{ //EXPORT
                        if($anymarketorders->getNmoIdOrder()){
                            Mage::helper('db1_anymarket/queue')->addQueue($storeID, $anymarketorders->getNmoIdOrder(), 'EXP', 'ORDER');
                        }
                    }
                }

            }
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('db1_anymarket')->__('Total %d orders were added to the queue.', count($anymarketOrdersIds))
            );
        }
        $this->_redirect('*/*/index');
    }

    /**
     * mass import anymarket Orders - action
     *
     * @access public
     * @return void
     *
     */
    public function massImportOrderAction()
    {
        $anymarketOrdersIds = $this->getRequest()->getParam('anymarketorders');
        if (!is_array($anymarketOrdersIds)) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('db1_anymarket')->__('Por favor selecione Orders para sincronizar.')
            );
        } else {
            foreach ($anymarketOrdersIds as $anymarketOrderId) {
                $anymarketorders = Mage::getModel('db1_anymarket/anymarketorders');
                $anymarketorders->load($anymarketOrderId);

                if( is_array($anymarketorders->getData('store_id')) ){
                    $arrStores = $anymarketorders->getData('store_id');
                    $storeID = reset($arrStores);
                }

                if($anymarketorders->getNmoIdAnymarket() != '') {
                    Mage::helper('db1_anymarket/queue')->addQueue($storeID, $anymarketorders->getNmoIdSeqAnymarket(), 'IMP', 'ORDER');
                }
            }
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('db1_anymarket')->__('Total %d orders were added to the queue.', count($anymarketOrdersIds))
            );
        }
        $this->_redirect('*/*/index');
    }

    /**
     * mass export anymarket Orders - action
     *
     * @access public
     * @return void
     *
     */
    public function massExportOrderAction()
    {
        $anymarketOrdersIds = $this->getRequest()->getParam('anymarketorders');
        if (!is_array($anymarketOrdersIds)) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('db1_anymarket')->__('Por favor selecione Orders para sincronizar.')
            );
        } else {
            foreach ($anymarketOrdersIds as $anymarketOrderId) {
                $anymarketorders = Mage::getModel('db1_anymarket/anymarketorders');
                $anymarketorders->load($anymarketOrderId);

                if( is_array($anymarketorders->getData('store_id')) ){
                    $arrStores = $anymarketorders->getData('store_id');
                    $storeID = reset($arrStores);
                }

                if($anymarketorders->getNmoIdOrder()){
                    Mage::helper('db1_anymarket/queue')->addQueue($storeID, $anymarketorders->getNmoIdOrder(), 'EXP', 'ORDER');
                }

            }
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('db1_anymarket')->__('Total %d orders were added to the queue.', count($anymarketOrdersIds))
            );
        }
        $this->_redirect('*/*/index');
    }

    /**
     * grid action
     *
     * @access public
     * @return void
     */
    public function gridAction()
    {
        $this->loadLayout()->renderLayout();
    }

    /**
     * edit anymarket orders - action
     *
     * @access public
     * @return void
     */
    public function editAction()
    {
        $anymarketordersId    = $this->getRequest()->getParam('id');
        $anymarketorders      = $this->_initAnymarketorders();
        if ($anymarketordersId && !$anymarketorders->getId()) {
            $this->_getSession()->addError(
                Mage::helper('db1_anymarket')->__('This anymarket orders no longer exists.')
            );
            $this->_redirect('*/*/');
            return;
        }
        $data = Mage::getSingleton('adminhtml/session')->getAnymarketordersData(true);
        if (!empty($data)) {
            $anymarketorders->setData($data);
        }
        Mage::register('anymarketorders_data', $anymarketorders);
        $this->loadLayout();
        $this->_title(Mage::helper('db1_anymarket')->__('AnyMarket'))
             ->_title(Mage::helper('db1_anymarket')->__('Anymarket Orders'));
        if ($anymarketorders->getId()) {
            $this->_title($anymarketorders->getNmoIdOrder());
        } else {
            $this->_title(Mage::helper('db1_anymarket')->__('Add anymarket orders'));
        }
        if (Mage::getSingleton('cms/wysiwyg_config')->isEnabled()) {
            $this->getLayout()->getBlock('head')->setCanLoadTinyMce(true);
        }
        $this->renderLayout();
    }

    /**
     * new anymarket orders action
     *
     * @access public
     * @return void
     */
    public function newAction()
    {
        $this->_forward('edit');
    }

    /**
     * save anymarket orders - action
     *
     * @access public
     * @return void
     */
    public function saveAction()
    {
        if ($data = $this->getRequest()->getPost('anymarketorders')) {
            try {
                $anymarketorders = $this->_initAnymarketorders();
                $anymarketorders->addData($data);
                $anymarketorders->save();
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('db1_anymarket')->__('Anymarket Orders was successfully saved')
                );
                Mage::getSingleton('adminhtml/session')->setFormData(false);
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array('id' => $anymarketorders->getId()));
                    return;
                }
                $this->_redirect('*/*/');
                return;
            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setAnymarketordersData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            } catch (Exception $e) {
                Mage::logException($e);
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('db1_anymarket')->__('There was a problem saving the anymarket orders.')
                );
                Mage::getSingleton('adminhtml/session')->setAnymarketordersData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(
            Mage::helper('db1_anymarket')->__('Unable to find anymarket orders to save.')
        );
        $this->_redirect('*/*/');
    }

    /**
     * delete anymarket orders - action
     *
     * @access public
     * @return void
     */
    public function deleteAction()
    {
        if ( $this->getRequest()->getParam('id') > 0) {
            try {
                $anymarketorders = Mage::getModel('db1_anymarket/anymarketorders');
                $anymarketorders->setId($this->getRequest()->getParam('id'))->delete();
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('db1_anymarket')->__('Anymarket Orders was successfully deleted.')
                );
                $this->_redirect('*/*/');
                return;
            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('db1_anymarket')->__('There was an error deleting anymarket orders.')
                );
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                Mage::logException($e);
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(
            Mage::helper('db1_anymarket')->__('Could not find anymarket orders to delete.')
        );
        $this->_redirect('*/*/');
    }

    /**
     * mass delete anymarket orders - action
     *
     * @access public
     * @return void
     */
    public function massDeleteAction()
    {
        $anymarketordersIds = $this->getRequest()->getParam('anymarketorders');
        if (!is_array($anymarketordersIds)) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('db1_anymarket')->__('Please select anymarket orders to delete.')
            );
        } else {
            try {
                foreach ($anymarketordersIds as $anymarketordersId) {
                    $anymarketorders = Mage::getModel('db1_anymarket/anymarketorders');
                    $anymarketorders->load($anymarketordersId);

                    $anymarketlog = Mage::getModel('db1_anymarket/anymarketlog');
                    $anymarketlog->setLogDesc('Order deleted by user');
                    $anymarketlog->setLogJson( json_encode($anymarketorders->getData()) );
                    $anymarketlog->setLogId( $anymarketorders->getData('nmo_id_anymarket') );
                    $anymarketlog->setStatus("0");
                    $anymarketlog->save();

                    $anymarketorders->delete();

                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('db1_anymarket')->__('Total of %d anymarket orders were successfully deleted.', count($anymarketordersIds))
                );
            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('db1_anymarket')->__('There was an error deleting anymarket orders.')
                );
                Mage::logException($e);
            }
        }
        $this->_redirect('*/*/index');
    }

    /**
     * mass status change - action
     *
     * @access public
     * @return void
     */
    public function massStatusAction()
    {
        $anymarketordersIds = $this->getRequest()->getParam('anymarketorders');
        if (!is_array($anymarketordersIds)) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('db1_anymarket')->__('Please select anymarket orders.')
            );
        } else {
            try {
                foreach ($anymarketordersIds as $anymarketordersId) {
                $anymarketorders = Mage::getSingleton('db1_anymarket/anymarketorders')->load($anymarketordersId)
                            ->setStatus($this->getRequest()->getParam('status'))
                            ->setIsMassupdate(true)
                            ->save();
                }
                $this->_getSession()->addSuccess(
                    $this->__('Total of %d anymarket orders were successfully updated.', count($anymarketordersIds))
                );
            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('db1_anymarket')->__('There was an error updating anymarket orders.')
                );
                Mage::logException($e);
            }
        }
        $this->_redirect('*/*/index');
    }

    /**
     * export as csv - action
     *
     * @access public
     * @return void
     */
    public function exportCsvAction()
    {
        $fileName   = 'anymarketorders.csv';
        $content    = $this->getLayout()->createBlock('db1_anymarket/adminhtml_anymarketorders_grid')
            ->getCsv();
        $this->_prepareDownloadResponse($fileName, $content);
    }

    /**
     * export as MsExcel - action
     *
     * @access public
     * @return void
     */
    public function exportExcelAction()
    {
        $fileName   = 'anymarketorders.xls';
        $content    = $this->getLayout()->createBlock('db1_anymarket/adminhtml_anymarketorders_grid')
            ->getExcelFile();
        $this->_prepareDownloadResponse($fileName, $content);
    }

    /**
     * export as xml - action
     *
     * @access public
     * @return void
     */
    public function exportXmlAction()
    {
        $fileName   = 'anymarketorders.xml';
        $content    = $this->getLayout()->createBlock('db1_anymarket/adminhtml_anymarketorders_grid')
            ->getXml();
        $this->_prepareDownloadResponse($fileName, $content);
    }

    /**
     * Check if admin has permissions to visit related pages
     *
     * @access protected
     * @return boolean
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('system/db1_anymarket/anymarketorders');
    }
}
