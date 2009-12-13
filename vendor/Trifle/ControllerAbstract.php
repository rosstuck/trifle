<?php

/**
 * Base controller for mixing in delegates with a controller.
 * @package Trifle
 * @author Ross Tuck
 */
class Trifle_ControllerAbstract extends Zend_Controller_Action {

    /**
     * Controller's delegation manager
     * @var Trifle_Manager
     */
    protected $_delegateManager;
    
    /**
     * List of delegates to get registered with the delegate manager
     * @var array|string
     */
    protected $_delegates = array();
    
    /**
     * Fetch the controller's delegate manager.
     * @return Trifle_Manager
     */
    protected function _getDelegateManager() {
        if($this->_delegateManager === null) {
           $this->_delegateManager = new Trifle_Manager($this, $this->_delegates);
        }
        
        return $this->_delegateManager;
    }

    /**
     * Map an unknown call to the delegate manager
     *
     * @todo A flag to prevent recursive action loops?
     * @param string $name
     * @param array $args
     * @return mixed Return value of the delegated action
     */    
    public function __call($name, $args) {
        if(substr(strtolower($name), -6) !== 'action') {
            return parent::__call($name, $args);
        } else {
            $manager = $this->_getDelegateManager();
            return $manager->run($name, $args);
        }
    }
}
