<?php

/** @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

/* @var $installer Mage_Catalog_Model_Resource_Eav_Mysql4_Setup */
$setup = new Mage_Eav_Model_Entity_Setup('core_setup');


// Add new Attribute group
$groupName = 'AnyMarket';
$entityTypeId = $setup->getEntityTypeId('catalog_product');
$attributeSetId = $setup->getDefaultAttributeSetId($entityTypeId);
$setup->addAttributeGroup($entityTypeId, $attributeSetId, $groupName, 100);

//$setup->addAttributeGroup('catalog_product', 'Default', 'AnyMarket', 1000);

// Add Integra Anymarket to prduct attribute set
$codigo = 'integra_anymarket';
$config = array(
    'group' => 'AnyMarket',
    'position' => 0,
    'required' => 1,
    'label'    => 'Integrar produto com o AnyMarket',
    'type' => 'int',
    'input'    => 'boolean',
    'visible'  => true,
    'apply_to' => 'simple,bundle,grouped,configurable',
    'note'     => '',
    'global'   => 'Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL'
);

$setup->addAttribute('catalog_product', $codigo, $config);

// Add id_anymarket to prduct attribute set
$codigo = 'id_anymarket';
$config = array(
    'group' => 'AnyMarket',
    'position' => 1,
    'required' => 0,
    'label'    => 'Código Anymarket',
    'type'     => 'int',
    'input'    => 'text',
    'apply_to' => 'simple,bundle,grouped,configurable',
    'note'     => 'Código referente ao produto no AnyMarket(Não Preencher)'
);

$setup->addAttribute('catalog_product', $codigo, $config);

// Add categoria_anymarket to prduct attribute set
$codigo = 'categoria_anymarket';
$config = array(
    'group' => 'AnyMarket',
    'position' => 2,
    'required' => 0,
    'label'    => 'Categoria Anymarket',
    'type'     => 'varchar',
    'input'    => 'text',
    'apply_to' => 'simple,bundle,grouped,configurable',
	'user_defined' => 1,
    'note'     => '<a style="cursor:pointer" onclick="showDialogCategory();">Selecione uma categoria para enviar ao Anymarket</a>'
);

$setup->addAttribute('catalog_product', $codigo, $config);

$installer->endSetup();