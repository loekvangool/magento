<?php
require_once('app/Mage.php');
Mage::app();

$installer = new Mage_Eav_Model_Entity_Setup('core_setup');
$installer->startSetup();

$entityTypeId = $installer->getEntityTypeId('catalog_product');
$idAttributeOldSelect = $installer->getAttribute($entityTypeId, 'categoria_anymarket', 'attribute_id');
$installer->updateAttribute($entityTypeId, $idAttributeOldSelect, 'frontend_input','select');
$installer->updateAttribute($entityTypeId, $idAttributeOldSelect, 'backend_type','varchar');
$installer->updateAttribute($entityTypeId, $idAttributeOldSelect, 'backend_model','eav/entity_attribute_backend_array');
$installer->updateAttribute($entityTypeId, $idAttributeOldSelect, 'source_model','db1_anymarket/system_config_source_categories_values');
$installer->updateAttribute($entityTypeId, $idAttributeOldSelect, 'note','Selecione uma categoria para enviar ao Anymarket');
$installer->endSetup();