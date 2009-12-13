<?php

/**
 * Base class for a controller delegate
 * @package Trifle
 * @author Ross Tuck
 */
class Trifle_DelegateAbstract {

    /**
     * A list of paths to check for fallback view scripts.
     * @var array
     */
    protected $_viewPaths = array();

    /**
     * Setup dependencies for the object
     *
     * @param Trifle_ControllerAbstract $controller Controller to delegate for
     */
    public function __construct(Trifle_ControllerAbstract $controller = null) {
        if($controller !== null) {
            $this->setController($controller);
        }
    }

    /**
     * Placeholder for a delegate's own init() function.
     */
    public function init() {
    }

    /**
     * Set the controller this delegate is substituting for
     *
     * @param Trifle_ControllerAbstract $controller Controller to delegate for
     * @return self Returns self for method chaining
     */
    public function setController(Trifle_ControllerAbstract $controller) {
        $this->_controller = $controller;
        return $this;
    }
    
    /**
     * Get the controller we're substituting for
     *
     * @return null|Trifle_ControllerAbstract Controller we're delegating for
     */
    public function getController() {
        return $this->_controller;
    }

    /**
     * Get the actions this delegate offers
     *
     * @todo If this goes 5.3 only, turn this to a LSB static function?
     * @return array Format of: array('indexAction', 'editAction', ...)
     */
    public function getActions() {
        $actionList = array();
        $methods = get_class_methods($this);

        foreach($methods as $method) {
            $method = strtolower($method);
            if(substr($method, -6) === 'action') {
                $actionList[] = $method;
            }
        }

        return $actionList;
    }

    /**
     * Execute the given action
     *
     * @todo Add view paths to controller
     * @param string $action The name of the action to run (format: 'index')
     * @param array $args List of arguments to pass into the action
     */    
    public function run($action, array $args = null) {
        $functionName = $action.'Action';

        $this->init();
        call_user_func_array(array($this, $functionName), $args);
        $this->_detectViewScript($action);
    }

    /**
     * Add a path to check for default view scripts
     *
     * @param string $path 
     * @return self Returns self for method chaining
     */
    public function addDefaultScriptPath($path) {
        $this->_viewPaths['script'][] = realpath($path);
        return $this;
    }

    /**
     * Get the default view script paths for this delegate
     *
     * @return array List of paths
     */
    public function getDefaultScriptPaths() {
        return $this->_viewPaths['script'];
    }

    /**
     * Render the default view script (if any and is not overriden)
     *
     * @param string $action String name of action (without "Action" suffix)
     */
    protected function _detectViewScript($action) {
        $view = $this->getController()->view;
        $viewRenderer = $this->getController()->getHelper('ViewRenderer');
        
        //Check if a developer overrode the default script
        $overrideTemplate = $this->_getScriptPath($viewRenderer, $view, $action);
        if($overrideTemplate !== false) {
            return;
        }

        $this->_renderDefaultScript($viewRenderer, $view, $action);
    }

    /**
     * Get the current viewRenderer's script path (absolute)
     *
     * @param Zend_Controller_Action_Helper_ViewRenderer $viewRenderer
     * @param Zend_View_Abstract $view
     * @param string $action Name of the action, without the "Action" suffix
     * @return bool|string False if not found, otherwise the path as string 
     */
    protected function _getScriptPath($viewRenderer, $view, $action) {
        $path = $viewRenderer->getViewScript($action);
        return $view->getScriptPath($path);
    }

    /**
     * Tries to render a delegate action's default template if it exists.
     *
     * Because we can't always know the short name of a delegate (like we can
     * with a controller), the default view spec of :controller/:action:suffix
     * won't work for us. This means we can't just prepend our default paths to
     * the view script paths. Instead, this function changes the viewRenderer
     * spec to just :action:suffix, puts in the default dirs, tries to render
     * and then flips back the settings afterwards. 
     *
     * @todo Ugly, needs refactoring
     * @param Zend_Controller_Action_Helper_ViewRenderer $viewRenderer
     * @param Zend_View_Abstract $view
     * @param string $action Name of the action, without the "Action" suffix
     */
    protected function _renderDefaultScript($viewRenderer, $view, $action) {
        //No settings? Abort to save cycles
        $defaultPaths = $this->getDefaultScriptPaths();
        if(empty($defaultPaths)) {
            return;
        }
        
        //Save the old settings
        $oldSpec = $viewRenderer->getNoController();
        $oldPaths = $view->getScriptPaths();

        //Set the viewrenderer spec to look inside a flat directory
        $viewRenderer->setNoController(true);
        $view->setScriptPath($defaultPaths);
        $path = $viewRenderer->getViewScript($action);  //relative path
        $fullPath = $view->getScriptPath($path);        //absolute path

        //We use the full path to make sure this template actually exists on
        //disc but Zend_View will only accept a path relative to a script dir.
        if(!empty($fullPath) !== false) {
            $viewRenderer->renderScript($path);
        }
        
        //Reset view settings so we don't botch anything afterwards?
        $viewRenderer->setNoController($oldSpec);
        $view->setScriptPath($oldPaths);
    }

    /**
     * Map unknown calls back to the controller
     *
     * @param string $name
     * @param array $args
     * @return mixed Return value of the dispatched function
     */
    public function __call($name, $args) {
        $controller = $this->getController();
        return call_user_func_array(array($controller, $name), $args);
    }

    /**
     * Map unknown members back to the controller
     *
     * @param string $name
     * @return mixed Return value of the controller member
     */    
    public function __get($name) {
        $controller = $this->getController();
        return $controller->$name;
    }
}
