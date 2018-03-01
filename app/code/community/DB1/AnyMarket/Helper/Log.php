<?php

class DB1_AnyMarket_Helper_Log extends DB1_AnyMarket_Helper_Data
{
    public function generateErrorLogForTransmission($storeID, $transmissionId, $description, $status)
    {
        $this->generateErrorLog($storeID, $transmissionId, $description, $status);
        $this->getSkuMarketplaceByTransmission($storeID, $transmissionId, $description);
    }

    private function getSkuMarketplaceByTransmission($storeID, $transmissionId, $message)
    {
        $HOST = Mage::getStoreConfig('anymarket_section/anymarket_acesso_group/anymarket_host_field', $storeID);
        $TOKEN = Mage::getStoreConfig('anymarket_section/anymarket_acesso_group/anymarket_token_field', $storeID);

        $headers = array(
            "Content-type: application/json",
            "Accept: */*",
            "gumgaToken: " . $TOKEN
        );

        $skuMarketplaces = $this->CallAPICurl("GET", $HOST . "/v2/skus/" . $transmissionId . "/marketplaces", $headers, null);
        if ($skuMarketplaces['error'] != '1') {
            $skmpId = $this->getIdSkuMarketplace($skuMarketplaces['return']);

            $bodySkmp = array("id" => $transmissionId, "transmissionStatus" => "ERROR", "errorMsg" => $message);
            $this->CallAPICurl("PUT", $HOST . "/v2/skus/" . $transmissionId . "/marketplaces/" . $skmpId, $headers, $bodySkmp);
        }
    }

    public function generateErrorLog($storeID, $itemId, $description, $status)
    {
        $anymarketlog = Mage::getModel('db1_anymarket/anymarketlog');
        $anymarketlog->setLogDesc($description);
        $anymarketlog->setStatus($status);
        $anymarketlog->setLogId($itemId);
        $anymarketlog->setStores(array($storeID));
        $anymarketlog->save();
    }

    private function getIdSkuMarketplace($skuMarketplaces)
    {
        foreach ($skuMarketplaces as $skmp) {
            if ($skmp->marketPlace == "ECOMMERCE") {
                return $skmp->id;
            }
        }
        return null;
    }

}