<?php

/* * ********************************************************************
 * Customization Services by ModulesGarden.com
 * Copyright (c) ModulesGarden, INBS Group Brand, All Rights Reserved 
 * 
 *
 *  CREATED BY MODULESGARDEN       ->        http://modulesgarden.com
 *  CONTACT                        ->       contact@modulesgarden.com
 *
 *
 *
 *
 * This software is furnished under a license and may be used and copied
 * only  in  accordance  with  the  terms  of such  license and with the
 * inclusion of the above copyright notice.  This software  or any other
 * copies thereof may not be provided or otherwise made available to any
 * other person.  No title to and  ownership of the  software is  hereby
 * transferred.
 *
 *
 * ******************************************************************** */

/**
 * @author Marcin Kozak <marcin.ko@modulesgarden.com>
 */
$installer = $this;
$installer->startSetup();
$installer->run("CREATE TABLE IF NOT EXISTS `{$installer->getTable('zendesk_settings')}` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `admin_user_id` int(10) unsigned NOT NULL COMMENT 'Admin ID',
  `use_global_settings` tinyint(1) DEFAULT '0' COMMENT 'User use setting from global configuration',
  `username` varchar(255) DEFAULT NULL COMMENT 'Username',
  `password` varchar(255) DEFAULT NULL COMMENT 'Password',
  `signature` text COMMENT 'Ticket Signature',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='zendesk_settings' AUTO_INCREMENT=2 ;
");
$installer->endSetup();