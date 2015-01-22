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
 * Adminhtml sales order create search products block
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Zendesk_Zendesk_Block_Adminhtml_Dashboard_Tab_Tickets_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    
    protected $_defaultLimit    = 20;
    protected $_defaultPage     = 1;
    protected $_defaultSort     = 'created_at';
    protected $_defaultDir      = 'desc';

    public function __construct()
    {
        parent::__construct();
        $this->setId('zendesk_tab_tickets_grid');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        if ($this->getRequest()->getParam('collapse')) {
            $this->setIsCollapsed(true);
        }
    }

    /**
     * Retrieve quote store object
     * @return Mage_Core_Model_Store
     */
    public function getStore()
    {
        return Mage::getSingleton('adminhtml/session_quote')->getStore();
    }

    /**
     * Prepare collection to be displayed in the grid
     *
     * @return Mage_Adminhtml_Block_Sales_Order_Create_Search_Grid
     */
    protected function _prepareCollection()
    {

        $filter = $this->getParam($this->getVarNameFilter(), null);
        $data = $this->helper('adminhtml')->prepareFilterString($filter);
        $data['per_page'] = (int) $this->getParam($this->getVarNameLimit(), $this->_defaultLimit);
        $data['page'] = (int) $this->getParam($this->getVarNamePage(), $this->_defaultPage);
        $data['sort_by'] = $this->getParam($this->getVarNameSort(), $this->_defaultSort);
        $data['sort_order'] = $this->getParam($this->getVarNameDir(), $this->_defaultDir);
        
        $collection = Mage::getModel('zendesk/resource_tickets_collection', $data);
        
        //Get users to render user id
        Mage::register('zendesk_users', Mage::getModel('zendesk/api_users')->all());

        if ( is_string($filter) )
        {
            $data = $this->helper('adminhtml')->prepareFilterString($filter);
            $this->_setFilterValues($data);
        }
        else if ( $filter && is_array($filter) )
        {
            $this->_setFilterValues($filter);
        }
        else if ( 0 !== sizeof($this->_defaultFilter) )
        {
            $this->_setFilterValues($this->_defaultFilter);
        }

        if ( isset($this->_columns[$data['sort_by']]) && $this->_columns[$data['sort_by']]->getIndex() )
        {
            $dir = (strtolower($data['sort_order']) == 'desc') ? 'desc' : 'asc';
            $this->_columns[$data['sort_by']]->setDir($dir);
            $this->_setCollectionOrder($this->_columns[$data['sort_by']]);
        }

        $this->setCollection($collection);
 
        return $this;
    }

    /**
     * Prepare columns
     *
     * @return Mage_Adminhtml_Block_Sales_Order_Create_Search_Grid
     */
    protected function _prepareColumns()
    {
        $this->addColumn('subject', array(
            'header'    => Mage::helper('zendesk')->__('Subject'),
            'sortable'  => false,
            'width'     => '60',
            'renderer'  => 'zendesk/adminhtml_dashboard_tab_tickets_grid_renderer_action',
            'index'     => 'subject'
        ));
        $this->addColumn('requester_id', array(
            'header'    => Mage::helper('zendesk')->__('Requester'),
            'sortable'  => false,
            'filter'    => false,
            'width'     => '60',
            'renderer'  => 'zendesk/adminhtml_dashboard_tab_tickets_grid_renderer_user',
            'index'     => 'requester_id'
        ));
        $this->addColumn('email', array(
            'header'    => Mage::helper('zendesk')->__('Email'),
            'sortable'  => false,
            'width'     => '60',
            'renderer'  => 'zendesk/adminhtml_dashboard_tab_tickets_grid_renderer_email',
            'index'     => 'email'
        ));

        $this->addColumn('status', array(
            'header'    => Mage::helper('zendesk')->__('Status'),
            'sortable'  => true,
            'width'     => '60',
            'type'      => 'options',
            'options'   => Mage::helper('zendesk')->getStatusMap(),
            'index'     => 'status'
        ));
        $this->addColumn('priority', array(
            'header'    => Mage::helper('zendesk')->__('Priority'),
            'sortable'  => true,
            'width'     => '60',
            'type'      => 'options',
            'options'   => Mage::helper('zendesk')->getPriorityMap(),
            'index'     => 'priority'
        ));
        $this->addColumn('created_at', array(
            'header'    => Mage::helper('zendesk')->__('Requested'),
            'sortable'  => true,
            'width'     => '60',
            'type'      => 'date',
            'renderer'  => 'zendesk/adminhtml_dashboard_tab_tickets_grid_renderer_created',
            'index'     => 'created_at'
        ));
        $this->addColumn('updated_at', array(
            'header'    => Mage::helper('zendesk')->__('Updated'),
            'sortable'  => true,
            'width'     => '60',
            'type'      => 'date',
            'renderer'  => 'zendesk/adminhtml_dashboard_tab_tickets_grid_renderer_updated',
            'index'     => 'updated_at'
        ));        
        
        Mage::unregister('zendesk_users');
        
        return parent::_prepareColumns();
    }

    public function getGridUrl()
    {
        $url = $this->getUrl('adminhtml/zendesk/loadBlock',array('block'=>'tickets', '_current' => true));
        return $url;
    }
    
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('id');
        
        $this->getMassactionBlock()->addItem('delete', array(
            'label' => Mage::helper('zendesk')->__('Delete'),
            'url' => $this->getUrl('*/zendesk/bulkDelete', array('form_key'=>Mage::getSingleton('core/session')->getFormKey())),
            'confirm' => Mage::helper('zendesk')->__('Are you sure you want to delete selected tickets?')
        ));
       
        $this->getMassactionBlock()->addItem('change_status', array(
            'label'     => Mage::helper('zendesk')->__('Change Status'),
            'url'       => $this->getUrl('*/zendesk/bulkChangestatus', array('form_key'=>Mage::getSingleton('core/session')->getFormKey())),
            'confirm'   => Mage::helper('zendesk')->__('Are you sure you want to change status of selected tickets?'),
            'additional' => array(
                'visibility' => array(
                    'name'      => 'status',
                    'type'      => 'select',
                    'class'     => 'required-entry',
                    'label'     => Mage::helper('zendesk')->__('Status'),
                    'values'    => Mage::helper('zendesk')->getStatusMap()
                )
            )
        ));
        return $this;
    }
    
    protected function getNoFilterMassactionColumn(){
        return true;
    }

}
