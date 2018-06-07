<?php
/*$installer = new Mage_Eav_Model_Entity_Setup('core_setup');
$installer->startSetup();
$installer->addAttribute('customer', 'zendesk_id', array(
    'group'           => 'General',
    'label'           => 'Zendesk Id',
    'input'           => 'text',
    'type'            => 'text',
    'required'        => 0,
    'visible_on_front'=> 1,
    'filterable'      => 0,
    'searchable'      => 0,
    'comparable'      => 0,
    'user_defined'    => 1,
    'is_configurable' => 0,
    'global'          => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'note'            => '',
));
$installer->endSetup();*/

$installer = new Mage_Eav_Model_Entity_Setup('core_setup');
$installer->startSetup();
$installer->addAttribute("customer", "zendesk_id",  array(
    "type"     => "varchar",
    "backend"  => "",
    "label"    => "zendesk_id",
    "input"    => "text",
    "source"   => "",
    "visible"  => true,
    "required" => false,
    "default" => "",
    "frontend" => "",
    "unique"     => false,
    "note"       => ""

));

$attribute   = Mage::getSingleton("eav/config")->getAttribute("customer", "zendesk_id");
$used_in_forms=array();

$used_in_forms[]="adminhtml_customer";
$used_in_forms[]="checkout_register";
$used_in_forms[]="customer_account_create";
$used_in_forms[]="customer_account_edit";
$used_in_forms[]="adminhtml_checkout";
$attribute->setData("used_in_forms", $used_in_forms)
            ->setData("is_used_for_customer_segment", true)
            ->setData("is_system", 0)
            ->setData("is_user_defined", 1)
            ->setData("is_visible", 1)
            ->setData("sort_order", 100);
$attribute->save();
$installer->endSetup();
