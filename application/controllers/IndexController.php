<?php
class IndexController extends Trifle_ControllerAbstract {
    protected $_delegates = array('Crud', 'Map');
    
    public function init() {
        $this->_helper->getHelper('contextSwitch')
            ->setAutoJsonSerialization(false)
            ->addActionContext('index', 'json')
            ->initContext();
    }
}
