<?php

class Proj_Delegate_Crud extends Trifle_DelegateAbstract {
    public function indexAction() {
        $this->view->message = 'project version of index page';
    }
    //no edit action
}
