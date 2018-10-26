<?php

class DB1_AnyMarket_Helper_Order extends DB1_AnyMarket_Helper_Data
{

    /**
     * get status order AM to MG from configs
     *
     * @param $OrderRowData
     * @return string
     */
    public function getStatusAnyMarketToMageOrderConfig($storeID, $OrderRowData){
        if($OrderRowData == null){
            $OrderRowData = "new";
        }

        $StatusOrder = Mage::getStoreConfig('anymarket_section/anymarket_integration_order_group/anymarket_status_am_mg_field', $storeID);
        $OrderReturn = 'ERROR: 1 Não há uma configuração válida para '.$OrderRowData;
        $StateReturn = "";
        if ($StatusOrder && $StatusOrder != 'a:0:{}') {
            $StatusOrder = unserialize($StatusOrder);
            if (is_array($StatusOrder)) {
                foreach($StatusOrder as $StatusOrderRow) {
                    if($StatusOrderRow['orderStatusAM'] == $OrderRowData){
                        $OrderReturn = $StatusOrderRow['orderStatusMG'];
                        $statuses = Mage::getModel('sales/order_status')->getCollection()->joinStates()
                            ->addFieldToFilter('main_table.status',array('eq'=>$OrderReturn));

                        $StateReturn = $statuses->getFirstItem()->getData('state');
                        break;
                    }

                }
            }
        }

        return array("status" => $OrderReturn, "state" => $StateReturn);
    }

    /**
     * create log order magento
     *
     * @param $fieldFilter
     * @param $fieldDataFilter
     * @param $statusInt
     * @param $descError
     * @param $idSeqAnyMarket
     * @param $IDOrderAnyMarket
     * @param $nmoIdOrder
     * @param $storeID
     */
    private function saveLogOrder($fieldFilter, $fieldDataFilter, $statusInt, $descError, $idSeqAnyMarket, $IDOrderAnyMarket, $nmoIdOrder, $storeID){
        $anymarketorders = Mage::getModel('db1_anymarket/anymarketorders')->setStoreId($storeID);
        $anymarketorders->load($fieldDataFilter, $fieldFilter);

        $anymarketorders->setStatus("0");
        $anymarketorders->setNmoStatusInt($statusInt);
        $anymarketorders->setNmoDescError($descError);

        $anymarketorders->setNmoIdSeqAnymarket($idSeqAnyMarket);
        $anymarketorders->setNmoIdAnymarket( $IDOrderAnyMarket );

        $anymarketorders->setNmoIdOrder($nmoIdOrder);
        $anymarketorders->setStores(array($storeID));
        $anymarketorders->save();

        if( $descError != "" ) {
            Mage::getSingleton('adminhtml/session')->addError($descError);
        }
    }

    /**
     * get estimated date from order comment
     *
     * @param $Order
     * @return string
     */
    public function getEstimatedDateFromOrder( $Order ){
        $estimatedDate = "";
        foreach ($Order->getStatusHistoryCollection() as $item) {
            $CommentCurr = $item->getComment();

            $CommentCurr = str_replace(array("<br>"), "<br/>", $CommentCurr );
            $iniEstimatedDate = strpos($CommentCurr, 'Entrega esperada para:');
            if( $iniEstimatedDate !== false ) {
                $estimatedDate = substr( $CommentCurr, $iniEstimatedDate+27, 19);
                break;
            }

        }
        return $estimatedDate;
    }

    /**
     * get delivered date from order comment
     *
     * @param $Order
     * @return string
     */
    public function getDeliveredDateFromOrder($Order, $statuAM){
        $delivedDate = null;
        $deliveredDateAlt = null;
        foreach ($Order->getStatusHistoryCollection() as $item) {
            if($statuAM == "CONCLUDED" &&  $item->getStatus() == $Order->getStatus() ){
                $deliveredDateAlt = $this->formatDateTimeZone( str_replace("/", "-", $item->getCreatedAt()));
            }

            $CommentCurr = strtolower($item->getComment());

            $iniDelivedDate = strpos($CommentCurr, 'data de entrega:');
            if( $iniDelivedDate !== false ) {
                $delivedDate = substr( $CommentCurr, $iniDelivedDate+16, 19);
                break;
            }
        }
        return $delivedDate == null ? $deliveredDateAlt : $delivedDate;
    }

    /**
     * check status in comment order
     *
     * @param $order
     * @return string
     */
    public function canUpdateStatusInOrderByComment( $order, $statusInAnymarket ){
        $delivedDate = null;
        foreach ($order->getStatusHistoryCollection() as $item) {
            $CommentCurr = $item->getComment();

            $iniDelivedDate = strpos($CommentCurr, 'Status Anymarket: '.$statusInAnymarket);
            if( $iniDelivedDate !== false ) {
                return false;
            }
        }
        return true;
    }

    /**
     * get estimated date from order comment
     *
     * @param $shipping
     * @return string
     */
    public function getDatesFromShipping( $shipping ){
        $estimatedDate = "";
        $shippedDate = "";
        foreach ($shipping->getCommentsCollection() as $item) {
            $CommentCurr = strtolower($item->getComment());

            $iniEstimatedDate = strpos($CommentCurr, 'data estimada de entrega:');
            if ($iniEstimatedDate !== false) {
                $estimatedDate = substr($CommentCurr, $iniEstimatedDate+30, 19);
            }

            $iniShippedDate = strpos($CommentCurr, 'data de entrega na transportadora:');
            if ($iniShippedDate !== false) {
                $shippedDate = substr($CommentCurr, $iniShippedDate + 39, 19);
            }

        }

        $shippedDate   = ($shippedDate != "")   ? $this->formatDateTimeZone( str_replace("/", "-", $shippedDate ) )   : "";
        $estimatedDate = ($estimatedDate != "") ? $this->formatDateTimeZone( str_replace("/", "-", $estimatedDate ) ) : "";
        return array($estimatedDate, $shippedDate);
    }

    /**
     * get status order MG to AM from configs
     *
     * @param $OrderRowData
     * @return string
     */
    private function getStatusMageToAnyMarketOrderConfig($storeID, $OrderRowData){
        if($OrderRowData == null){
            $OrderRowData = "new";
        }

        $StatusOrder = Mage::getStoreConfig('anymarket_section/anymarket_integration_order_group/anymarket_status_mg_am_field', $storeID);
        $OrderReturn = 'ERROR: 2 Não há uma configuração válida para '.$OrderRowData;
        if ($StatusOrder && $StatusOrder != 'a:0:{}') {
            $StatusOrder = unserialize($StatusOrder);
            if (is_array($StatusOrder)) {
                foreach($StatusOrder as $StatusOrderRow) {
                    if($StatusOrderRow['orderStatusMG'] == $OrderRowData){
                        $OrderReturn = $StatusOrderRow['orderStatusAM'];
                        break;
                    }

                }
            }
        }

        return $OrderReturn;
    }

    /**
     * create order in Magento
     *
     * @param $storeID
     * @param $OrderJSON
     * @param $addType
     * @return array
     */
    public function getCompleteAddressOrder($storeID, $OrderJSON, $addType){
        $OrderJSON = json_decode(json_encode($OrderJSON), true);
        $retArrStreet = array(
            0 => "Não especificado.",
            1 => " ",
            2 => " ",
            3 => " "
        );

        //TRATAMENTO PARA QUANDO NAO EXISTER O BILLING ADDRESS
        if(!isset($OrderJSON[$addType]) && $addType == "billingAddress"){
            $addType = "shipping";
        }

        if(!isset($OrderJSON[$addType]) || !isset($OrderJSON[$addType]['street'])){
            return $retArrStreet;
        }

        $street1 = Mage::getStoreConfig('anymarket_section/anymarket_attribute_group/anymarket_add1_field', $storeID);
        $street2 = Mage::getStoreConfig('anymarket_section/anymarket_attribute_group/anymarket_add2_field', $storeID);
        $street3 = Mage::getStoreConfig('anymarket_section/anymarket_attribute_group/anymarket_add3_field', $storeID);
        $street4 = Mage::getStoreConfig('anymarket_section/anymarket_attribute_group/anymarket_add4_field', $storeID);

        $street1 = (isset($OrderJSON[$addType][$street1])) ? $OrderJSON[$addType][$street1] : $OrderJSON[$addType]['address'];
        $street2 = (isset($OrderJSON[$addType][$street2])) ? $OrderJSON[$addType][$street2] : '';
        $street3 = (isset($OrderJSON[$addType][$street3])) ? $OrderJSON[$addType][$street3] : '';
        $street4 = (isset($OrderJSON[$addType][$street4])) ? $OrderJSON[$addType][$street4] : '';

        $retArrStreet = array(
            0 => $street1,
            1 => $street2,
            2 => $street3,
            3 => $street4
        );

        return $retArrStreet;
    }

