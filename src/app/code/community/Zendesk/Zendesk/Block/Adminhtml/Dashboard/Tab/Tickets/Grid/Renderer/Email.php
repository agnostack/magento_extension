<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Adminhtml
 * @copyright   Copyright (c) 2014 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Adminhtml sales create order product search grid price column renderer
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Zendesk_Zendesk_Block_Adminhtml_Dashboard_Tab_Tickets_Grid_Renderer_Email extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $maped_users = array();
        $users = Mage::registry('zendesk_users');
        if( $users )
        {
            foreach( $users as $user )
            {
                $maped_users[$user['id']] = $user['email'];
            }

            if( !isset($maped_users[$row->getRequesterId()]) )
            {
                $model = Mage::getModel('zendesk/api_users')->get($row->getRequesterId());
                $users[] = $model;
                Mage::unregister('zendesk_users');
                Mage::register('zendesk_users', $users);
                return $model['email'];
            }
        }
        
        return $maped_users[$row->getRequesterId()];
    }

}