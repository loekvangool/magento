<?php

class DB1_AnyMarket_Helper_OrderGenerator extends DB1_AnyMarket_Helper_Data
{
    const CUSTOMER_RANDOM = null;
    
    protected $_shippingMethod = 'freeshipping_freeshipping';
    protected $_AdditionalInformation = 'anymarket';    
    protected $_paymentMethod = 'cashondelivery';
    protected $_shippingDescription = 'Forma de Entrega - Anymarket.';
    protected $_billing = null;
    protected $_shipping = null;
    protected $_shippingValue = 0;
    protected $_cpfcnpj = null;

    protected $_customer = self::CUSTOMER_RANDOM;

    protected $_subTotal = 0;
    protected $_order;
    public $_storeId;

    /**
     * @param $value
     */
    public function setCpfCnpj($value)
    {
        $this->_cpfcnpj = $value;
    }

    /**
     * @param $value
     */
    public function setShippingValue($value)
    {
        $this->_shippingValue = $value;
    }

    /**
     * @param $methodName
     */
    public function setShippingMethod($methodName)
    {
        $this->_shippingMethod = $methodName;
    }

    /**
     * @param $methodName
     */
    public function setPaymentMethod($methodName)
    {
        $this->_paymentMethod = $methodName;
    }

    /**
     * @param $billing
     */
    public function setBillAddress($billing)
    {
        $this->_billing = $billing;
    }

    /**
     * @param $shipping
     */
    public function setShipAddress($shipping)
    {
        $this->_shipping = $shipping;
    }

    /**
     * @param $addInfo
     */
    public function setAdditionalInformation($addInfo)
    {
        $this->_AdditionalInformation = $addInfo;
    }

    /**
     * @param $shippingDesc
     */
    public function setShippingDescription($shippingDesc)
    {
        $this->_shippingDescription = $shippingDesc;
    }

    /**
     * set customer of order
     *
     * @param $customer
     */
    public function setCustomer($customer)
    {
        if ($customer instanceof Mage_Customer_Model_Customer){
            $this->_customer = $customer;
        }
        if (is_numeric($customer->getId())){
            $this->_customer = Mage::getModel('customer/customer')->load($customer->getId());
        }
    }

    /**
     * create order in MG
     *
     * @param $products
     * @return int
     */
    public function createOrder($storeID, $products)
    {
        if (!($this->_customer instanceof Mage_Customer_Model_Customer)){
            $this->setCustomer(self::CUSTOMER_RANDOM);
        }

        $transaction = Mage::getModel('core/resource_transaction');

        if(!$this->_storeId){
            $this->_storeId = $this->_customer->getStoreId();
        }

        $reservedOrderId = Mage::getSingleton('eav/config')
            ->getEntityType('order')
            ->fetchNewIncrementId($this->_storeId);

        $currencyCode  = Mage::app()->getBaseCurrencyCode();
        $this->_order = Mage::getModel('sales/order')
            ->setIncrementId($reservedOrderId)
            ->setStoreId($this->_storeId)
            ->setQuoteId(0)
            ->setDiscountAmount(0)
            ->setShippingAmount((float)$this->_shippingValue)
            ->setShippingTaxAmount(0)
            ->setBaseDiscountAmount(0)
            ->setIsVirtual(0)
            ->setBaseShippingAmount((float)$this->_shippingValue)
            ->setBaseShippingTaxAmount(0)
            ->setBaseTaxAmount(0)
            ->setBaseToGlobalRate(1)
            ->setBaseToOrderRate(1)
            ->setStoreToBaseRate(1)
            ->setStoreToOrderRate(1)
            ->setTaxAmount(0)
            ->setGlobalCurrencyCode($currencyCode)
            ->setBaseCurrencyCode($currencyCode)
            ->setStoreCurrencyCode($currencyCode)
            ->setOrderCurrencyCode($currencyCode);


        $this->_order->setCustomerEmail($this->_customer->getEmail())
            ->setCustomerFirstname($this->_customer->getFirstname())
            ->setCustomerLastname($this->_customer->getLastname())
            ->setCustomerGroupId($this->_customer->getGroupId())
            ->setCustomerTaxvat($this->_cpfcnpj)
            ->setCustomerIsGuest(0)
            ->setCustomer($this->_customer);

        if($this->_billing == null){
            $billing = $this->_customer->getDefaultBillingAddress();
        }else{
            $billing = $this->_billing;
        }

        $billingAddress = Mage::getModel('sales/order_address')
            ->setStoreId($this->_storeId)
            ->setAddressType(Mage_Sales_Model_Quote_Address::TYPE_BILLING)
            ->setCustomerId($this->_customer->getId())
            ->setCustomerAddressId($this->_customer->getDefaultBilling())
            ->setCustomerAddress_id($billing->getEntityId())
            ->setPrefix($billing->getPrefix())
            ->setFirstname($billing->getFirstname())
            ->setMiddlename($billing->getMiddlename())
            ->setLastname($billing->getLastname())
            ->setSuffix($billing->getSuffix())
            ->setCompany($billing->getCompany())
            ->setStreet($billing->getStreet())
            ->setCity($billing->getCity())
            ->setCountry_id($billing->getCountryId())
            ->setRegion($billing->getRegion())
            ->setRegion_id($billing->getRegionId())
            ->setPostcode($billing->getPostcode())
            ->setTelephone($billing->getTelephone())
            ->setFax($billing->getFax());
        $this->_order->setBillingAddress($billingAddress);

        if($this->_shipping == null){
            $shipping = $this->_customer->getDefaultShippingAddress();
        }else{
            $shipping = $this->_shipping;
        }
        $shippingAddress = Mage::getModel('sales/order_address')
            ->setStoreId($this->_storeId)
            ->setAddressType(Mage_Sales_Model_Quote_Address::TYPE_SHIPPING)
            ->setCustomerId($this->_customer->getId())
            ->setCustomerAddressId($this->_customer->getDefaultShipping())
            ->setCustomer_address_id($shipping->getEntityId())
            ->setPrefix($shipping->getPrefix())
            ->setFirstname($shipping->getFirstname())
            ->setMiddlename($shipping->getMiddlename())
            ->setLastname($shipping->getLastname())
            ->setSuffix($shipping->getSuffix())
            ->setCompany($shipping->getCompany())
            ->setStreet($shipping->getStreet())
            ->setCity($shipping->getCity())
            ->setCountry_id($shipping->getCountryId())
            ->setRegion($shipping->getRegion())
            ->setRegion_id($shipping->getRegionId())
            ->setPostcode($shipping->getPostcode())
            ->setTelephone($shipping->getTelephone())
            ->setFax($shipping->getFax());

        $this->_order->setShippingAddress($shippingAddress)
            ->setShippingMethod($this->_shippingMethod)
            ->setShippingDescription($this->_shippingDescription);

        $orderPayment = Mage::getModel('sales/order_payment')
            ->setStoreId($this->_storeId)
            ->setCustomerPaymentId(0)
            ->setMethod($this->_paymentMethod)
            ->setAdditionalInformation('metodo', $this->_AdditionalInformation)
            ->setPoNumber(' – ');

        $this->_order->setPayment($orderPayment);

        $qtyItems = $this->_addProducts($storeID, $products);
        $this->_order->setTotalQtyOrdered($qtyItems);

        $this->_order->setSubtotal($this->_subTotal)
            ->setBaseSubtotal($this->_subTotal)
            ->setGrandTotal( (float)$this->_subTotal+(float)$this->_shippingValue )
            ->setBaseGrandTotal($this->_subTotal);

        $transaction->addObject($this->_order);
        $transaction->addCommitCallback(array($this->_order, 'place'));
        $transaction->addCommitCallback(array($this->_order, 'save'));
        $transaction->save();

        return $reservedOrderId;
    }

