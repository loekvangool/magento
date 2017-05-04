<?php

class DB1_AnyMarket_Helper_Category extends DB1_AnyMarket_Helper_Data
{

    protected $arrJSON = array();
    protected $arrDelCat = array();
    protected $arrNewCateg = array();


    /**
     * export all category to AnyMarket
     *
     * @param $storeID
     * @return integer
     */
    public function exportCategories($storeID){
        $rootCategoryId = Mage::app()->getStore($storeID)->getRootCategoryId();
        if($rootCategoryId == 0){
            $categories = Mage::getModel('catalog/category')
                ->getCollection()
                ->setStoreId($storeID)
                ->addFieldToFilter('is_active', 1)
                ->addAttributeToSelect('*')
                ->addAttributeToSort('position', 'asc');
        }else{
            $categories = Mage::getModel('catalog/category')
                ->getCollection()
                ->setStoreId($storeID)
                ->addFieldToFilter('is_active', 1)
                ->addAttributeToFilter('path', array('like' => "1/{$rootCategoryId}/%"))
                ->addAttributeToSelect('*')
                ->addAttributeToSort('position', 'asc');
        }
        $fItem = true;
        $cCateg = 0;
        $ctrlCateg = array();
        foreach ($categories as $category) {
            $intAM = $category->getData('categ_integra_anymarket');

            if($fItem && $intAM == 0){
                continue;
            }
            $fItem = false;

            if($intAM == 1){
                $checkCategStr = $category->getPath()."_".$category->getParentId()."_".$category->getName();

                if ( !in_array($checkCategStr, $ctrlCateg) ) {
                    $amCategParent = Mage::getModel('db1_anymarket/anymarketcategories')->load($category->getParentId(), 'nmc_id_magento');
                    if ($amCategParent->getData('nmc_cat_id')) {
                        $retuCateg = $this->exportSpecificCategory($category, $amCategParent->getData('nmc_cat_id'), $storeID);
                    } else {
                        $retuCateg = $this->exportSpecificCategory($category, null, $storeID);
                    }
                    if ($retuCateg) {
                        $cCateg++;

                        array_push($ctrlCateg, $checkCategStr);
                    }
                }
            }

        }
        return $cCateg;
    }

    /**
     * get Specific Category by name
     *
     * @param $arrOfCategs
     * @param integer
     */
    function getCategoryByPathName( $arrOfCategs ){
        $categoryMain = Mage::getResourceModel('catalog/category_collection')
            ->addFieldToFilter('name', reset($arrOfCategs))
            ->getFirstItem();

        for ($i=1; $i < count($arrOfCategs); $i++) {
            $categoryMain = $categoryMain->getChildrenCategories()
                ->addFieldToFilter('name', $arrOfCategs[$i])
                ->getFirstItem();
        }

        return $categoryMain->getId();
    }

    /**
     * Delete Specific Category Recursively
     *
     * @param $category
     * @param $storeID
     */
    public function deleteCategs($category, $storeID){
        $this->arrDelCat = array();
        array_push($this->arrDelCat, $category->getId());
        $this->deleteCategRecursively($category, $storeID);

        foreach ( array_reverse($this->arrDelCat) as $categ ) {
            $_category = Mage::getModel('catalog/category')->load($categ);
            Mage::helper('db1_anymarket/category')->deleteSpecificCategory($_category, $storeID);
        }
        
    }

    /**
     * Delete Specific Category Recursively
     *
     * @param $category
     * @param $storeID
     */
    public function deleteCategRecursively($category, $storeID){
        $subcats = $category->getChildren();
        if($subcats != ''){
            foreach(explode(',',$subcats) as $subCatid){
                $_category = Mage::getModel('catalog/category')->load($subCatid);

                array_push($this->arrDelCat, $subCatid);
                if($_category->getChildren() != ''){
                    $this->deleteCategRecursively($_category, $storeID);
                }
            }
        }
    }

    /**
     * Delete Specific Category
     *
     * @param $category
     * @param $storeID
     */
    public function deleteSpecificCategory($category, $storeID){
        $anymarketcategories = Mage::getModel('db1_anymarket/anymarketcategories')->load($category->getId(), 'nmc_id_magento');
        if( $anymarketcategories->getData('nmc_cat_id') != '' ){
            $HOST  = Mage::getStoreConfig('anymarket_section/anymarket_acesso_group/anymarket_host_field', $storeID);
            $TOKEN = Mage::getStoreConfig('anymarket_section/anymarket_acesso_group/anymarket_token_field', $storeID);

            $headers = array( 
                "Content-type: application/json",
                "Accept: */*",
                "gumgaToken: ".$TOKEN
            );

            $returnDelCat = $this->CallAPICurl("DELETE", $HOST."/v2/categories/".$anymarketcategories->getData('nmc_cat_id'), $headers, null);

            $anymarketlog = Mage::getModel('db1_anymarket/anymarketlog');
            if($returnDelCat['error'] == '1'){
                $anymarketlog->setLogDesc( 'Error on delete category ('.$category->getName().') - '.is_string($returnDelCat['return']) ? $returnDelCat['return'] : json_encode($returnDelCat['return']) );
            }else{
                $anymarketlog->setLogDesc( 'Deleted category ('.$category->getName().')' );
                $anymarketcategories->delete();
            }
            $anymarketlog->setStatus("1");
            $anymarketlog->setStores(array($storeID));
            $anymarketlog->save();
        }
    }

