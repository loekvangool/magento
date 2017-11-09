<?php
/**
 * AnyMarket default helper
 *
 * @category    DB1
 * @package     DB1_AnyMarket
 */
class DB1_AnyMarket_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * @return string
     */
    public function getCurrentStoreView(){
        $storeID = Mage::app()->getStore()->getId();
        if( $storeID == null || $storeID == 0 ){
            $storeID = Mage::app()->getDefaultStoreView()->getId();
            if( $storeID == null ){
                $storeID = 1;
            }
        }

        return $storeID;
    }

    /**
     * @param $OI
     * @return array
     */
    public function getTokenByOi($OI) {
        $allStores = $this->getAllStores();

        $arrStores = array();
        $OIConfig = Mage::getStoreConfig('anymarket_section/anymarket_acesso_group/anymarket_oi_field', 0);
        if( $OI == $OIConfig ){
            $TOKEN = Mage::getStoreConfig('anymarket_section/anymarket_acesso_group/anymarket_token_field', 0);
            array_push($arrStores, array(
                                          "token" => $TOKEN,
                                          "storeID" => '0',
            ));
        }

        foreach ($allStores as $store) {
            $storeID = $store['store_id'];
            $OIConfig = Mage::getStoreConfig('anymarket_section/anymarket_acesso_group/anymarket_oi_field', $storeID);
            if( $OI == $OIConfig ){
                $TOKEN = Mage::getStoreConfig('anymarket_section/anymarket_acesso_group/anymarket_token_field', $storeID);
                array_push($arrStores, array(
                                              "token" => $TOKEN,
                                              "storeID" => $storeID,
                ));
            }
        }

        return $arrStores;
    }

    /**
     * return text with mask
     */
    public function Mask($mask,$str){
        $str = str_replace(" ","",$str);

        for($i=0;$i<strlen($str);$i++){
            $mask[strpos($mask,"#")] = $str[$i];
        }

        return $mask;
    }

    /**
     * check if module is enabled
     */
    public function anymarketModuleIsEnabled()
    {
        $outputPath = "advanced/modules_disable_output/DB1_AnyMarket";

        $enableConfig = new Mage_Core_Model_Config();
        $enableConfig->saveConfig($outputPath, "1");
        unset($enableConfig);
    }

    /**
     * get Document Type
     *
     * @param $document
     * @return string
     */
    public function getDocumentType($document)
    {
        $document = str_replace("/","", str_replace("-","",str_replace(".","",$document)));
        $docCount = strlen($document);

        $tpDoc = "CPF";
        if( $docCount == 14 ){
            $tpDoc = "CNPJ";
        }

        return $tpDoc;
    }

    /**
     * convert array to options
     *
     * @param $options
     * @return array
     */
    public function convertOptions($options)
    {
        $converted = array();
        foreach ($options as $option) {
            if (isset($option['value']) && !is_array($option['value']) &&
                isset($option['label']) && !is_array($option['label'])) {
                $converted[$option['value']] = $option['label'];
            }
        }
        return $converted;
    }

    /**
     * get substring between two caracter
     *
     * @param $content
     * @param $start
     * @param $end
     * @return string
     */
    public function getBetweenCaract($content, $start, $end)
    {
        $r = explode($start, $content);
        if (isset($r[1])){
            $r = explode($end, $r[1]);
            return $r[0];
        }
        return '';
    }

    /**
     * get all store data
     *
     * @param null $websiteID
     * @return array
     */
    public function getAllStores($websiteID = null)
    {
        $arrStores = array();
        if(!$websiteID){
            foreach (Mage::app()->getWebsites() as $website) {
                foreach ($website->getGroups() as $group) {
                    $stores = $group->getStores();
                    foreach ($stores as $store) {
                        array_push($arrStores, $store->getData());
                    }
                }
            }
        }else{
            $website = Mage::getModel('core/website')->load($websiteID);

            foreach ($website->getStoreIds() as $storeid) {
                $storeDat = Mage::getModel('core/store')->load($storeid);
                array_push($arrStores, $storeDat->getData());
            }
        }
        array_push($arrStores, array("store_id" => 0) );
        return $arrStores;
    }

    /**
     * format date time
     *
     * @param $date
     * @return string
     */
    public function formatDateTimeZone($date){
        if($date) {
            $timeZone = new DateTimeZone(Mage::getStoreConfig('general/locale/timezone'));
            $DateTime = new DateTime($date, $timeZone);
            return date_format($DateTime, 'Y-m-d\TH:i:sP');
        }else{
            return gmdate('Y-m-d\TH:i:s\Z');
        }
    }

    private function normalize_str($str){
        $unwanted_array = array('Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
            'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
            'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
            'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
            'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y' );
        return strtr( $str, $unwanted_array );
    }

    /**
     * call curl
     *
     * @param $method
     * @param $url
     * @param $headers
     * @param $params
     * @return array|string
     */
    public function CallAPICurl($method, $url, $headers, $params){
        $orderGenerator = Mage::helper('db1_anymarket/rate');
        $orderGenerator->applyRateLimit();

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $data_string = "";
        if ($method == "POST"){
            $data_string = json_encode($params);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
        }else if($method == "PUT"){
            $data_string = json_encode($params);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
        }else if($method == "DELETE"){
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
        }

        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT ,0); 
        curl_setopt($curl, CURLOPT_TIMEOUT, 400);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        $curl_response = curl_exec($curl);
        $err = curl_error($curl);

        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ( $status == 200 || $status == 204 || $status == 201 ) {
    			$retorno = array("error" => "0", "json" => $data_string, "return" => json_decode($curl_response));
    	}elseif ( $status == 404 || $status == 503 ) {
    			$retorno = array("error" => "1", "json" => $data_string, "return" => $this->normalize_str($curl_response));
        }else{
            if($err){
                $retorno = array("error" => "1", "json" => $data_string, "return" => 'Error Curl: '.$err );
            }else{
                $retJsonCurlResp = json_decode($curl_response);

                $retString = '';
                if( isset($retJsonCurlResp->message) ){
                    $retString = 'Message: '.$retJsonCurlResp->message;
                }

                if( isset($retJsonCurlResp->details) ){
                    $retString .= '; Details: '.$retJsonCurlResp->details;
                }

                if( isset($retJsonCurlResp->fieldErrors) ){
                    $retString .= '; Field Erros: (';
                    foreach ($retJsonCurlResp->fieldErrors as $error) {
                        $retString .= 'Field: '.utf8_encode($error->field);
                        $retString .= ', Message: '.$error->message.';';
                    }
                    $retString .= ')';
                }

                if($retString != ''){
                    $retorno = array("error" => "1", "json" => $data_string, "return" => $this->normalize_str($retString) );
                }else{
                    $retorno = array("error" => "1", "json" => $data_string, "return" => $this->normalize_str($curl_response) );
                }
            }

        }
        if($retorno == ""){
            $retorno = $data_string;
        }

        curl_close($curl);
        return $retorno;
    }

    /**
     * unique array multidim
     *
     * @param $array
     * @param $key
     * @return array
     */
    public function unique_multidim_array($array, $key) {
        $temp_array = array();
        $i = 0;
        $key_array = array();
        $var_array = array();
        $var_array_hash = array();
        foreach($array as $val) {
            if( isset($val['variation']) ){
                if ((!in_array($val[$key], $key_array) || !in_array($val['variation'], $var_array)) &&
                    !in_array($val['hash'].$val['variation'], $var_array_hash)
                ) {
                    $key_array[$i] = $val[$key];
                    $var_array[$i] = $val['variation'];
                    array_push($temp_array, $val);
                    array_push($var_array_hash, $val['hash'].$val['variation']);
                }
            }else {
                if (!in_array($val[$key], $key_array)) {
                    $key_array[$i] = $val[$key];
                    array_push($temp_array, $val);
                }
            }
            $i++;
        }

        return $temp_array;
    }

    /**
     * add message inbox of magento
     *
     * @param $title
     * @param $Desc
     * @param $URL
     */
    public function addMessageInBox($storeID ,$title, $Desc, $URL){

        $addMsgInbox  = Mage::getStoreConfig('anymarket_section/anymarket_logs_group/anymarket_inbox_field', $storeID);
        if( $addMsgInbox == '1' ) {
            if (Mage::helper('core')->isModuleEnabled('Mage_AdminNotification')) {
                $AdminNotice = Mage::getModel('adminnotification/inbox');
                $AdminNotice->setSeverity('2');
                $AdminNotice->setTitle($title);
                $AdminNotice->setDescription($Desc);
                $AdminNotice->setUrl($URL);
                $AdminNotice->setDateAdded(date('Y-m-d H:i:s'));
                $AdminNotice->save();
            }
        }
        Mage::getSingleton('adminhtml/session')->addError($Desc);
    }


    /**
     *
     */
    public function massInsertAttribute(){
        $productAttrs = Mage::getResourceModel('catalog/product_attribute_collection');
        $storeID = $this->getCurrentStoreView();

        foreach ($productAttrs as $productAttr) {
            if($productAttr->getFrontendLabel() != null){
                $attrCheck =  Mage::getModel('db1_anymarket/anymarketattributes')->load($productAttr->getAttributeId(), 'nma_id_attr');

                if($attrCheck->getData('nma_id_attr') == null){
                    $anymarketattribute = Mage::getModel('db1_anymarket/anymarketattributes');
                    $anymarketattribute->setNmaIdAttr( $productAttr->getAttributeId() );
                    $anymarketattribute->setNmaDesc( $productAttr->getFrontendLabel() );
                    $anymarketattribute->setStatus( "0" );
                    $anymarketattribute->setStores(array($storeID));
                    $anymarketattribute->save();
                }
            }
        }
    }

    /**
     *
     */
    public function in_array_r($needle, $haystack, $strict = false) {
        foreach ($haystack as $item) {
            if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && $this->in_array_r($needle, $item, $strict))) {
                return true;
            }
        }

        return false;
    }

}