<?php

class DB1_AnyMarket_Helper_Variation extends DB1_AnyMarket_Helper_Data
{
    /**
     * get all variations from magento
     *
     * @access public
     */
    public function getVariations($storeID){
        $HOST  = Mage::getStoreConfig('anymarket_section/anymarket_acesso_group/anymarket_host_field', $storeID);
        $TOKEN = Mage::getStoreConfig('anymarket_section/anymarket_acesso_group/anymarket_token_field', $storeID);

        $headers = array(
            "Content-type: application/json",
            "gumgaToken: ".$TOKEN
        );

        $variationsArr = array();
        $startRec = 0;
        $countRec = 1;
        while ($startRec <= $countRec) {
            $variationGetRet = $this->CallAPICurl("GET", $HOST . "/v2/variations/?offset=" . $startRec . "&limit=30", $headers, null);

            if ($variationGetRet['error'] == '0' ) {
                $variationJSON = $variationGetRet['return'];
                if( isset($variationJSON->page->totalElements) ){
                    $startRec = $startRec + $variationJSON->page->size;
                    $countRec = $variationJSON->page->totalElements;

                    foreach ($variationJSON->content as $variation) {
                        array_push($variationsArr, array("id" => $variation->id, "name" => $variation->name));
                    }
                }else{
                    $startRec = 1;
                    $countRec = 0;                    
                }
            } else {
                $startRec = 1;
                $countRec = 0;

                $anymarketlog = Mage::getModel('db1_anymarket/anymarketlog');
                $anymarketlog->setLogDesc('Error on get variations (' . $variationGetRet['return'] . ')');
                $anymarketlog->setStatus("1");
                $anymarketlog->setStores(array($storeID));
                $anymarketlog->save();
            }
        }
        return $variationsArr;
    }


}