    /**
     * create order in Magento
     *
     * @param $IDOrderAnymarket
     * @return $order
     */
    public function getOrderAnymarketFromHistoryComment($IDOrderAnymarket, $codAnyMarket){
        $likeFilter =  '%Pedido no Canal de Vendas: </b>'.$IDOrderAnymarket.'<br>%';
        $likeFilter .= '%Id no MarketPlace: </b>'.$codAnyMarket.'<br>%';

        $collection = Mage::getModel('sales/order_status_history')->getCollection()
            ->addAttributeToFilter('comment', array('like' => $likeFilter));

        if(count($collection) <= 0){
            return null;
        }

        $firstItem = $collection->getFirstItem();
        $order = Mage::getModel('sales/order')->load( $firstItem->getData('parent_id') );
        if($order->getIncrementId() == "" || $order->getIncrementId() == null ){
            return null;
        }
        return $order;
    }

    /**
     * create order in Magento
     *
     * @param $anymarketordersSpec
     * @param $products
     * @param $customer
     * @param $IDAnyMarket
     * @param $IDSeqAnyMarket
     * @param $infoMetPag
     * @param $Billing
     * @param $Shipping
     * @param $shippValue
     * @return integer
     */
    private function create_order($storeID, $orderJSON, $anymarketordersSpec, $products, $customer, $IDSeqAnyMarket, $infoMetPag, $Billing, $Shipping, $ShippingDesc)
    {
        if( ($anymarketordersSpec->getData('nmo_id_anymarket') == null) ||
            ($anymarketordersSpec->getData('nmo_status_int') == "Não integrado (AnyMarket)") ||
            ($anymarketordersSpec->getData('nmo_status_int') == "ERROR 01") ) {
            $AttrToDoc = strtolower(Mage::getStoreConfig('anymarket_section/anymarket_attribute_group/anymarket_doc_type_field', $storeID));

            $orderGenerator = Mage::helper('db1_anymarket/ordergenerator');
            $orderGenerator->_storeId = $storeID;

            $shippValue  = $orderJSON->freight;
            $idAnyMarket = $orderJSON->marketPlaceId;
            $discount = $orderJSON->discount;

            $orderGenerator->setShippingMethod('freeshipping_freeshipping');
            $orderGenerator->setPaymentMethod('db1_anymarket');
            $orderGenerator->setAdditionalInformation($infoMetPag);
            $orderGenerator->setShippingValue($shippValue);
            $orderGenerator->setShipAddress($Shipping);
            $orderGenerator->setBillAddress($Billing);
            $orderGenerator->setCustomer($customer);
            $orderGenerator->setDiscount($discount);
            $orderGenerator->setCpfCnpj($customer->getData($AttrToDoc));
            $orderGenerator->setShippingDescription($ShippingDesc);

            $CodOrder = $orderGenerator->createOrder($storeID, $products);

            $this->saveLogOrder('nmo_id_anymarket', $idAnyMarket, 'Integrado', '', $IDSeqAnyMarket, $idAnyMarket, $CodOrder, $storeID);

            $anymarketlog = Mage::getModel('db1_anymarket/anymarketlog');
            $anymarketlog->setLogDesc('Order Created: ' . $CodOrder . ' ID Anymarket: ' . $idAnyMarket);
            $anymarketlog->setStatus("0");
            $anymarketlog->setStores(array($storeID));
            $anymarketlog->save();
        }else{
            $CodOrder = $anymarketordersSpec->getData('nmo_id_order');
        }

        return $CodOrder;
    }

    /**
     * get all order in feed AnyMarket
     */
    public function getFeedOrdersFromAnyMarket($storeID){
        $HOST  = Mage::getStoreConfig('anymarket_section/anymarket_acesso_group/anymarket_host_field', $storeID);
        $TOKEN = Mage::getStoreConfig('anymarket_section/anymarket_acesso_group/anymarket_token_field', $storeID);

        if( $TOKEN != '' && $TOKEN != null ) {
            $headers = array(
                "Content-type: application/json",
                "Accept: */*",
                "gumgaToken: " . $TOKEN
            );

            $returnProd = $this->CallAPICurl("GET", $HOST . "/v2/orders/feeds?limit=100", $headers, null);

            if ($returnProd['error'] == '1') {
                $anymarketlog = Mage::getModel('db1_anymarket/anymarketlog');
                $anymarketlog->setLogDesc('Error on get feed orders ' . $returnProd['return']);
                $anymarketlog->setStatus("1");
                $anymarketlog->save();
            } else {
                $listOrders = $returnProd['return'];
                foreach ($listOrders as $order) {
                    $anymarketlog = Mage::getModel('db1_anymarket/anymarketlog');
                    $anymarketlog->setLogDesc('Consumed Order from feed: ' . $order->id . ' with token: ' . $order->token);
                    $anymarketlog->setStatus("1");
                    $anymarketlog->save();

                    $this->getSpecificOrderFromAnyMarket($order->id, $order->token, $storeID);

                    if($order->token != 'notoken') {
                        $paramFeed = array(
                            "token" => $order->token
                        );
                        $this->CallAPICurl("PUT", $HOST . "/rest/api/v2/orders/feeds/" . $order->id, $headers, $paramFeed);
                    }
                }
            }
        }
    }

