<?php
set_time_limit(500000);

require_once('app/Mage.php');
umask(0);
Mage::app();

error_reporting(E_ALL);
ini_set('display_errors', 1);

$storeID = $_GET['store_id'];
$AttrToDoc = strtolower(Mage::getStoreConfig('anymarket_section/anymarket_attribute_group/anymarket_doc_type_field', $storeID));

$salesModel=Mage::getModel("sales/order");
$salesCollection = $salesModel->getCollection();
foreach($salesCollection as $order){
    $orderId = $order->getIncrementId();
    $OrderCheck = Mage::getModel('sales/order')->loadByIncrementId( $orderId );

    if( $OrderCheck->getCustomerTaxvat() == null ){
        $orderHistory = Mage::getModel('sales/order_status_history')->getCollection()
            ->addFieldToFilter('parent_id', $OrderCheck->getId());

        $needChange = false;
        foreach ($orderHistory as $history) {
            $iniEstimatedDate = strpos($history->getComment(), "Código do Pedido no Canal de Vendas");
            if ($iniEstimatedDate !== false) {
                $needChange = true;
                break;
            }
        }
        if ($needChange) {
            $customer_id = $OrderCheck->getCustomerId();
            $customerData = Mage::getModel('customer/customer')->load($customer_id);

            $taxVat = $customerData->getData($AttrToDoc);
            $OrderCheck->setCustomerTaxvat( $taxVat );
            $OrderCheck->save();

            echo $orderId." (".$taxVat.") - OK <br>";
        }
    }
}

?>