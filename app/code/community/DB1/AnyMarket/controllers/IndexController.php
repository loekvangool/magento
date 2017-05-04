<?php

// /anymarketcallback/index/sinc
class DB1_AnyMarket_IndexController extends Mage_Core_Controller_Front_Action {        
    public function sincAction() {
		$value = json_decode(file_get_contents('php://input'));

		if( $value && isset($value->type) ){
			$anymarketlog = Mage::getModel('db1_anymarket/anymarketlog');
			$anymarketlog->setLogDesc( 'Callback received');
			$anymarketlog->setLogJson(file_get_contents('php://input'));
			$anymarketlog->setStatus("0");
			$anymarketlog->save();

			$allStores = Mage::helper('db1_anymarket')->getTokenByOi( $value->content->oi );
			if( !empty($allStores) ) {
				foreach ($allStores as $store) {
					$storeID = $store['storeID'];
					$TOKEN = $store['token'];

					if ($TOKEN != '') {
						if ($value->type == 'ORDER') {
                            $sincMode = Mage::getStoreConfig('anymarket_section/anymarket_general_group/anymarket_operation_type_imp_field', $storeID);
                            if( $sincMode == "1" ) {
                                Mage::helper('db1_anymarket/queue')->addQueue($storeID, $value->content->id, 'IMP', 'ORDER');

                                echo "Adicionado na fila Magento.";
                            }else {
                                if (Mage::registry('callback_order_executed_' . $value->content->id)) {
                                    Mage::unregister('callback_order_executed_' . $value->content->id);
                                    return $this;
                                }
                                Mage::register('callback_order_executed_' . $value->content->id, true);

                                try {
                                    $cache = Mage::app()->getCache();

                                    $lastProcOrder = $cache->load('order_' . $value->content->id);
                                    $lastProc = true;
                                    if ($lastProcOrder != null) {
                                        $dateOld = new DateTime(date('y-m-d H:i:s', strtotime($lastProcOrder)));
                                        $dateNew = new DateTime(date('y-m-d H:i:s'));

                                        $difDate = $dateOld->diff($dateNew);
                                        if ($difDate->h <= 0 && $difDate->i <= 0 && $difDate->s <= 20) {
                                            $lastProc = false;
                                        }
                                    }

                                    if ($lastProc) {
                                        $cache->save(date('y-m-d H:i:s'), 'order_' . $value->content->id, array($value->content->id), 60 * 60);
                                        Mage::helper('db1_anymarket/order')->getSpecificOrderFromAnyMarket($value->content->id, "notoken", $storeID);
                                    }
                                    Mage::unregister('callback_order_executed_' . $value->content->id);

                                } catch (Exception $e) {
                                    echo "Erro ao inserir Pedido, verificar os logs do Magento";
                                    Mage::unregister('callback_order_executed_' . $value->content->id);
                                    Mage::logException($e);
                                }
                            }
						} elseif ($value->type == 'TRANSMISSION') {
                            $sincMode = Mage::getStoreConfig('anymarket_section/anymarket_general_group/anymarket_operation_type_imp_field', $storeID);
                            if( $sincMode == "1" ) {
                                $typeSincProd = Mage::getStoreConfig('anymarket_section/anymarket_integration_prod_group/anymarket_type_prod_sync_field', $storeID);
                                if($typeSincProd == "1") {
                                    Mage::helper('db1_anymarket/queue')->addQueue($storeID, $value->content->id, 'IMP', 'PRODUCT');
                                }else{
                                    $typeSincOrder = Mage::getStoreConfig('anymarket_section/anymarket_integration_order_group/anymarket_type_order_sync_field', $storeID);
                                    if( $typeSincOrder == "0" ){
                                        Mage::helper('db1_anymarket/queue')->addQueue($storeID, $value->content->id, 'IMP', 'STOCK');
                                    }
                                }
                                echo "Adicionado na fila Magento.";
                            }else{
                                $listTransmissions = array();

                                if( !Mage::helper('db1_anymarket/product')->validateCallbackReceiver($storeID, $value->content->id) ){
                                    $anymarketlog = Mage::getModel('db1_anymarket/anymarketlog');
                                    $anymarketlog->setLogDesc('Callback Rejected');
                                    $anymarketlog->setLogJson(file_get_contents('php://input'));
                                    $anymarketlog->setStatus("0");
                                    $anymarketlog->save();

                                    return $this;
                                }

                                $HOST = Mage::getStoreConfig('anymarket_section/anymarket_acesso_group/anymarket_host_field', $storeID);

                                $headers = array(
                                    "Content-type: application/json",
                                    "Accept: */*",
                                    "gumgaToken: " . $TOKEN
                                );

                                array_push($listTransmissions, array(
                                        "id" => $value->content->id,
                                        "token" => "notoken"
                                    )
                                );

                                $JSON = json_encode($listTransmissions);

                                $transRet = Mage::helper('db1_anymarket/product')->getSpecificFeedProduct($storeID, json_decode($JSON), $headers, $HOST);
                                echo $transRet;
                            }
						}
					} else {
						$anymarketlog = Mage::getModel('db1_anymarket/anymarketlog');
						$anymarketlog->setLogDesc('OI nao encontrado');
						$anymarketlog->setLogJson($value->content->oi);
						$anymarketlog->setStatus("0");
						$anymarketlog->save();

						echo "OI NOT FOUND";
					}
				}
			}else{
				echo "OI NOT FOUND";
			}

		}

    }
}

?>