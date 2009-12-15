<?php
/**
 * Controls the mapping and execution of multiple delegates for one controller
 * @package Trifle
 * @author Ross Tuck
 */
class Trifle_Manager {

    /**
     * Key we store delegate loading paths under in the registry
     */
    const REGISTRY_KEY = 'Trifle_Delegate_Paths';

    /**
     * List of delegate objects
     * @var array
     */
    protected $_delegates = array(); 
    
    /**
     * Controller this manager uses
     * @var Trifle_ControllerAbstract
     */
    protected $_controller;
    
    /**
     * Actions by responding delegate. Format: Array('index' => Delegate, ...)
     * @var array
     */
    protected $_actionMapping = array();
    
    /**
     * The delegate loader
     * @var Zend_Loader_PluginLoader
     */
    protected $_delegateLoader;

    /**
     * Setup required and optional dependencies
     *
     * @param Trifle_ControllerAbstract $controller Controller to delegate for
     * @param array|string $delegates Delegate name(s) string or object(s)
     * @param Zend_Loader_PluginLoader $loader Loader for delegate objects
     */ 
    public function __construct(Trifle_ControllerAbstract $controller, $delegates = array(), Zend_Loader_PluginLoader $loader = null) {
        if($loader === null) {
            $loader = $this->_defaultDelegateLoader();
        }

        $this->setDelegateLoader($loader)
             ->setController($controller)
             ->setDelegates($delegates);
    }

    /**
     * Set the controller to delegate for
     *
     * @param Trifle_ControllerAbstract $controller
     * @return self Returns self for method chaining
     */    
    public function setController(Trifle_ControllerAbstract $controller) {
        $this->_controller = $controller;
        return $this;
    }
    
    /**
     * Get the controller currently being delegated for
     *
     * @return Trifle_ControllerAbstract
     */
    public function getController() {
        return $this->_controller;
    }
    
    /**
     * Set the delegates to use for this object.
     *
     * @param string|array $delegates
     * @return self Returns self for method chaining
     */
    public function setDelegates($delegates) {
        $delegates = (array)$delegates;
    
        foreach($delegates as $key => $value) {
            $delegateId = $value;
            $spec = array();
            if(!is_numeric($key)) {
                $delegateId = $key;
                $spec = $value;
            }
            
            $this->addDelegate($delegateId, $spec);
        }

        return $this;
    }

    /**
     * Add a delegate for use, optionally with action config
     *
     * So far, $spec recognizes the following options:
     * -only:   Whitelist of actions to enable (no suffix).
     * -except: Blacklist of actions of not enable (no suffix).
     *
     * @param string|Trifle_DelegateAbstract $delegateId Delegate or string name
     * @param array $spec Optional config for action(s) configuration
     * @return self Returns self for method chaining
     */        
    public function addDelegate($delegateId, array $spec = array()) {
        $delegate = $this->_loadDelegate($delegateId);

        $actions = $this->_parseActionSpec($delegate, $spec);
        $this->_registerActions($delegate, $actions);
        $this->_delegates[] = $delegate;
        
        return $this;        
    }

    /**
     * Find the actions usable from this delegate, base on it's spec
     *
     * @param Trifle_DelegateAbstract $delegate
     * @param array $spec Config array to parse
     * @return array List of actions allowable
     */
    protected function _parseActionSpec($delegate, array $spec) {
        //All actions
        $actions = $delegate->getActions();
        $actions = array_map(array($this, '_cleanActionName'), $actions);

        //Whitelist
        if(!empty($spec['only'])) {
            $actions = array_intersect($actions, (array)$spec['only']);
        }

        //Blacklist
        if(!empty($spec['except'])) {
            $actions = array_diff($actions, (array)$spec['except']);
        }

        return $actions;
    }

    /**
     * List of all delegate objects in use
     *
     * @return array Format of Array(DelegateObj, DelegateObj, ...)
     */
    public function getDelegates() {
        return $this->_delegates;
    }

    /**
     * Set the delegate loader
     *
     * @param Zend_Loader_PluginLoader $loader
     * @return self Returns self for method chaining
     */
    public function setDelegateLoader(Zend_Loader_PluginLoader $loader) {
        $this->_delegateLoader = $loader;
        return $this;
    }

    /**
     * Get the delegate loader
     *
     * @return Zend_Loader_PluginLoader
     */
    public function getDelegateLoader() {
        return $this->_delegateLoader;
    }

    /**
     * Return a loader suitable for default use 
     *
     * @todo Pull paths or loader from registry for those not using di?
     * @return Zend_Loader_PluginLoader
     */
    protected function _defaultDelegateLoader() {
        $paths = $this->_getDefaultPaths();
        $loader = new Zend_Loader_PluginLoader($paths, 'Trifle_Delegates');

        return $loader;
    }

    /**
     * Set the default paths used for loading delegates
     *
     * @param array $paths List of paths. Format of A('prefix' => 'path', ...)
     * @see Zend_Loader_PluginLoader
     */
    public static function setDefaultPaths(array $paths) {
        Zend_Registry::set(self::REGISTRY_KEY, $paths);
    }

    /**
     * Return a list of the end-developer supplied loading paths
     *
     * @return array Array of paths or empty array if none given
     */    
    protected function _getDefaultPaths() {
        $key = self::REGISTRY_KEY;
        if(!Zend_Registry::isRegistered($key)) {
            return array();
        }
        
        return Zend_Registry::get($key);
    }
    
    /**
     * Load a delegate from a given identifier.
     *
     * @param Trifle_DelegateAbstract|string $id String for loader or instance
     * @return Trifle_DelegateAbstract
     */
    protected function _loadDelegate($id) {
        $delegate = null;

        if($id instanceof Trifle_DelegateAbstract) {
            $delegate = $id;
        } elseif(is_string($id)) {
            $className = $this->getDelegateLoader()->load($id);
            $delegate = new $className;
        } else {
            throw new InvalidArgumentException('Delegate name "'.(string)$id.'" is not understood');
        }

        $delegate->setController($this->getController());
        return $delegate;
    }

    /**
     * Register the actions of a delegate
     *
     * @param Trifle_DelegateAbstract $delegate
     * @return self Returns self for method chaining
     */
    protected function _registerActions($delegate, array $newActions) {
        foreach($newActions as $action) {
            if(isset($this->_actionMapping[$action])) {
                throw new Exception("Action '$action' is already registered.");
            }

            $this->_actionMapping[$action] = $delegate;
        }

        return $this;
    }
    
    /**
     * Return the delegate mapped to an action
     *
     * @param string $action Name of action. Format of 'index', 'new', etc.
     * @param Trifle_DelegateAbstract
     */
    public function getDelegateByAction($action) {
        if(!isset($this->_actionMapping[$action])) {
            throw new Exception("Action '$action' was not found.");
        }
    
        return $this->_actionMapping[$action];
    }
    
    /** 
     * Execute an action from amongst the delegates
     *
     * @param string $action Name of action. Format of 'indexAction' or 'index'
     * @param array|null $args Arguments to be injected to the action function
     * @return mixed Return value of the action function
     */
    public function run($action, array $args = null) {
        $action = $this->_cleanActionName($action);
        
        $delegate = $this->getDelegateByAction($action);
        return $delegate->run($action, $args);
    }

    /**
     * Normalize an action's name. (lowercase, strip Action suffix, etc)
     *
     * @param string $action
     * @return string 
     */
    protected function _cleanActionName($action) {
        $action = strtolower($action);
        if(substr($action, -6) === 'action') {
            $action = substr($action, 0, -6);
        }

        return $action;
    }
}