    /**
     *  Export Specific Category Recursively
     *
     * @param $category
     * @param $storeID
     */
    public function exportCategRecursively($category, $storeID){
        $subcats = $category->getChildren();
        foreach(explode(',',$subcats) as $subCatid){
            $_category = Mage::getModel('catalog/category')->load($subCatid);
            if($_category->getData('categ_integra_anymarket') == 1){
                $this->exportSpecificCategory($_category, $category->getId(), $storeID);

                if($_category->getChildren() != ''){
                    $this->exportCategRecursively($_category, $storeID);
                }
            }
        }
    }

    /**
     * Export Specific Category
     *
     * @param $category
     * @param $IdParent
     * @param $storeID
     * @return array|null
     */
    public function exportSpecificCategory($category, $IdParent, $storeID){
        $HOST  = Mage::getStoreConfig('anymarket_section/anymarket_acesso_group/anymarket_host_field', $storeID);
        $TOKEN = Mage::getStoreConfig('anymarket_section/anymarket_acesso_group/anymarket_token_field', $storeID);

        $headers = array( 
            "Content-type: application/json",
            "Accept: */*",
            "gumgaToken: ".$TOKEN
        );

        $id = $category->getId();
        $name = $category->getName();

        $JSON = array(
            "name" => $name,
            "partnerId" => $id,
            "parent" => array("id" => $IdParent),
            "priceFactor" => 1,
            "calculatedPrice" => true
        );

        $anymarketcategories = Mage::getModel('db1_anymarket/anymarketcategories')->load($category->getId(), 'nmc_id_magento');
        if( $anymarketcategories->getData('nmc_cat_id') == '' ){

            $parentID = $category->getParentId();
            if($parentID){
                $amCatPar = Mage::getModel('db1_anymarket/anymarketcategories')->load($parentID, 'nmc_id_magento');
                $IdParent = $amCatPar->getData('nmc_cat_id');
                $JSON["parent"] = array("id" => $IdParent);
            }

            $returnCat = $this->CallAPICurl("POST", $HOST."/v2/categories/", $headers, $JSON);

            if($returnCat['error'] == '1'){
                $anymarketlog = Mage::getModel('db1_anymarket/anymarketlog');
                $anymarketlog->setLogDesc( 'Error on export category ('.$name.') - '.is_string($returnCat['return']) ? $returnCat['return'] : json_encode($returnCat['return']) );
                $anymarketlog->setLogJson($returnCat['json']);
                $anymarketlog->setStatus("1");
                $anymarketlog->setStores(array($storeID));
                $anymarketlog->save();

                return null;
            }else{
                $JSONReturn = $returnCat['return'];

                $anymarketlog = Mage::getModel('db1_anymarket/anymarketlog');
                $anymarketlog->setLogDesc( 'Category successfully exported ('.$name.')' );
                $anymarketlog->setLogJson($returnCat['json']);
                $anymarketlog->setStatus("1");
                $anymarketlog->setStores(array($storeID));
                $anymarketlog->save();

                $anymarketcategories = Mage::getModel('db1_anymarket/anymarketcategories');
                $anymarketcategories->setNmcCatId( $JSONReturn->id );
                $anymarketcategories->setNmcCatRootId( $IdParent != null ? $IdParent : '000' );
                $anymarketcategories->setNmcCatDesc( $name );
                $anymarketcategories->setNmcIdMagento( $id );
                $anymarketcategories->setStores(array($storeID));
                $anymarketcategories->setStatus('1');
                $anymarketcategories->save();

                if($category->getChildren() != ''){
                    $this->exportCategRecursively($category, $storeID);
                }

                return array($JSONReturn->id);
            }
        }else{

            $returnCatPUT = $this->CallAPICurl("PUT", $HOST."/v2/categories/".$anymarketcategories->getNmcCatId(), $headers, $JSON);

            if($returnCatPUT['error'] == '1'){
                $anymarketlog = Mage::getModel('db1_anymarket/anymarketlog');
                $anymarketlog->setLogDesc( 'Error on update category ('.$name.') - '.is_string($returnCatPUT['return']) ? $returnCatPUT['return'] : json_encode($returnCatPUT['return']) );
                $anymarketlog->setLogJson($returnCatPUT['json']);
                $anymarketlog->setStatus("1");
                $anymarketlog->setStores(array($storeID));
                $anymarketlog->save();

                return null;
            }else{
                $JSONReturn = $returnCatPUT['return'];

                $anymarketlog = Mage::getModel('db1_anymarket/anymarketlog');
                $anymarketlog->setLogDesc( 'Category successfully updated ('.$name.')' );
                $anymarketlog->setLogJson($returnCatPUT['json']);
                $anymarketlog->setStatus("1");
                $anymarketlog->setStores(array($storeID));
                $anymarketlog->save();

                $anymarketcategories->setNmcCatRootId( $IdParent );
                $anymarketcategories->setNmcCatDesc( $name );
                $anymarketcategories->save();

                return array($JSONReturn->id);
            }
        }
    }

