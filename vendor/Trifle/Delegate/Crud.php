<?php

class Trifle_Delegate_Crud extends Trifle_DelegateAbstract {
    public function indexAction() {
        $this->view->message = 'index page';
    }
    
    public function editAction() {
        $this->view->message = 'edit Form goes here';
    }
}