    /**
     * get specific order from AnyMarket
     *
     * @param $idSeqAnyMarket
     * @param $tokenFeed
     * @param $storeID
     * @return boolean
     */
    public function getSpecificOrderFromAnyMarket($idSeqAnyMarket, $tokenFeed, $storeID){
        $HOST  = Mage::getStoreConfig('anymarket_section/anymarket_acesso_group/anymarket_host_field', $storeID);
        $TOKEN = Mage::getStoreConfig('anymarket_section/anymarket_acesso_group/anymarket_token_field', $storeID);
        $headers = array( 
            "Content-type: application/json",
            "Accept: */*",
            "gumgaToken: ".$TOKEN
        );

        $stateProds = true;
        $returnOrderItens = $this->CallAPICurl("GET", $HOST."/v2/orders/".$idSeqAnyMarket, $headers, null);
        if($returnOrderItens['error'] == '0'){
            $OrderJSON = $returnOrderItens['return'];
            $IDOrderAnyMarket = $OrderJSON->marketPlaceId;
            $anymarketordersSpec = Mage::getModel('db1_anymarket/anymarketorders');
            $anymarketordersSpec->load($idSeqAnyMarket, 'nmo_id_seq_anymarket');
            $OrderIDMage = '';

            if( ($anymarketordersSpec->getData('nmo_id_anymarket') != null) &&
                ($anymarketordersSpec->getData('nmo_status_int') != "Não integrado (AnyMarket)") &&
                ($anymarketordersSpec->getData('nmo_status_int') != "ERROR 01") ) {

                $statsConfig = $this->getStatusAnyMarketToMageOrderConfig($storeID, $OrderJSON->status);
                $statusMage = $statsConfig["status"];
                if (strpos($statusMage, 'ERROR:') === false) {
                    if ($anymarketordersSpec->getData('nmo_id_order') != null) {
                        $this->changeStatusOrder($storeID, $OrderJSON, $anymarketordersSpec->getData('nmo_id_order'));
                    }
                }
                return true;
            }

            $IDAnyMarket = $OrderJSON->marketPlaceNumber;
            $codAnyMarket = $OrderJSON->marketPlaceId;
            $ctrlOrder = $this->getOrderAnymarketFromHistoryComment( $IDAnyMarket, $codAnyMarket );
            if( $ctrlOrder != null ) {
                $IDAnyMarket = $OrderJSON->marketPlaceId;
                $this->saveLogOrder('nmo_id_anymarket', $IDAnyMarket, 'Integrado', '', $idSeqAnyMarket, $IDAnyMarket, $ctrlOrder->getIncrementId(), $storeID);

                $statsConfig = $this->getStatusAnyMarketToMageOrderConfig($storeID, $OrderJSON->status);
                $statusMage = $statsConfig["status"];
                if (strpos($statusMage, 'ERROR:') === false) {
                    if ($ctrlOrder->getIncrementId() != null) {
                        $this->changeStatusOrder($storeID, $OrderJSON, $ctrlOrder->getIncrementId());
                    }
                }
                return true;
            }

            $ConfigOrder = Mage::getStoreConfig('anymarket_section/anymarket_integration_order_group/anymarket_type_order_sync_field', $storeID);
            if($ConfigOrder == 1) {
                $statsConfig = $this->getStatusAnyMarketToMageOrderConfig($storeID, $OrderJSON->status);
                $statusMage = $statsConfig["status"];

                if (strpos($statusMage, 'ERROR:') === false) {
                    //TRATA OS PRODUTOS
                    $_products = array();
                    $shippingDesc = array();
                    foreach ($OrderJSON->items as $item) {

                        if( isset($item->shippings) ) {
                            foreach ($item->shippings as $shippItem) {
                                if (!in_array($shippItem->shippingtype, $shippingDesc)) {
                                    array_push($shippingDesc, $shippItem->shippingtype);
                                }
                            }
                        }

                        $productLoaded = Mage::getModel('catalog/product')->setStoreId($storeID)->loadByAttribute('sku', $item->sku->partnerId);
                        if ($productLoaded) {
                            $arrayTMP = array(
                                'product' => $productLoaded->getId(),
                                'price' => $item->unit,
                                'discount' => $item->discount,
                                'qty' => $item->amount,
                            );

                            if($productLoaded->getTypeID() == "bundle") {
                                $optionsBundle = Mage::helper('db1_anymarket/product')->getDetailsOfBundle($productLoaded);

                                $boundOpt = array();
                                $boundOptQty = array();
                                foreach ($optionsBundle as $detProd) {
                                    $boundOpt[$detProd['option_id']] = $detProd['selection_id'];
                                    $boundOptQty[$detProd['option_id']] = (float)$detProd['selection_qty'];
                                }

                                $arrayTMP['bundle_option'] = $boundOpt;
                                $arrayTMP['bundle_option_qty'] = $boundOptQty;
                            }

                            array_push($_products, $arrayTMP);
                        } else {
                            $this->saveLogOrder('nmo_id_seq_anymarket',
                                $idSeqAnyMarket,
                                'ERROR 01',
                                Mage::helper('db1_anymarket')->__('Product is not registered') . ' (SKU: ' . $item->sku->partnerId . ')',
                                $idSeqAnyMarket,
                                $IDOrderAnyMarket,
                                '',
                                $storeID);

                            $anymarketlog = Mage::getModel('db1_anymarket/anymarketlog');
                            $anymarketlog->setLogDesc(Mage::helper('db1_anymarket')->__('Product is not registered') . ' (Order: ' . $idSeqAnyMarket . ', SKU : ' . $item->sku->partnerId . ')');
                            $anymarketlog->setStores(array($storeID));
                            $anymarketlog->setStatus("0");
                            $anymarketlog->save();

                            $this->addMessageInBox($storeID, Mage::helper('db1_anymarket')->__('Error on synchronize order.'),
                                Mage::helper('db1_anymarket')->__('Error synchronizing order number: ') . "Anymarket(" . $IDOrderAnyMarket . ") <br/>" .
                                Mage::helper('db1_anymarket')->__('Product is not registered') . ' (SKU: ' . $item->sku->partnerId . ')',
                                '');
                            $stateProds = false;
                            break;
                        }
                    }

                    //verifica se criou o produto
                    if ($stateProds) {
                        //TRATA O CLIENTE
                        $document = null;
                        if (isset($OrderJSON->buyer->document)) {
                            $document = $OrderJSON->buyer->document;
                        }

                        if ($document != null) {
                            try {
                                $AttrToDoc = strtolower(Mage::getStoreConfig('anymarket_section/anymarket_attribute_group/anymarket_doc_type_field', $storeID));
                                $groupCustomer = Mage::getStoreConfig('anymarket_section/anymarket_attribute_group/anymarket_customer_group_field', $storeID);

                                $email = $OrderJSON->buyer->email;
                                $customer = Mage::getModel('customer/customer')
                                    ->getCollection()
                                    ->addFieldToFilter($AttrToDoc, $document)->load()->getFirstItem();

                                //caso nao ache pelo CPF valida se nao tem mascara
                                if(!$customer->getId()) {
                                    if (strlen($document) == 11) {
                                        $document = $this->Mask('###.###.###-##', $document);
                                    } else {
                                        $document = $this->Mask('##.###.###/####-##', $document);
                                    }

                                    $customer = Mage::getModel('customer/customer')
                                        ->getCollection()
                                        ->addFieldToFilter($AttrToDoc, $document)->load()->getFirstItem();

                                    //caso ainda nao encontrou valida se existe o email
                                    if(!$customer->getId()) {
                                        $customer = Mage::getModel('customer/customer')
                                            ->getCollection()
                                            ->addFieldToFilter('email', $email)->load()->getFirstItem();

                                    }
                                }

                                $AddressBilling  = null;
                                $AddressShipping = null;

                                $firstName = $OrderJSON->buyer->name;
                                $lastName = '.';
                                if ($firstName != '') {
                                    $nameComplete = explode(" ", $firstName);

                                    $lastNameP = array_slice($nameComplete, 1);
                                    $lastNameImp = implode(" ", $lastNameP);

                                    $firstName = array_shift($nameComplete);
                                    $lastName = $lastNameImp == '' ? '.' : $lastNameImp;
                                }

                                $shippingRegion = $this->getStateNormalized($OrderJSON, "shipping");
                                $billingRegion = $this->getStateNormalized($OrderJSON, "billingAddress");

                                $addressShippingFullData = $this->getCompleteAddressOrder($storeID, $OrderJSON, "shipping");
                                $addressBillingFullData = $this->getCompleteAddressOrder($storeID, $OrderJSON, "billingAddress");
                                if ($customer->getId() == null) {
                                    $_DataCustomer = array(
                                        'account' => array(
                                            'firstname' => $firstName,
                                            'lastname' => $lastName,
                                            'email' => $email,
                                            $AttrToDoc => $document,
                                            'password' => 'a111111',
                                            'default_billing' => '_item1',
                                            'default_shipping' => '_item1',
                                            'store_id' => $storeID,
                                            'website_id' => Mage::app()->getWebsite()->getId(),
                                            'group_id' => $groupCustomer,
                                        ),
                                        'address' => array(
                                            '_item1' => array(
                                                'firstname' => $firstName,
                                                'lastname' => $lastName,
                                                'street' => $addressShippingFullData,
                                                'city' => (isset($OrderJSON->shipping->city)) ? $OrderJSON->shipping->city : 'Não especificado',
                                                'country_id' => $OrderJSON->shipping->countryAcronymNormalized,
                                                'region_id' => $shippingRegion['id'],
                                                'region' => $shippingRegion['name'],
                                                'postcode' => (isset($OrderJSON->shipping->zipCode)) ? $OrderJSON->shipping->zipCode : 'Não especificado',
                                                'telephone' => $OrderJSON->buyer->phone,
                                                'is_default_billing' => '0',
                                                'is_default_shipping' => '1'
                                            ),
                                            '_item2' => array(
                                                'firstname' => $firstName,
                                                'lastname' => $lastName,
                                                'street' => $addressBillingFullData,
                                                'city' => (isset($OrderJSON->billingAddress->city)) ? $OrderJSON->billingAddress->city : 'Não especificado',
                                                'country_id' => $OrderJSON->billingAddress->countryAcronymNormalized,
                                                'region_id' => $billingRegion['id'],
                                                'region' => $billingRegion['name'],
                                                'postcode' => (isset($OrderJSON->billingAddress->zipCode)) ? $OrderJSON->billingAddress->zipCode : 'Não especificado',
                                                'telephone' => $OrderJSON->buyer->phone,
                                                'is_default_billing' => '1',
                                                'is_default_shipping' => '0'
                                            ),
                                        ),
                                    );

                                    $customerRet = Mage::helper('db1_anymarket/customergenerator')->createCustomer($_DataCustomer);
                                    $customer = $customerRet['customer'];

                                    $addressCustomerRet = $customerRet['addr'];
                                    $AddressShipping = $addressCustomerRet[0];
                                    if(count($addressCustomerRet) >= 2) {
                                        $AddressBilling = $addressCustomerRet[1];
                                    }
                                } else {
                                    //PERCORRE OS ENDERECOS PARA VER SE JA HA CADASTRADO O INFORMADO
                                    $needRegisterShipp = true;
                                    $needRegisterBill = true;
                                    foreach ($customer->getAddresses() as $address) {
                                        $zipCodeOrder = (isset($OrderJSON->shipping->zipCode)) ? $OrderJSON->shipping->zipCode : 'Não especificado';
                                        $addressOrder = (isset($OrderJSON->shipping->address)) ? $OrderJSON->shipping->address : 'Frete não especificado.';
                                        if (($address->getData('postcode') == $zipCodeOrder) && ($address->getData('street') == $addressOrder)) {
                                            $AddressShipping = $address;
                                            $needRegisterShipp = false;
                                        }

                                        $zipCodeOrder = (isset($OrderJSON->billingAddress->zipCode)) ? $OrderJSON->billingAddress->zipCode : 'Não especificado';
                                        $addressOrder = (isset($OrderJSON->billingAddress->address)) ? $OrderJSON->billingAddress->address : 'Frete não especificado.';
                                        if (($address->getData('postcode') == $zipCodeOrder) && ($address->getData('street') == $addressOrder)) {
                                            $AddressBilling = $address;
                                            $needRegisterBill = false;
                                        }
                                    }
                                    // PASSAR PARA UMA FUNCAO
                                    //CRIA O ENDERECO de SHIPPING CASO NAO TENHA O INFORMADO
                                    if ($needRegisterShipp) {
                                        $address = Mage::getModel('customer/address');

                                        $addressData = array(
                                            'firstname' => $firstName,
                                            'lastname' => $lastName,
                                            'street' => $addressShippingFullData,
                                            'city' => (isset($OrderJSON->shipping->city)) ? $OrderJSON->shipping->city : 'Não especificado',
                                            'country_id' => 'BR',
                                            'region' => $shippingRegion['name'],
                                            'region_id' => $shippingRegion['id'],
                                            'postcode' => (isset($OrderJSON->shipping->zipCode)) ? $OrderJSON->shipping->zipCode : 'Não especificado',
                                            'telephone' => $OrderJSON->buyer->phone
                                        );

                                        $address->setIsDefaultBilling(0);
                                        $address->setIsDefaultShipping(1);
                                        $address->addData($addressData);
                                        $address->setPostIndex('_item1');
                                        $customer->addAddress($address);
                                    }

                                    //CRIA O ENDERECO de BILLING CASO NAO TENHA O INFORMADO
                                    if ($needRegisterBill) {
                                        $address = Mage::getModel('customer/address');

                                        $addressData = array(
                                            'firstname' => $firstName,
                                            'lastname' => $lastName,
                                            'street' => $addressBillingFullData,
                                            'city' => (isset($OrderJSON->billingAddress->city)) ? $OrderJSON->billingAddress->city : 'Não especificado',
                                            'country_id' => 'BR',
                                            'region' => $billingRegion['name'],
                                            'region_id' => $billingRegion['id'],
                                            'postcode' => (isset($OrderJSON->billingAddress->zipCode)) ? $OrderJSON->billingAddress->zipCode : 'Não especificado',
                                            'telephone' => $OrderJSON->buyer->phone
                                        );

                                        $address->setIsDefaultBilling(1);
                                        $address->setIsDefaultShipping(0);
                                        $address->addData($addressData);
                                        $address->setPostIndex('_item2');
                                        $customer->addAddress($address);
                                    }
                                    $customer->save();
                                }

                                $infoMetPag = 'ANYMARKET';
                                $infoMetPagCom = array();
                                if( isset($OrderJSON->payments) ) {
                                    foreach ($OrderJSON->payments as $payment) {
                                        $infoMetPag = $payment->method;
                                        if($payment->paymentMethodNormalized) {
                                            $parcelas = isset($payment->installments) ? $payment->installments : '0';
                                            array_push($infoMetPagCom, $payment->paymentMethodNormalized." - Parcelas: ".$parcelas );
                                        }
                                    }
                                }

                                $OrderIDMage = $this->create_order($storeID, $OrderJSON, $anymarketordersSpec, $_products, $customer,
                                    $idSeqAnyMarket, $infoMetPag, $AddressBilling, $AddressShipping,
                                    implode(",", $shippingDesc) );
                                $OrderCheck = Mage::getModel('sales/order')->loadByIncrementId($OrderIDMage);

                                $this->changeFeedOrder($HOST, $headers, $idSeqAnyMarket, $tokenFeed);

                                if ($OrderCheck->getId()) {
                                    $comment = '<b>Código do Pedido no Canal de Vendas: </b>'.$OrderJSON->marketPlaceNumber.'<br>';
                                    $comment .= '<b>Id no MarketPlace: </b>'.$OrderJSON->marketPlaceId.'<br>';
                                    $comment .= '<b>Canal de Vendas: </b>'.$OrderJSON->marketPlace.'<br>';

                                    if(isset($OrderJSON->shipping->promisedShippingTime)){
                                        $dateTmpPromis =  new DateTime($OrderJSON->shipping->promisedShippingTime);
                                        $dateTmpPromis = date_format($dateTmpPromis, 'd/m/Y H:i:s');

                                        $comment .= '<b>Entrega esperada para: </b>'.$dateTmpPromis.'<br>';
                                    }

                                    if( count($infoMetPagCom) > 0 ) {
                                        foreach ($infoMetPagCom as $iMetPag) {
                                            $comment .= '<b>Forma de Pagamento: </b>' . $iMetPag . '<br>';
                                        }
                                    }else{
                                        $comment .= '<b>Forma de Pagamento: </b>Inf. não disponibilizada pelo marketplace.<br>';
                                    }

                                    $addressComp = (isset($OrderJSON->shipping->address)) ? $OrderJSON->shipping->address : 'Não especificado';
                                    $comment .= '<b>Endereço Completo: </b>'.$addressComp;

                                    $OrderCheck->addStatusHistoryComment( $comment );
                                    $OrderCheck->setEmailSent(false);
                                    $OrderCheck->save();

                                    $this->changeStatusOrder($storeID, $OrderJSON, $OrderIDMage);
                                }
                            } catch (Exception $e) {
                                $orderCheckExcpt = Mage::getModel('sales/order')->loadByIncrementId($OrderIDMage);
                                $statusExpt = $orderCheckExcpt->getId() ? 'Integrado' : 'ERROR 01';
                                $this->saveLogOrder('nmo_id_seq_anymarket',
                                    $idSeqAnyMarket,
                                    $statusExpt,
                                    'System: ' . $e->getMessage(),
                                    $idSeqAnyMarket,
                                    $IDOrderAnyMarket,
                                    $OrderIDMage,
                                    $storeID);

                                Mage::log($e, null, 'anymarket_exception.log');
                            }
                        } else {
                            $this->saveLogOrder('nmo_id_seq_anymarket',
                                $idSeqAnyMarket,
                                'ERROR 01',
                                Mage::helper('db1_anymarket')->__('Customer invalid or blank document.'),
                                $idSeqAnyMarket,
                                $IDOrderAnyMarket,
                                '',
                                $storeID);

                            $anymarketlog = Mage::getModel('db1_anymarket/anymarketlog');
                            $anymarketlog->setLogDesc('Error on import Order: ' . Mage::helper('db1_anymarket')->__('Customer invalid or blank document.'));
                            $anymarketlog->setStatus("0");
                            $anymarketlog->setStores(array($storeID));
                            $anymarketlog->save();

                            $this->addMessageInBox($storeID, Mage::helper('db1_anymarket')->__('Error on synchronize order.'),
                                Mage::helper('db1_anymarket')->__('Error synchronizing order number: ') . "Anymarket(" . $IDOrderAnyMarket . ") <br/>" .
                                Mage::helper('db1_anymarket')->__('Customer invalid or blank document.'),
                                '');
                        }
                    }
                }

                if ($tokenFeed != null && $tokenFeed != 'notoken') {
                    $paramFeed = array(
                        "token" => $tokenFeed
                    );

                    $this->CallAPICurl("PUT", $HOST . "/rest/api/v2/orders/feeds/" . $idSeqAnyMarket, $headers, $paramFeed);
                }
            }

        }else{
            $anymarketlog = Mage::getModel('db1_anymarket/anymarketlog');
            $anymarketlog->setLogDesc( 'Error on import Order: '.$idSeqAnyMarket.'  '.$returnOrderItens['return'] );
            $anymarketlog->setStatus("0");
            $anymarketlog->save();

            $this->addMessageInBox($storeID, Mage::helper('db1_anymarket')->__('Error on synchronize order.'),
                                   Mage::helper('db1_anymarket')->__('Error synchronizing order number: ')."Anymarket(".$idSeqAnyMarket.")",
                                   '');
        }
    }

