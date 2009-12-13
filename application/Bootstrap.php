<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap {

    protected function _initAddPaths() {
        Zend_Loader_Autoloader::getInstance()->setFallbackAutoloader(true);
   }

    protected function _initDelegatePaths() {
        Trifle_Manager::setDefaultPaths(array(
            'Proj_Delegate'     => 'Proj/Delegate/',
            'Trifle_Delegate'   => 'Trifle/Delegate/'
        ));
    }
}