    // ------ ANYMARKET
    /**
     * get all root category of AM
     */
    public function getCategories($storeID){
        $HOST  = Mage::getStoreConfig('anymarket_section/anymarket_acesso_group/anymarket_host_field', $storeID);
        $TOKEN = Mage::getStoreConfig('anymarket_section/anymarket_acesso_group/anymarket_token_field', $storeID);

        $headers = array( 
            "Content-type: application/json",
            "Accept: */*",
            "gumgaToken: ".$TOKEN
        );

        $resCountCateg = 0;
        $startRec = 0;
        $countRec = 1;
        $arrOrderCod = null;
        $this->arrNewCateg = array();
        while ($startRec <= $countRec) {
            $returnCat = $this->CallAPICurl("GET", $HOST."/v2/categories/?offset=".$startRec."&limit=30", $headers, null);

            if($returnCat['error'] == '1'){
                $startRec = 1;
                $countRec = 0;

                $anymarketlog = Mage::getModel('db1_anymarket/anymarketlog');
                $anymarketlog->setLogDesc( 'Error on Sincronize Category '. $returnCat['return'] );
                $anymarketlog->setStatus("1");
                $anymarketlog->save();
            }else{
                $CatJSON = $returnCat['return'];

                $startRec = $startRec + $CatJSON->page->size;
                $countRec = $CatJSON->page->totalElements;

                foreach ($CatJSON->content as $category) {
                    $IDCat = $category->id;
                    array_push($this->arrNewCateg, $IDCat);
                    $anymarketcategoriesUpdt = Mage::getModel('db1_anymarket/anymarketcategories')->load($IDCat, 'nmc_cat_id');
                    if($anymarketcategoriesUpdt->getData('nmc_cat_id') == null){
                        $anymarketcategories = Mage::getModel('db1_anymarket/anymarketcategories');
                        $anymarketcategories->setNmcCatId( $IDCat );
                        $anymarketcategories->setNmcCatRootId( '000' );
                        $anymarketcategories->setStatus('1');
                        $anymarketcategories->setNmcCatDesc( $category->name );
                        $anymarketcategories->setStores(array($storeID));
                        $anymarketcategories->save();
                    }else{
                        $anymarketcategoriesUpdt->setNmcCatDesc( $category->name );
                        $anymarketcategoriesUpdt->save();
                    }

                    $resCountCateg += $this->getChildCat($HOST, $headers, $category->id, $IDCat, $storeID);
                    $resCountCateg++;
                }

            }

        }

        return $resCountCateg;
/*
        if(!empty($this->arrNewCateg) ){
            $allCategs = Mage::getModel('db1_anymarket/anymarketcategories')->getCollection();

            foreach ($allCategs as $categ) {
                if( !in_array($categ->getData('nmc_cat_id'), $this->arrNewCateg) ){
                    $categ->delete();
                }
            }
        }
*/
    }