    /**
     * change status feed order
     *
     * @param $HOST
     * @param $headers
     * @param $IDFeed
     * @param $tokenFeed
     */
    private function changeFeedOrder($HOST, $headers, $IDFeed, $tokenFeed){
        if($tokenFeed != 'notoken'){
            $paramsFeeds = array(
                "token" => $tokenFeed
            );

            $returnChangeTrans = $this->CallAPICurl("PUT", $HOST."/v2/orders/feeds/".$IDFeed, $headers, $paramsFeeds);
            if($returnChangeTrans['error'] == '1'){
                $anymarketlog = Mage::getModel('db1_anymarket/anymarketlog');
                $anymarketlog->setLogDesc( Mage::helper('db1_anymarket')->__('Error update feed order.'));
                $anymarketlog->setStatus("1");
                $anymarketlog->save();
            }
        }

    }

    private function checkIfCanCreateInvoice($Order){
        $continueOrder = true;
        foreach ($Order->getInvoiceCollection() as $inv) {
            $invoice = Mage::getModel('sales/order_invoice')->loadByIncrementId( $inv->getIncrementId() );
            foreach ($invoice->getCommentsCollection() as $item) {
                $CommentCurr = $item->getComment();
                if ((strpos($CommentCurr, 'Registro de Pagamento criado por Anymarket') !== false)) {
                    $continueOrder = false;
                    break;
                }

            }
        }

        return $continueOrder;
    }

