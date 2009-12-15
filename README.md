# What is it?
Trifle is a set of classes for making reusable actions in Zend Framework. It tries to let you compose boilerplate code in more flexible ways. This partly involves shamelessly reuses ideas from other projects like [Action Delegates](http://iam.mrburly.com/content/display/show/slug/action-delegates) and [Inherited_Resources](http://github.com/josevalim/inherited_resources).

## How do I use it?
Copy the contents of vendor/Trifle to your own vendor directory.

In your bootstrap, setup the plugin loader:

    protected function _initDelegatePaths() {
        Trifle_Manager::setDefaultPaths(array(
            'Project_Delegate'     => 'Project/Delegate/',
            'Company_Delegate'     => 'Company/Delegate/'
        ));
    }

In your controllers, add your delegates:

    class IndexController extends Trifle_ControllerAbstract {
        protected $_delegates = array('Crud', 'GoogleMaps'); 
    }
    
Or if it's just one:

    protected $_delegates = 'Crud'; 

## Writing a delegate
Delegates look like controllers:

    class Project_Delegate_Crud extends Trifle_DelegateAbstract {
        public function indexAction() {
            $this->view->list = 'Collection goes here...';
        }
        
        public function editAction() {
            $this->view->form = $this->getForm();
        }
    }

__call() and __get() proxy to the controller, so you can naturally pull resources from it (but not __set()!). You can also call $this->getController() to reduce magic overhead.

## View scripts
Delegates can ship with default view scripts.

    class Project_Delegate_Geolocation extends Trifle_DelegateAbstract {
        public function init() {
            $this->addDefaultScriptPath('absolute path to scripts folder');
        }
        //actions...
    }

The view handling is still simple: Place the templates inside a directory and name each after an action.

If you'd like to override the default view script, add a script for the action according to your viewrenderer settings. It'll be used instead of the default directories.

See the sample project included for more examples.

## Handling multiple delegates
If you're mixing in two delegates that both respond to the same action, Trifle will throw an exception. However, you can work around this:

    class IndexController extends Trifle_ControllerAbstract {
        protected $_delegates = array(
            'GoogleMaps' => array(
                'except' => 'index'
            ),
            'Crud' => array(
                'only'   => array('index', 'edit'),
            )
        ); 
    }

So far, trifle accepts the "only" and "except" parameters. 

## Disclaimer
This version is still rough and misses some features (and points). It's probably okay but I can't promise anything.

## TODO / Future features
- Fetch individual delegates back out by name
- Config arrays for each delegate
- Composable resources (mix in models, forms, etc)
- Refactor the view handling
- ?Port manager to action helper
- ?"As" syntax for renaming an action 
- ?Deprecate adding instances directly (i.e. require the plugin loader). This means I could count on every delegate having a short string name.
- ?Caching some controller access / holding view in delegate / profiling
