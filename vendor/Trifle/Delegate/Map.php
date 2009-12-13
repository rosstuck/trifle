<?php

class Trifle_Delegate_Map extends Trifle_DelegateAbstract {

    public function init() {
        $this->addDefaultScriptPath(dirname(__FILE__).'/../views/scripts/map');
    }

    public function mapAction() {
        $this->view->message = 'index page';
    }
}