    /**
     * change status order
     *
     * @param $storeID
     * @param $JSON
     * @param $IDOrderMagento
     *
     * @return boolean
     */
    private function changeStatusOrder($storeID, $JSON, $IDOrderMagento){
        $StatusPedAnyMarket = $JSON->status;

        $statsConfig = $this->getStatusAnyMarketToMageOrderConfig($storeID, $StatusPedAnyMarket );
        $stateMage  = $statsConfig["state"];
        $statusMage = $statsConfig["status"];

        if (strpos($statusMage, 'ERROR:') === false) {
            Mage::getSingleton('core/session')->setImportOrdersVariable('false');
            $order = Mage::getModel('sales/order')->loadByIncrementId( $IDOrderMagento );

            if( ($order->getData('state') == $stateMage) && ($order->getData('status') == $statusMage) ){
                return false;
            }

            if(!$this->canUpdateStatusInOrderByComment($order, $StatusPedAnyMarket)){
                return false;
            }

            $createRegPay = Mage::getStoreConfig('anymarket_section/anymarket_integration_order_group/anymarket_create_reg_pay_field', $storeID);
            $itemsarray = null;

            if( isset($JSON->invoice) && $StatusPedAnyMarket == 'INVOICED' ){
                if( $order->canInvoice() ){
                    if(isset($JSON->invoice->accessKey) ) {
                        $nfe = $JSON->invoice->accessKey;
                        $dateNfe = $JSON->invoice->date;

                        $DateTime = strtotime($dateNfe);
                        $fixedDate = date('d/m/Y H:i:s', $DateTime);

                        if($itemsarray == null) {
                            $orderItems = $order->getAllItems();
                            foreach ($orderItems as $_eachItem) {
                                $opid = $_eachItem->getId();
                                $qty = $_eachItem->getQtyOrdered();
                                $itemsarray[$opid] = $qty;
                            }
                        }

                        if (!$order->hasInvoices()) {
                            $nfeString = 'nfe:' . $nfe . ', emissao:' . $fixedDate;
                            Mage::getModel('sales/order_invoice_api')->create($order->getIncrementId(), $itemsarray, $nfeString, 0, 0);
                        }else{
                            $firstInvoiceID = $order->getInvoiceCollection()->getFirstItem()->getIncrementId();
                            $invoice = Mage::getModel('sales/order_invoice')->loadByIncrementId( $firstInvoiceID );
                            $addComment = true;
                            foreach ($invoice->getCommentsCollection() as $item) {
                                $CommentCurr = $item->getComment();
                                if ((strpos($CommentCurr, 'Adicionado por Anymarket - nfe:') !== false)) {
                                    $addComment = false;
                                    break;
                                }
                            }

                            if( $addComment ){
                                $nfeString = 'Adicionado por Anymarket - nfe:' . $nfe . ', emissao:' . $fixedDate;

                                $invoice->addComment($nfeString, "");
                                $invoice->setEmailSent(false);
                                $invoice->save();
                            }
                        }
                    }
                }
            }

            if( isset($JSON->tracking) && $StatusPedAnyMarket == 'PAID_WAITING_DELIVERY' ){
                if( $order->canShip() && !$order->hasShipments() ){
                    if(isset($JSON->tracking->number)) {
                        $TrNumber = $JSON->tracking->number;
                        $TrCarrier = strtolower($JSON->tracking->carrier);

                        $shipmentId = Mage::getModel('sales/order_shipment_api')->create($order->getIncrementId(), $itemsarray, 'Create by AnyMarket', false, 1);

                        $TracCodeArr = Mage::getModel('sales/order_shipment_api')->getCarriers($order->getIncrementId());
                        if (isset($TracCodeArr[$TrCarrier])) {
                            Mage::getModel('sales/order_shipment_api')->addTrack($shipmentId, $TrCarrier, $TrCarrier, $TrNumber);
                        } else {
                            $arrVar = array_keys($TracCodeArr);
                            Mage::getModel('sales/order_shipment_api')->addTrack($shipmentId, array_shift($arrVar), 'Não Econtrado(' . $TrCarrier . ')', $TrNumber);
                        }
                    }
                }
            }

            $order->setData('state', $stateMage);
            $order->setStatus($statusMage, true);

            $orderComment = '<br>Status Anymarket: '.$StatusPedAnyMarket;
            if($stateMage == Mage_Sales_Model_Order::STATE_COMPLETE){
                $orderComment = 'Finalizado pelo AnyMarket.'.$orderComment;
            }else{
                $orderComment = 'Status alterado pelo Anymarket.'.$orderComment;
            }
            $history = $order->addStatusHistoryComment($orderComment, false);
            $history->setIsCustomerNotified(false);

            if($stateMage == Mage_Sales_Model_Order::STATE_CANCELED) {
                foreach ($order->getAllItems() as $item) {
                    $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct( $item->getProductId() );
                    if ($stockItem->getManageStock()) {
                        $stockItem->setData('qty', $stockItem->getQty() + $item->getQtyOrdered());
                    }
                    $stockItem->setData('is_in_stock', 1);
                    $stockItem->save();

                    $item->setQtyCanceled($item->getQtyOrdered());
                    $item->save();
                }
            }
            $order->save();

            if( $createRegPay == "1" && $StatusPedAnyMarket == 'PAID_WAITING_SHIP' ){
                if( $order->canInvoice() ){
                    if( $this->checkIfCanCreateInvoice($order) ) {
                        $orderItems = $order->getAllItems();
                        foreach ($orderItems as $_eachItem) {
                            $opid = $_eachItem->getId();
                            $qty = $_eachItem->getQtyOrdered();
                            $itemsarray[$opid] = $qty;
                        }
                        $nfeString = "Registro de Pagamento criado por Anymarket";
                        Mage::getModel('sales/order_invoice_api')->create($order->getIncrementId(), $itemsarray, $nfeString, 0, 0);
                    }
                }
            }

            $this->saveLogOrder('nmo_id_anymarket',
                                 $JSON->marketPlaceId, 
                                 'Integrado', 
                                 '', 
                                 $JSON->id, 
                                 $JSON->marketPlaceId, 
                                 $IDOrderMagento, 
                                 $storeID);

            $anymarketlog = Mage::getModel('db1_anymarket/anymarketlog');
            $anymarketlog->setLogDesc('Order Updated: ' . $IDOrderMagento . ' ID Anymarket: ' . $JSON->marketPlaceId . ' Status: ' . $statusMage);
            $anymarketlog->setStatus("0");
            $anymarketlog->setStores(array($storeID));
            $anymarketlog->save();

            Mage::getSingleton('core/session')->setImportOrdersVariable('true');
        }
    }

    /**
     * get all info from invoice
     *
     * @param $start
     * @param $end
     * @param $comment
     * @return string
     */
    public function procCommentInvoiceOrder($start, $end, $comment){
        $vCtrlStart = '';
        $vKey = '';
        $bCtrlComment = false;
        for ($i=0; $i < strlen($comment) ; $i++) {
            if( $bCtrlComment ){
                $vKey .= $comment[$i];
                if (strpos($vKey, $end) !== false ) {
                   return  str_replace($end, "", $vKey);
                }
            }else {
                $vCtrlStart .= $comment[$i];
                if (strpos($vCtrlStart, $start) !== false ) {
                    $bCtrlComment = true;
                }
            }
        }

        return $vKey != '' ? $vKey : null;
    }
    
    /**
     * get invoice order from custom model
     *
     * @param $storeID
     * @param $comment
     * @return array
     */
    public function getFromCustomInvoiceModel($storeID, $comment){
        $customModel = Mage::getStoreConfig('anymarket_section/anymarket_integration_order_group/anymarket_custom_invoice_field', $storeID);
        $metCustomModel = Mage::getStoreConfig('anymarket_section/anymarket_integration_order_group/anymarket_capture_invoice_type_field', $storeID);

        $comment = str_replace( array("<br>", "</br>", "<br/>", "<b>") , "", $comment);

        $returnArr = array();
        preg_match_all ('/<ci[^>]*?>([^`]*?)<:ci>/', $customModel, $arrKeyIniChave, PREG_SET_ORDER);
        preg_match_all ('/<cf[^>]*?>([^`]*?)<:cf>/', $customModel, $arrKeyFimChave, PREG_SET_ORDER);

        preg_match_all ('/<ni[^>]*?>([^`]*?)<:ni>/', $customModel, $arrKeyIniNum, PREG_SET_ORDER);
        preg_match_all ('/<nf[^>]*?>([^`]*?)<:nf>/', $customModel, $arrKeyFimNum, PREG_SET_ORDER);

        preg_match_all ('/<di[^>]*?>([^`]*?)<:di>/', $customModel, $arrKeyIniData, PREG_SET_ORDER);
        preg_match_all ('/<df[^>]*?>([^`]*?)<:df>/', $customModel, $arrKeyFimData, PREG_SET_ORDER);

        $comment = str_replace( array("<ci>", "<:ci>", "<cf>", "<:cf>", "<ni>", "<:ni>", "<nf>", "<:nf>", "<di>", "<:di>", "<df>", "<:df>") , "", $comment);

        if( isset($arrKeyIniChave[0]) && isset($arrKeyFimChave[0]) ) {
            $chaveSt = $arrKeyIniChave[0][1];
            $chaveEn = $arrKeyFimChave[0][1];

            if( $metCustomModel == '1' ) {
                $rKey = $this->procCommentInvoiceOrder($chaveSt, $chaveEn, $comment);
                if($rKey) {
                    $returnArr['key'] = $rKey;
                }
            }else {
                $posStart = strpos($comment, $chaveSt);
                $commentTrat = substr($comment, $posStart, strlen($comment));
                $posEnd = strpos($commentTrat, $chaveEn);
                if ($posStart !== false) {
                    $posStartT = $posStart + strlen($chaveSt);
                    if ($posEnd !== false) {
                        $posEndT = $posEnd - strlen($chaveSt);
                        $returnArr['key'] = substr($comment, $posStartT, $posEndT);

                        $posEnd += strlen($chaveEn);
                    } else {
                        $returnArr['key'] = substr($comment, $posStartT, strlen($comment));
                    }
                    $comment = str_replace(substr($comment, $posStart, $posEnd), "", $comment);
                }
            }
        }

        if( isset($arrKeyIniNum[0]) && isset($arrKeyFimNum[0]) ) {
            $numSt = $arrKeyIniNum[0][1];
            $numEn = $arrKeyFimNum[0][1];

            if( $metCustomModel == '1' ) {
                $rNum = $this->procCommentInvoiceOrder($numSt, $numEn, $comment);
                if($rNum) {
                    $returnArr['number'] = $rNum;
                }
            }else {
                $posStart = strpos($comment, $numSt);
                $commentTrat = substr($comment, $posStart, strlen($comment));
                $posEnd = strpos($commentTrat, $numEn);

                if ($posStart !== false && $posEnd !== false) {
                    $posStartT = $posStart + strlen($numSt);
                    $posEndT = $posEnd - strlen($numSt);
                    $returnArr['number'] = substr($comment, $posStartT, $posEndT);

                    $posEnd += strlen($numEn);
                    $comment = str_replace(substr($comment, $posStart, $posEnd), "", $comment);
                }
            }
        }

        if( isset($arrKeyIniData[0]) && isset($arrKeyFimData[0]) ) {
            $dateSt = $arrKeyIniData[0][1];
            $dateEn = $arrKeyFimData[0][1];
            if( $metCustomModel == '1' ) {
                $rDate = $this->procCommentInvoiceOrder($dateSt, $dateEn, $comment);
                if($rDate) {
                    $returnArr['date'] = $rDate;
                }
            }else {
                $posStart = strpos($comment, $dateSt);
                $commentTrat = substr($comment, $posStart, strlen($comment));
                $posEnd = strpos($commentTrat, $dateEn);
                if ($posStart !== false && $posEnd !== false) {
                    $posStart += strlen($dateSt);
                    $posEnd -= strlen($dateSt);

                    $date = substr($comment, $posStart, $posEnd);
                    $dateTmp = str_replace("/", "-", $date);

                    $returnArr['date'] = $this->formatDateTimeZone($dateTmp);
                }
            }
        }

        return $returnArr;
    }