    /**
     * get all child category of AM
     *
     * @param $HOST
     * @param $headers
     * @param $catID
     * @param $IDCatRoot
     * @param $id_store
     *
     * @return integer
     */
    private function getChildCat($HOST, $headers, $catID, $IDCatRoot, $id_store){
        $returnCatSpecific = $this->CallAPICurl("GET", $HOST."/v2/categories/".$catID, $headers, null);
        $CatSpecifivJSON = $returnCatSpecific['return'];
        $retCategCount = 0;
        if($returnCatSpecific['error'] == '0'){
            if( isset($CatSpecifivJSON->children) ){
                foreach ($CatSpecifivJSON->children as $catChild) {

                    array_push($this->arrNewCateg, $catChild->id);
                    $anymarketcategoriesUpdt = Mage::getModel('db1_anymarket/anymarketcategories')->load($catChild->id, 'nmc_cat_id');
                    if($anymarketcategoriesUpdt->getData('nmc_cat_id') == null){
                        $anymarketcategories = Mage::getModel('db1_anymarket/anymarketcategories');
                        $anymarketcategories->setNmcCatId( $catChild->id );
                        $anymarketcategories->setNmcCatRootId( $IDCatRoot );
                        $anymarketcategories->setStatus('1');
                        $anymarketcategories->setNmcCatDesc( $catChild->name );
                        $anymarketcategories->setStores(array($id_store));
                        $anymarketcategories->save();
                    }else{
                        $anymarketcategoriesUpdt->setNmcCatDesc( $catChild->name );
                        $anymarketcategoriesUpdt->save();
                    }

                    $retCategCount += $this->getChildCat($HOST, $headers, $catChild->id, $catChild->id, $id_store);
                    $retCategCount++;
                }
            }
        }else{
            $anymarketlog = Mage::getModel('db1_anymarket/anymarketlog');
            $anymarketlog->setLogDesc( $returnCatSpecific['return'] );
            $anymarketlog->setLogId( $IDCatRoot ); 
            $anymarketlog->setStatus("1");
            $anymarketlog->save();
        }
        return $retCategCount;
    }

    /**
     * Find category in Anymarket by full path
     *
     * @param $anymarketCategories
     * @param $fullPathMagento
     * @param $currIndexFullPath
     *
     * @return boolean
     */
    private function findInAnymarketByFullPath($anymarketCategories, $fullPathMagento, $currIndexFullPath, $fullPathAnymarket){
        foreach ($anymarketCategories as $categ) {
            $categAnymarket = (string)strtolower($categ->name);
            $categMagento   = (string)strtolower($fullPathMagento[$currIndexFullPath]);

            $categAnymarket = str_replace(' ', '', $categAnymarket);
            $categMagento   = str_replace(' ', '', $categMagento);
            if( $categMagento == $categAnymarket ){
                array_push($fullPathAnymarket, $categ->name);

                $jsonCmpMagento = json_encode($fullPathMagento);
                $jsonCmpAnymarket = json_encode($fullPathAnymarket);

                $jsonCmpMagento = str_replace(' ', '', $jsonCmpMagento);
                $jsonCmpAnymarket = str_replace(' ', '', $jsonCmpAnymarket);

                if( $jsonCmpAnymarket == $jsonCmpMagento ){
                    return isset($categ->children) ? false : $categ->id;
                }else {
                    $currIndexFullPath += 1;
                    $retProc = $this->findInAnymarketByFullPath($categ->children, $fullPathMagento, $currIndexFullPath, $fullPathAnymarket);
                    return $retProc != null || $retProc == false ? $retProc : $categ->id;
                }
            }
        }
        return null;
    }

    private function verifyCategoryForSend($categoryCollection, $ignoredCategories){
        $completePathCateg = array();
        $lastLevel = 1;
        $lastParentId = null;
        $idsUsedCategory = array();
        foreach ($categoryCollection as $categ) {
            if( $categ->getData('name') != null && $categ->getData('level') > $lastLevel ){
                if( $this->in_array_r($categ->getData('entity_id'), $ignoredCategories) ){
                    continue;
                }
                if( $lastParentId == null || $categ->getData('parent_id') == $lastParentId){
                    array_push($completePathCateg, $categ->getData('name') );
                    $lastParentId = $categ->getData('entity_id');
                    array_push($idsUsedCategory, $lastParentId);
                    $lastLevel += 1;
                }
            }
        }

        return array($idsUsedCategory, $completePathCateg);
    }

    public function valideCategoryToSend($HOST, $TOKEN, $idProduct){
        $headers = array(
            "Content-type: application/json",
            "Accept: */*",
            "gumgaToken: ".$TOKEN
        );

        $anymarketCategories = $this->CallAPICurl("GET", $HOST."/v2/categories/fullPath", $headers, null);

        $product = Mage::getModel('catalog/product')->load($idProduct);
        $categoryCollection = $product->getCategoryCollection()
            ->addAttributeToSelect('name')
            ->setOrder('path', 'ASC');

        $ignoredCategories = array();
        do {
            $fullPathMagento = $this->verifyCategoryForSend($categoryCollection, $ignoredCategories);
            if (count($fullPathMagento[1]) <= 0) {
                return false;
            }
            array_push($ignoredCategories, $fullPathMagento[0]);
            $compatCateg = $this->findInAnymarketByFullPath($anymarketCategories["return"], $fullPathMagento[1], 0, array());
        }while(!$compatCateg);

        return $compatCateg;
    }


}