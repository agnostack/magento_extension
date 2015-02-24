<?php

class Zendesk_Zendesk_Block_Adminhtml_Widget_Form_Renderer_Fieldset extends Mage_Adminhtml_Block_Widget_Form_Renderer_Fieldset {
    protected $_message = '';
    
    protected function _construct() {
        parent::_construct();
        $this->setTemplate('zendesk/widget/form/renderer/fieldset.phtml');
    }
    
    public function setMessage($message) {
        $this->_message = $message;
    }
    
    public function getMessage() {
        return $this->_message;
    }
    
    public function hasMessage() {
        $message = $this->getMessage();
        return ! empty($message);
    }
}