    /**
     * get invoice order from default model
     *
     * @param $CommentCurr
     * @return array
     */
    public function procInvoiceModelsToAnymarket($CommentCurr){
        $nfeCount = strpos($CommentCurr, 'nfe:');
        $emissaoCount = strpos($CommentCurr, 'emiss');
        if( (strpos($CommentCurr, 'nfe:') !== false) && (strpos($CommentCurr, 'emiss') !== false) ) {
            $caracts = array("/", "-", ".");
            $nfeTmp = str_replace($caracts, "", $CommentCurr );
            $chaveAcID = substr( $nfeTmp, $nfeCount+4, 44);

            $date = substr( $CommentCurr, $emissaoCount+8, 19);
            $dateTmp = str_replace("/", "-", $date );

            $date = $this->formatDateTimeZone($dateTmp);
            return array("key" => $chaveAcID, "date" => $date);
        }else{
            return null;
        }
    }

    /**
     * get invoice order
     *
     * @param $Order
     * @return array
     */
    public function getInvoiceOrder($Order, $storeID){
        $nfeID = "";
        $date = "";
        $chaveAcID = "";
        if ($Order->hasInvoices()) {
            foreach ($Order->getInvoiceCollection() as $inv) {
                $invoice = Mage::getModel('sales/order_invoice')->loadByIncrementId( $inv->getIncrementId() );
                foreach ($invoice->getCommentsCollection() as $item) {
                    $CommentCurr = $item->getComment();
                    $invData = $this->procInvoiceModelsToAnymarket($CommentCurr);
                    if( $invData ){
                        $nfeID = $invData["key"];
                        $chaveAcID = $invData["key"];
                        $date = $invData["date"];
                        break;
                    }

                    $customModel = $this->getFromCustomInvoiceModel($storeID, $CommentCurr);
                    if( isset($customModel['key']) ){
                        $nfeID = isset($customModel['number']) ? $customModel["number"] : $customModel["key"];
                        $chaveAcID = $customModel["key"];

                        if( !isset($customModel['date']) ) {
                            $dateTmp = new DateTime(str_replace("/", "-", $item->getData('created_at')));
                            $date =  date_format($dateTmp, 'Y-m-d\TH:i:s\Z');
                        }else{
                            $date = $customModel["date"];
                        }
                        break;
                    }
                }
            }
        }

        if( $chaveAcID == "" ) {
            foreach ($Order->getStatusHistoryCollection() as $item) {
                $CommentCurr = $item->getComment();

                $invData = $this->procInvoiceModelsToAnymarket($CommentCurr);
                if( $invData ){
                    $nfeID = $invData["key"];
                    $chaveAcID = $invData["key"];
                    $date = $invData["date"];
                    break;
                }

                $customModel = $this->getFromCustomInvoiceModel($storeID, $CommentCurr);
                if( isset($customModel['key']) ){
                    $nfeID = isset($customModel['number']) ? $customModel["number"] : $customModel["key"];
                    $chaveAcID = $customModel["key"];

                    if( !isset($customModel['date']) ) {
                        $dateTmp = new DateTime(str_replace("/", "-", $item->getData('created_at')));
                        $date =  date_format($dateTmp, 'Y-m-d\TH:i:s\Z');
                    }else{
                        $date = $customModel["date"];
                    }
                    break;
                }

                $CommentCurr = str_replace(array(" ", "<b>", "</b>"), "", $CommentCurr);
                $CommentCurr = str_replace(array("<br>"), "<br/>", $CommentCurr);
                $chaveAcesso = strpos($CommentCurr, 'ChavedeAcesso:');
                if ((strpos($CommentCurr, 'ChavedeAcesso:') !== false)) {
                    $chaveAcID = substr($CommentCurr, $chaveAcesso + 14, 44);

                    $notaFiscal = strpos($CommentCurr, 'Notafiscal:');
                    if ((strpos($CommentCurr, 'Notafiscal:') !== false)) {
                        $endNF = strpos($CommentCurr, '<br/>');
                        $nfeID = substr($CommentCurr, $notaFiscal + 11, $endNF - 11);

                        if ($nfeID == "") {
                            $nfeID = $chaveAcID;
                        }
                    } else {
                        $notaFiscal = strpos($CommentCurr, 'NrNF-e');
                        if ($notaFiscal !== false) {
                            $endNF = strpos($CommentCurr, '<br/>');
                            $nfeID = substr($CommentCurr, $notaFiscal + 6, $endNF - 6);

                            if ($nfeID == "") {
                                $nfeID = $chaveAcID;
                            }
                        }
                    }

                    $dateTmp = new DateTime(str_replace("/", "-", $item->getData('created_at')));
                    $date = date_format($dateTmp, 'Y-m-d\TH:i:s\Z');
                    break;
                }
            }
        }

        if( $chaveAcID == "" && $Order->hasShipments() ) {
            foreach ($Order->getShipmentsCollection() as $ship) {
                $shippment = Mage::getModel('sales/order_shipment')->loadByIncrementId( $ship->getIncrementId() );
                foreach ($shippment->getCommentsCollection() as $item) {
                    $CommentCurr = $item->getComment();
                    $invData = $this->procInvoiceModelsToAnymarket($CommentCurr);
                    if( $invData ){
                        $nfeID = $invData["key"];
                        $chaveAcID = $invData["key"];
                        $date = $invData["date"];
                        break;
                    }

                    $customModel = $this->getFromCustomInvoiceModel($storeID, $CommentCurr);
                    if( isset($customModel['key']) ){
                        $nfeID = isset($customModel['number']) ? $customModel["number"] : $customModel["key"];
                        $chaveAcID = $customModel["key"];

                        if( !isset($customModel['date']) ) {
                            $dateTmp = new DateTime(str_replace("/", "-", $item->getData('created_at')));
                            $date =  date_format($dateTmp, 'Y-m-d\TH:i:s\Z');
                        }else{
                            $date = $customModel["date"];
                        }
                        break;
                    }

                }
            }
        }
        return array("number" => $nfeID, "date" => $date, "accessKey" => $chaveAcID);
    }

    /**
     * get tracking order
     *
     * @param $Order
     * @param $statuAM
     * @return array
     */
    public function getTrackingOrder($Order, $statuAM){
        $TrackNum = '';
        $TrackTitle = '';
        $TrackCreate = '';
        $dateTrack = '';
        $datesRes = array("", "");
        $shipmentCollection = Mage::getResourceModel('sales/order_shipment_collection')
                                                    ->setOrderFilter($Order)
                                                    ->load();
        foreach ($shipmentCollection as $shipment){
            $datesRes = $this->getDatesFromShipping($shipment);
            foreach($shipment->getAllTracks() as $tracknum){
                $TrackNum = $tracknum->getNumber();
                $TrackTitle = $tracknum->getTitle();
                $TrackCreate = $tracknum->getCreatedAt();

                $dateTmp =  new DateTime(str_replace("/", "-", $TrackCreate ));
                $dateTrack = date_format($dateTmp, 'Y-m-d\TH:i:s\Z');
            }
        }

        $retArray = array("number" => $TrackNum,
                             "carrier" => $TrackTitle,
                             "date" => $dateTrack,
                             "url" => "");

        $retArray["shippedDate"]  = ($datesRes[1] != "") ? $datesRes[1] : $dateTrack;
        if($datesRes[0] != "") {
            $retArray["estimateDate"] = $datesRes[0];
        }else{
            $estFromOrder = $this->getEstimatedDateFromOrder($Order);
            if($estFromOrder != ""){
                $retArray["estimateDate"] = $this->formatDateTimeZone(str_replace("/", "-", $estFromOrder ));
            }
        }

        $deliveredDate = $this->getDeliveredDateFromOrder( $Order, $statuAM );
        if( $deliveredDate ){
            $retArray['deliveredDate'] = $this->formatDateTimeZone(str_replace("/", "-", $deliveredDate ));
        }

        return $retArray;
    }