    private function groupProductByID($products){
        $arryProdAt = array();
        $ctrlProds = array();
        foreach ($products as $idxProd => $prod) {
            if( isset($prod['bundle_option_qty']) ) {
                foreach ($prod['bundle_option_qty'] as $key => $value) {
                    $prod['bundle_option_qty'][$key] = $prod['bundle_option_qty'][$key] * $prod['qty'];
                }
            }

            for ($i=$idxProd+1; $i < count($products); $i++) {
                $prodToGroup = $products[$i];

                $arrToComp1 = $prod;
                $arrToComp2 = $prodToGroup;

                unset($arrToComp1['qty']);
                unset($arrToComp2['qty']);
                unset($arrToComp1['bundle_option_qty']);
                unset($arrToComp2['bundle_option_qty']);
                if (md5(serialize($arrToComp1)) == md5(serialize($arrToComp2))) {
                    $prod['qty'] += $prodToGroup['qty'];
                    if( isset($prod['bundle_option_qty']) ) {
                        foreach ($prod['bundle_option_qty'] as $key => $value) {
                            $prod['bundle_option_qty'][$key] += $prodToGroup['bundle_option_qty'][$key];
                        }
                    }

                }
            }

            if( !in_array($prod['product'], $ctrlProds) ) {
                array_push($arryProdAt, $prod);
                array_push($ctrlProds, $prod['product']);
            }
        }
        return $arryProdAt;
    }

    /**
     * add products in order
     *
     * @param $products
     *
     * @return integer
     */
    protected function _addProducts($storeID, $products)
    {
        $this->_subTotal = 0;

        //GROUP ITENS
        $arryProdAt = $this->groupProductByID($products);
        $qtyOrdered = 0;
        foreach ($arryProdAt as $productRequest) {
            $this->_addProduct($storeID, $productRequest);
            $qtyOrdered += $productRequest['qty'];
        }

        return $qtyOrdered;
    }