    /**
     * update or create order in AM
     *
     * @param $Order
     */
    public function updateOrCreateOrderAnyMarket($storeID, $Order)
    {
        $ConfigOrder = Mage::getStoreConfig('anymarket_section/anymarket_integration_order_group/anymarket_type_order_sync_field', $storeID);
        $canUpdateOrder = $this->updateOrderAnymarket($storeID, $Order);
        if (!$canUpdateOrder && $ConfigOrder == 0) {
            $this->sendOrderToAnyMarket($storeID, $Order);
        }
    }

    /**
     * update order in AM
     *
     * @param $storeID
     * @param $Order
     *
     * @return bool
     */
    public function updateOrderAnymarket($storeID, $Order){
        $idOrder = $Order->getIncrementId();
        $anymarketorderupdt = Mage::getModel('db1_anymarket/anymarketorders')->load($idOrder, 'nmo_id_order');

        if($anymarketorderupdt->getData('nmo_id_seq_anymarket') == null ||
           $anymarketorderupdt->getData('nmo_id_seq_anymarket') == ""){
            return false;
        }

        if( ($anymarketorderupdt->getData('nmo_status_int') != 'ERROR 02') &&
            ($anymarketorderupdt->getData('nmo_status_int') != 'Integrado')){
            return false;
        }

        $HOST  = Mage::getStoreConfig('anymarket_section/anymarket_acesso_group/anymarket_host_field', $storeID);
        $TOKEN = Mage::getStoreConfig('anymarket_section/anymarket_acesso_group/anymarket_token_field', $storeID);

        $status = $Order->getStatus();
        $statuAM = $this->getStatusMageToAnyMarketOrderConfig($storeID, $status);
        if (strpos($statuAM, 'ERROR:') !== false) {
            return false;
        }

        if($statuAM == "PENDING" ){
            return false;
        }

        $headers = array(
            "Content-type: application/json",
            "Accept: */*",
            "gumgaToken: ".$TOKEN
        );

        $params = array(
            "status" => $statuAM
        );

        $invoiceData = $this->getInvoiceOrder($Order, $storeID);
        if ($invoiceData['accessKey'] != '') {
            $params["invoice"] = $invoiceData;
        }else if($statuAM == "INVOICED"){
            $anymarketlog = Mage::getModel('db1_anymarket/anymarketlog');
            $anymarketlog->setLogDesc( Mage::helper('db1_anymarket')->__('Informação de Nota Fiscal é obrigatória no pedido para Atualizar como Faturado.') );
            $anymarketlog->setLogId( $idOrder );
            $anymarketlog->setLogJson('');
            $anymarketlog->setStores(array($storeID));
            $anymarketlog->setStatus("0");
            $anymarketlog->save();

            return false;
        }

        $trackingData = $this->getTrackingOrder($Order, $statuAM);
        if ($trackingData['number'] != '') {
            $params["tracking"] = $trackingData;
        }

        if( isset( $params["tracking"] ) && $statuAM == "CONCLUDED" ){
            $deliveredDate = $params["tracking"];
            if( !isset($deliveredDate['deliveredDate']) ){
                $anymarketlog = Mage::getModel('db1_anymarket/anymarketlog');
                $anymarketlog->setLogDesc( Mage::helper('db1_anymarket')->__('Informação de Data de Entrega é obrigatória nos comentários do pedido.') );
                $anymarketlog->setLogId( $idOrder );
                $anymarketlog->setLogJson('');
                $anymarketlog->setStores(array($storeID));
                $anymarketlog->setStatus("0");
                $anymarketlog->save();

                return false;
            }
        }

        if(  ($statuAM != "PENDING" ) || isset($params["tracking"]) || isset($params["invoice"]) ){
            $IDOrderAnyMarket = $anymarketorderupdt->getData('nmo_id_seq_anymarket');

            $returnOrder = $this->CallAPICurl("PUT", $HOST."/v2/orders/".$IDOrderAnyMarket, $headers, $params);
            if($returnOrder['error'] == '1'){
                $anymarketorderupdt->setStatus("0");
                $anymarketorderupdt->setNmoStatusInt('ERROR 02');
                $anymarketorderupdt->setNmoDescError($returnOrder['return']);
                $anymarketorderupdt->setStores(array($storeID));
                $anymarketorderupdt->save();
            }

            $anymarketlog = Mage::getModel('db1_anymarket/anymarketlog');
            $anymarketlog->setLogDesc( json_encode($returnOrder['return']) );
            $anymarketlog->setLogId( $idOrder );
            $anymarketlog->setLogJson( json_encode($returnOrder['json']) );
            $anymarketlog->setStores(array($storeID));
            $anymarketlog->setStatus("0");
            $anymarketlog->save();
        }else{
            $anymarketlog = Mage::getModel('db1_anymarket/anymarketlog');
            $anymarketlog->setLogDesc( Mage::helper('db1_anymarket')->__('There was some error getting data Invoice or Tracking.') );
            $anymarketlog->setLogId( $idOrder );
            $anymarketlog->setLogJson('');
            $anymarketlog->setStores(array($storeID));
            $anymarketlog->setStatus("0");
            $anymarketlog->save();
        }

        return true;
    }

    /**
     * send order to AM
     *
     * @param $storeID
     * @param $Order
     *
     * @return boolean
     */
    public function sendOrderToAnyMarket($storeID, $Order){
        $anymarketorderupdt = Mage::getModel('db1_anymarket/anymarketorders')->load($Order->getIncrementId(), 'nmo_id_order');
        $seqIdAnymarket = $anymarketorderupdt->getData('nmo_id_seq_anymarket');
        if( $seqIdAnymarket != '' || $seqIdAnymarket != null ){
            return false;
        }

        $ConfigOrder = Mage::getStoreConfig('anymarket_section/anymarket_integration_order_group/anymarket_type_order_sync_field', $storeID);
        if($ConfigOrder == 0 && $Order->getIncrementId() != null){
            $idOrder = $Order->getIncrementId();
            $HOST  = Mage::getStoreConfig('anymarket_section/anymarket_acesso_group/anymarket_host_field', $storeID);
            $TOKEN = Mage::getStoreConfig('anymarket_section/anymarket_acesso_group/anymarket_token_field', $storeID);

            //TRATA OS ITEMS
            $orderedItems = $Order->getAllVisibleItems();
            $orderedProductIds = array();

            foreach ($orderedItems as $item) {
                $orderedProductIds[] = array(
                    "sku" => array(
                        "partnerId" => $item->getData('sku')
                    ),
                    "amount" => $item->getData('qty_ordered'),
                    "unit" => $item->getData('original_price'),
                    "discount" => $item->getData('discount_amount')
                );
            }

            //OBTEM OS DADOS DO PAGAMENTO
            $payment = $Order->getPayment();

            //OBTEM OS DADOS DA ENTREGA
            $shipping = $Order->getShippingAddress();
            $billing  = $Order->getBillingAddress();

            $docField = strtolower(Mage::getStoreConfig('anymarket_section/anymarket_attribute_group/anymarket_doc_type_field', $storeID));
            $docData = "";
            if(!$Order->getCustomerIsGuest() || $Order->getCustomerId() != null ){
                $customer = Mage::getModel("customer/customer")->load($Order->getCustomerId());
                $docData = $customer->getData( $docField );
            }

            if( $docData == "" ){
                if($Order->getCustomerTaxvat()){
                    $docData = $Order->getCustomerTaxvat();
                }
            }

            $statusOrder = $Order->getStatus();
            if($statusOrder == 'pending'){
                $statuAM = $this->getStatusMageToAnyMarketOrderConfig($storeID, 'new');
            }else{
                $statuAM = $this->getStatusMageToAnyMarketOrderConfig($storeID, $statusOrder);
            }


            if( (strpos($statuAM, 'ERROR:') !== false) || ($statuAM == '') ) {
                return false;
            }
            $dateTmp =  new DateTime(str_replace("/", "-", $Order->getData('created_at') ));
            $params = array(
                'marketPlaceId' => $idOrder,
                "createdAt" => date_format($dateTmp, 'Y-m-d\TH:i:s\Z'),
                "status" =>  $statuAM,
                "marketPlace" => "ECOMMERCE",
                "marketPlaceStatus" => $statuAM,
                "marketPlaceUrl" => null,
                "shipping" => array(
                    "city" => $shipping->getCity(),
                    "state" => $shipping->getRegion(),
                    "country" => $shipping->getCountry(),
                    "address" => $shipping->getStreetFull(),
                    "street" =>  $shipping->getStreet(1),
                    "number" =>  $shipping->getStreet(2),
                    "comment" =>  $shipping->getStreet(3),
                    "neighborhood" =>  $shipping->getStreet(4),
                    "zipCode" => $shipping->getPostcode()
                ),
                "billingAddress" => array(
                    "city" => $billing->getCity(),
                    "state" => $billing->getRegion(),
                    "country" => $billing->getCountry(),
                    "address" => $billing->getStreetFull(),
                    "street" =>  $billing->getStreet(1),
                    "number" =>  $billing->getStreet(2),
                    "comment" =>  $billing->getStreet(3),
                    "neighborhood" =>  $billing->getStreet(4),
                    "zipCode" => $billing->getPostcode()
                ),
                "buyer" => array(
                    "id" => 0,
                    "name" => $Order->getCustomerFirstname()." ".$Order->getCustomerLastname(),
                    "email" => $Order->getCustomerEmail(),
                    "document" =>  $docData,
                    "documentType" => $this->getDocumentType($docData),
                    "phone" => $shipping->getTelephone(),
                ),
                "items" => $orderedProductIds,
                "payments" => array(
                                array(
                                    "method" => $payment->getMethodInstance()->getTitle(),
                                    "status" => "",
                                    "value" => $Order->getBaseGrandTotal()
                                ),
                ),
                "discount" => floatval( $Order->getDiscountAmount() ) < 0 ? floatval( $Order->getDiscountAmount() )*-1 : $Order->getDiscountAmount(),
                "freight" => $Order->getShippingAmount(),
                "gross" => $Order->getBaseSubtotal(),
                "total" => $Order->getBaseGrandTotal()
            );

            $arrTracking = $this->getTrackingOrder($Order, $statuAM);
            $arrInvoice = $this->getInvoiceOrder($Order, $storeID);

            if($arrTracking["number"] != ''){
                $params["tracking"] = $arrTracking;
            };

            if($arrInvoice["number"] != ''){
                $params["invoice"] = $arrInvoice;
            };

            $headers = array(
                "Content-type: application/json",
                "Accept: */*",
                "gumgaToken: ".$TOKEN
            );

            $returnOrder = $this->CallAPICurl("POST", $HOST."/v2/orders/", $headers, $params);

            $anymarketlog = Mage::getModel('db1_anymarket/anymarketlog');
            $anymarketlog->setLogDesc( json_encode($returnOrder['return']) );

            $anymarketorders = Mage::getModel('db1_anymarket/anymarketorders')->load($idOrder, 'nmo_id_order');
            $anymarketorders->setStatus("1");
            $anymarketorders->setStores(array($storeID));
            if($returnOrder['error'] == '1'){
                if( strpos($returnOrder['return'], 'existe uma venda de ECOMMERCE cadastrada com o') === false ) {
                    $anymarketorders->setNmoStatusInt('ERROR 02');
                    $anymarketorders->setNmoDescError($returnOrder['return']);
                }else{
                    $OrderResp = json_decode($returnOrder['return']);

                    $IDAnymarket = strpos($OrderResp, 'ID Anymarket');
                    if ($IDAnymarket !== false) {
                        $idAnymarketOrder = substr($OrderResp, $IDAnymarket+14, 100);
                        $idAnymarketOrder = str_replace(']"}', "", $idAnymarketOrder );

                        if( is_numeric ($idAnymarketOrder) ) {
                            $anymarketorders->setNmoStatusInt('Integrado');
                            $anymarketorders->setNmoDescError('');
                            $anymarketorders->setNmoIdSeqAnymarket($idAnymarketOrder);
                            $anymarketorders->setNmoIdOrder($idOrder);
                            $anymarketorders->setNmoIdAnymarket($idOrder);
                            $anymarketorders->save();

                            $anymarketlog->setStores(array($storeID));
                            $anymarketlog->setLogDesc("Pedido encontrado [" . $idAnymarketOrder . "] e realizado o relacionamento.");
                            $anymarketlog->setStatus("0");
                            $anymarketlog->save();

                            $OrderRetry = Mage::getModel('sales/order')->loadByIncrementId($idOrder);
                            $this->updateOrCreateOrderAnyMarket($storeID, $OrderRetry);
                            return $this;
                        }
                    }
                }
            }else{
                $retOrderJSON = $returnOrder['return'];
                $anymarketorders->setNmoStatusInt('Integrado');
                $anymarketorders->setNmoDescError('');
                $anymarketorders->setNmoIdAnymarket( $retOrderJSON->marketPlaceId );
                $anymarketorders->setNmoIdSeqAnymarket( $retOrderJSON->id );

                $anymarketlog->setLogId( $retOrderJSON->marketPlaceId );
            }

            $anymarketlog->setStores(array($storeID));
            $anymarketlog->setLogJson( $returnOrder['json'] );
            $anymarketlog->setStatus("0");
            $anymarketlog->save();

            $anymarketorders->setNmoIdOrder($idOrder);
            $anymarketorders->save();
        }

    }

    /**
     * List Order from AnyMarket
     *
     * @return int
     */
    public function listOrdersFromAnyMarketMagento($storeID){
        $HOST  = Mage::getStoreConfig('anymarket_section/anymarket_acesso_group/anymarket_host_field', $storeID);
        $TOKEN = Mage::getStoreConfig('anymarket_section/anymarket_acesso_group/anymarket_token_field', $storeID);

        $headers = array(
            "Content-type: application/json",
            "Accept: */*",
            "gumgaToken: ".$TOKEN
        );

        $startRec = 0;
        $countRec = 1;
        $arrOrderCod = null;

        $contPed = 0;
        while ($startRec <= $countRec) {
            $returnOrder = $this->CallAPICurl("GET", $HOST."/v2/orders/?offset=".$startRec."&limit=30", $headers, null);
            if($returnOrder['error'] == '1'){
                $startRec = 1;
                $countRec = 0;

                $anymarketlog = Mage::getModel('db1_anymarket/anymarketlog');
                $anymarketlog->setLogDesc( Mage::helper('db1_anymarket')->__('Error on import order from anymarket '). $returnOrder['return'] );
                $anymarketlog->setStatus("0");
                $anymarketlog->save();
            }else {
                $JsonReturn = $returnOrder['return'];

                $startRec = $startRec + $JsonReturn->page->size;
                $countRec = $JsonReturn->page->totalElements;

                foreach ($JsonReturn->content as $value) {
                    $IDOrderAnyMarket = $value->marketPlaceId;

                    $statsConfig = $this->getStatusAnyMarketToMageOrderConfig($storeID, $value->status);
                    $statusMage = $statsConfig["status"];
                    if (strpos($statusMage, 'ERROR:') === false) {
                        $anymarketorders = Mage::getModel('db1_anymarket/anymarketorders')->setStoreId($storeID);
                        $anymarketorders->load($IDOrderAnyMarket, 'nmo_id_anymarket');
                        if ($anymarketorders->getData('nmo_id_anymarket') == null || (is_array($anymarketorders->getData('store_id')) && !in_array($storeID, $anymarketorders->getData('store_id')))) {
                            $idAnyMarket = $value->id;

                            $anymarketorders = Mage::getModel('db1_anymarket/anymarketorders');
                            $anymarketorders->setStatus("0");
                            $anymarketorders->setNmoStatusInt('Não integrado (AnyMarket)');
                            $anymarketorders->setNmoDescError('');
                            $anymarketorders->setNmoIdSeqAnymarket($idAnyMarket);
                            $anymarketorders->setNmoIdAnymarket($IDOrderAnyMarket);
                            $anymarketorders->setNmoIdOrder('');
                            $anymarketorders->setStores(array($storeID));
                            $anymarketorders->save();

                            $contPed = $contPed + 1;
                        }

                    }
                }
            }
        }

        $salesCollection = Mage::getModel("sales/order")->getCollection();
        foreach($salesCollection as $order){
            $orderId = $order->getIncrementId();
            $storeID = $order->getStoreId();

            $anymarketorders = Mage::getModel('db1_anymarket/anymarketorders')->setStoreId($storeID);
            $anymarketorders->load($orderId, 'nmo_id_order');
            if($anymarketorders->getData('nmo_id_order') == null || (is_array($anymarketorders->getData('store_id')) && !in_array($storeID, $anymarketorders->getData('store_id')) ) ){
                $anymarketorders = Mage::getModel('db1_anymarket/anymarketorders');
                $anymarketorders->setStatus("0");
                $anymarketorders->setNmoStatusInt('Não integrado (Magento)');
                $anymarketorders->setNmoDescError('');
                $anymarketorders->setNmoIdOrder( $orderId );
                $anymarketorders->setStores(array($storeID));
                $anymarketorders->save();

                $contPed = $contPed+1;
            }
        }

        return $contPed;

    }

    /**
     * @param $OrderJSON
     * @param $addType
     * @return array
     */
    public function getStateNormalized($OrderJSON, $addType) {
        $OrderJSON = json_decode(json_encode($OrderJSON), true);

        $regionCollection = Mage::getModel('directory/region')->getCollection();
        $regionNameAcro = (isset($OrderJSON[$addType]['state'])) ? $OrderJSON[$addType]['state'] : 'Não especificado';
        $regionName = (isset($OrderJSON[$addType]['stateNameNormalized'])) ? $OrderJSON[$addType]['stateNameNormalized'] : 'Não especificado';
        $region = array('id' => 0, 'name' => $regionName);
        foreach ($regionCollection as $key) {
            if ($key->getData('name') == $regionName || $key->getData('name') == $regionNameAcro) {
                return array('id' => $key->getData('region_id'), 'name' => $key->getData('name'));
            }
        }

        return $region;
    }

}