    /**
     * add product in order
     *
     * @param $requestData
     * @return array
     * @throws Exception
     */
    protected function _addProduct($storeID, $requestData)
    {
        $request = new Varien_Object();
        $request->setData($requestData);

        $product = Mage::getModel('catalog/product')->setStoreId($storeID)->load($request['product']);

        $cartCandidates = $product->getTypeInstance(true)
            ->prepareForCartAdvanced($request, $product);

        if (is_string($cartCandidates)) {
            throw new Exception($cartCandidates);
        }

        if (!is_array($cartCandidates)) {
            $cartCandidates = array($cartCandidates);
        }

        $parentItem = null;
        $errors = array();
        $items = array();
        foreach ($cartCandidates as $candidate) {
            $item = $this->_productToOrderItem($candidate, $candidate->getCartQty(), $request);
            $items[] = $item;

            if (!$parentItem) {
                $parentItem = $item;
            }
            if ($parentItem && $candidate->getParentProductId()) {
                $item->setParentItem($parentItem);
            }

            $item->setQty($item->getQty() + $candidate->getCartQty());

            // collect errors instead of throwing first one
            if ($item->getHasError()) {
                $message = $item->getMessage();
                if (!in_array($message, $errors)) { // filter duplicate messages
                    $errors[] = $message;
                }
            }
        }
        if (!empty($errors)) {
            Mage::throwException(implode("\n", $errors));
        }

        foreach ($items as $item){
            $this->_order->addItem($item);
        }

        return $items;
    }


    /**
     * add product in order
     *
     * @param Mage_Catalog_Model_Product $product
     * @param int $qty
     * @param $productRequest
     * @return Mage_Sales_Model_Order_Item
     */
    function _productToOrderItem(Mage_Catalog_Model_Product $product, $qty = 1, $productRequest)
    {
        $price = $productRequest['price'];
        $discount = $productRequest['discount'];
        $options = $product->getCustomOptions();

        $optionsByCode = array();
        $bundleOptSelAttr = null;
        foreach ($options as $option)
        {
            $quoteOption = Mage::getModel('sales/quote_item_option')->setData($option->getData())
                ->setProduct($option->getProduct());

            if($quoteOption->getCode() ==  'bundle_selection_attributes' ) {
                $bundleOptSelAttr = $quoteOption->getValue();
            }
            $optionsByCode[$quoteOption->getCode()] = $quoteOption;
        }

        $product->setCustomOptions($optionsByCode);

        // DECREMENTE O STOCK
        $stockItem =Mage::getModel('cataloginventory/stock_item')->loadByProduct( $product->getId() );
        if( $stockItem->getManageStock() ){
            $stockItem->setData('qty', $stockItem->getQty()-$product['cart_qty']);
        }
        $stockItem->save();

        $options = $product->getTypeInstance(true)->getOrderOptions($product);
        
        if($bundleOptSelAttr != null) {
            $options['bundle_selection_attributes'] = $bundleOptSelAttr;
        }

        $finalPrice = $price;
        if( $product['parent_product_id'] ){
            $productParent = Mage::getModel('catalog/product')->load( $product['parent_product_id'] );

            if( $productParent->getTypeID() == "bundle" && $productParent->getPriceType() == 0 ) {
                //GET PROC FROM REAL PRICE
                $priceModel = $productParent->getPriceModel();
                $PriceBundle = $priceModel->getTotalPrices($productParent, null, true, false);

                $PriceBundle = reset($PriceBundle);
                $priceProdCur = $product->getFinalPrice();

                $currPorc = (100*$priceProdCur)/$PriceBundle;
                $finalPrice =  ($currPorc*$price)/100;
            }

        }

        $qtdOrdered = $product['cart_qty'];
        if( $product->getTypeID() == "bundle" ) {
            $rowTotal = 0;
        }else{
            $rowTotal = $finalPrice * $qty;
        }

        $orderItem = Mage::getModel('sales/order_item')
            ->setStoreId($this->_storeId)
            ->setQuoteItemId(0)
            ->setQuoteParentItemId(NULL)
            ->setProductId($product->getId())
            ->setProductType($product->getTypeId())
            ->setQtyBackordered(NULL)
            ->setTotalQtyOrdered($product['rqty'])
            ->setQtyOrdered($qtdOrdered)
            ->setName($product->getName())
            ->setSku($product->getSku())
            ->setPrice($finalPrice)
            ->setBasePrice($finalPrice)
            ->setDiscountAmount($discount)
            ->setBaseDiscountAmount($discount)
            ->setOriginalPrice( $product->getFinalPrice() )
            ->setRowTotal($rowTotal)
            ->setBaseRowTotal($rowTotal)

            ->setWeeeTaxApplied(serialize(array()))
            ->setBaseWeeeTaxDisposition(0)
            ->setWeeeTaxDisposition(0)
            ->setBaseWeeeTaxRowDisposition(0)
            ->setWeeeTaxRowDisposition(0)
            ->setBaseWeeeTaxAppliedAmount(0)
            ->setBaseWeeeTaxAppliedRowAmount(0)
            ->setWeeeTaxAppliedAmount(0)
            ->setWeeeTaxAppliedRowAmount(0)

            ->setProductOptions($options);

        $this->_subTotal += ($rowTotal - $discount);

        return $orderItem;
    }
}
