<?php

const XCORE_VERSION = '0.2.6-dev2';




/**
 * XCore - Simple Standalone Micro App Engine
 * @author wolo.pl '.' studio / 2021-2025
 */
abstract class XCore implements XCoreSingleton {

    /**
     * Optional local app config path
     */
    const CONFIG_FILE = 'config/app_config.php';



    /**
     * Initial XCore settings - override in child class
     * @var array 
     */
    protected $defaultSettingsXCore = [

        'baseHref' => '',

        'DEV' => false,
        'dontExecCommands' => 0,    // only build and collect clis
        
        'log_app_path' => 'app.log',

        'dbAuth' => [
            'user' => '',
            'password' => '',
            'host' => '',
            'dbname' => ''
        ],

        // pages available to render
        'pages' => [
            // 'PAGE_ID' => ['title' => 'PAGE TITLE'] 
            'home' =>     ['title' => 'Home'],    
            //'example' =>  ['title' => 'Example subpage'],    
        ],

        // menu config 
        'menuMain' => [
            // item ref:    'href' => [ (optional) URL ],
            //              'pageId' => [ (optional) PAGE_ID from pages ],
            //              'title' => [ (optional) when using pageId, takes title from pages ]   ],
            ['pageId' => 'home'],    
            //['pageId' => 'example'],
        ],
    ];


    /**
     * General App configuration, to be set in child classes. During init these arrays are merged: $settingsDefaultXCore + $settingsDefaultApp + $settingsLocal
     * @var array 
     */
	protected $defaultSettingsApp = [];


    /**
     * Final working configuration to read settings from
     * @var array
     */
    protected $settings = [];



    /**
     * Is this run an ajax call?
     * (for this to work, call xhr with: headers: {'XCore-Request-Type': 'Ajax',},
     * /one sure way to detect xhr call is to just pass that info by yourself)
     * @var bool
     */
    protected $isAjaxCall = false;

    /**
     * Message/error to show
     * @var array 
     */
	protected $messages = [];
	
        /**
         * // todo: decide to keep it here or not
         * Stored commands to show and/or execute
         * @var array 
         */
        public $cmds = [];

	// input values / gp
    protected $vars = [];

	// action requested
	protected $action = '';

	protected $actionsAvailable = [];

	// database connection
	protected $dbConnection = null; 

    // pages available to render. usually set through settings/config
    // example:   'home' => ['title' => 'Home'],
    protected $pages = [];
    
    // menu to build from these pages or custom urls 
    protected $menuMain = [];
    
    /**
     * Page object
     * @var XCorePage|null 
     */
	protected $Page = null;
	
	/**
     * Base general App view 
     * @var XCoreView|null 
     */
	protected $View = null;



        /**
         * Contents collection to output
         * @var array 
         */
        public $content = [];




	public function __construct()
    {
	    $this->configure();
	    //$this->init(); // don't do this in construct. init in separate call
	}
	
	
	protected function configure()
    {
	    // set config
        $localConfig = [];
        if (file_exists(static::CONFIG_FILE))   {
            $localConfig = @include_once(static::CONFIG_FILE);
        }
        $this->settings = array_replace_recursive($this->defaultSettingsXCore, $this->defaultSettingsApp, (array) $localConfig);


	    // todo later: validate pages / menu config
	    $this->pages = $this->settings['pages'];
	    $this->menuMain = $this->settings['menuMain'];
    }


    /**
     * In most cases it should be able to be called in any App, without any interferences
     * So no need to override this with custom code unless you plan more custom-code pages 
     */
    public function init()
    {
        XCoreLog::set('app', $this->settings['log_app_path']);
	    $this->isAjaxCall = isset($_SERVER['HTTP_XCORE_REQUEST_TYPE']) && strtolower($_SERVER['HTTP_XCORE_REQUEST_TYPE']) === 'ajax';

	    // optional, connects if finds config
	    $this->dbConnection = XCoreUtil::databaseConnect($this->settings['dbAuth']);
	    
	    
        // init & sanitize input
        $this->action = XCoreUtil::cleanInputVar($_POST['action'] ?? $_GET['action']);
	    // clean this value after use, to prevent potential including this var in built urls - it's purpose is single-use
		unset($this->vars['action']);

        // page object, selected on user requested id (p = page id)
		$this->vars['p'] = XCoreUtil::cleanInputVar($_GET['p']);
        
	    $this->initPage();
    }

    
    /**
     * In most cases it should be able to be called in any App, without any interferences
     * So no need to override this with custom code 
     */
    protected function initPage()
    {
	    $this->Page = Loader::get(XCorePage::class, $this->vars['p']);
    }
    
    
    /**
     * Get configuration option value
     * @param $varName
     * @return mixed|null
     */
	public function getConfVar($varName)
    {
	    return $this->settings[$varName] ?? null;
    }
    
    /**
     * Get full configuration
     * @return array
     */
	public function getSettings()
    {
	    return $this->settings;
    }
    



    /**
     * Run action
     * @throws Exception
     */
	protected function runAction()
    {
        if (!$this->action) {
            return;
        }
        if ($this->action && in_array($this->action, $this->actionsAvailable)) {
            $this->msg('- Action called: ' . $this->action, 'info');
            $actionMethodName = "action_{$this->action}";
            if (!method_exists($this, $actionMethodName))  {
                throw new Exception('Action set as available, but no method named: '.$actionMethodName, 564573);
            }
            $this->$actionMethodName();
        }
        else {
            $this->msg('Action not found or unavailable', 'error');
            $this->sendContent([
                'success' => false,
                'code' => 'ACTION_NOT_FOUND',
            ]);
        }
	}


    /**
     * Output xhr or html body
     * @param array $response Response data to include in output (both ajax and frontend)
     * @throws Exception
     */
	protected function sendContent(array $response = [])
    {
        // make sure there's always 'result'. merge this way doesn't override, but sets if missed 
        $response = $response + ['result' => []];

	    if ($this->isAjaxCall)  {
	        // include collected notifications, but I believe one latest error is enough
            $lastMessage = $this->getMessages('error', 1, true);
            if ($lastMessage)
                $response['last_message'] = $lastMessage[0];
            if ($this->settings['DEV'])
                $response['allMessages'] = $this->getMessages();

            header('Content-type:application/json;charset=utf-8');
            print json_encode($response, JSON_PRETTY_PRINT);
        }
	    else    {
	        $this->buildAppOutput($response);
	        $this->View->render();
	        print $this->View->getOutput();
        }
        exit;
	}


    /**
     * Compiles main App output to display / extend to add some global markers for base template
     *
     * @param array $response Data returned from actions or other operations
     * @throws Exception
     */
    public function buildAppOutput(array $response = []): void
    {
        $this->View = Loader::get(XCoreView::class, XCoreView::TYPE__BASE);
        $this->View->setTemplate('base');

        try {
            $this->View->assign('BASE_HREF', $this->getConfVar('baseHref'));
            $this->View->assign('MENU_MAIN', Loader::get(XCoreViewhelperMenu::class)->render('main', [
                    // tbd: should be read from main config
                    'wrapItem' => '|',
                    'glue' => '',
            ]));
        } catch (Exception $e)  {
            $this->msg('View exception ('.$e->getCode().'): ' . $e->getMessage());
        }

        try {
            $this->Page->buildPageContent();
            $content = $this->Page->getOutput()['content'];
        } catch (Exception $e)  {
            $this->msg('Page exception ('.$e->getCode().'): ' . $e->getMessage());
        }
        $this->View->assign('PAGE_CONTENT', $content);
        $this->View->assign('MESSAGES', $this->View->displayMessages());
    }



	/**
     * MAIN RUN
     */
    public function handleRequest()
    {
		// control access here, if needed

		if ($this->action) {
		    $this->runAction();
        }

        $this->sendContent();
	}
	

	
	
	
	
	
	// RUN HELPERS


	/**
	 * Add message/notice
     * 
	 * @param string $message
	 * @param string $class - class for notice p, may be error or info (warn?)
	 * @param string $index - index can be checked in tag markup, to indicate error class in form element
	 */
    public function msg(string $message, string $class = '', string $index = ''): void
    {
		if ($index)  $this->messages[$index] = [$message, $class];
		else         $this->messages[] = [$message, $class];
	}


    /**
     * Get collected messages
     * @param string $class Filter by class/type
     * @param int $limit Return no more than this number of items. Like if you need only one.
     * @param bool $reverse Return newest first
     * @return array
     */
    public function getMessages(string $class = '', int $limit = 0, bool $reverse = false): array
    {
        $result = $this->messages;
        if ($class) {
            $workArray = $result;
            $result = [];
            foreach ($workArray as $item)   {
                if ($item[1] === $class)
                    $result[] = $item;
            }
        }
        if ($reverse) {
            $result = array_reverse($result);
        }
        if ($limit) {
            $result = array_slice($result, 0, $limit, true);
        }
        
		return $result;
	}


    /**
	 * Get available pages
     * @return array
	 */
    public function getPagesConfig(): array
    {
		return $this->pages;
	}

	/**
	 * Get Page object
     * @return XCorePage|null
     */
    public function getPageObject(): ?XCorePage
    {
		return $this->Page;
	}

	/**
	 * Get menu config
     * @return array
	 */
    public function getMenuMain(): array
    {
		return $this->menuMain;
	}


	/**
	 * Get menu config
	 */
    public function getDbConnection(): ?mysqli
    {
		return $this->dbConnection;
	}





	


    // SHORT


    /**
     * Get uri with query part
     * 
     * @param array $params
     * @param string $uriToProcess
     * @param bool $keepCurrentVars
     * @return string
     */
	public function linkTo_uri(array $params, string $uriToProcess, bool $keepCurrentVars = false): string
    {
		if ($keepCurrentVars)
			$params = array_merge($this->vars, $params);

		return XCoreUtil::linkTo_uri($params, $uriToProcess);
	}


    /**
     * Build <a> tag
     *
     * @param string $label
     * @param array $params
     * @param string $uriToProcess
     * @param bool $keepCurrentVars
     * @param array $config
     * @return string
     */
	public function linkTo(string $label, array $params, string $uriToProcess = '', bool $keepCurrentVars = false, array $config = []): string
    {
		if ($keepCurrentVars)
			$params = array_merge($this->vars, $params);

		return XCoreUtil::linkTo($label, $params, $uriToProcess, $config);
	}



	/**
     * Shorthand to main XCore - App object
     * (basically it will return your /app/SomeApp.php instance, which extends XCore
     * and the object is stored as singleton in Loader)
     * Of course for that reason we cannot just return $this in here, it wouldn't have much sense.
     * 
     * Important - don't use this when App object may be not instantiated yet, like in it's own construct / init etc. 
     * You will end in endless loop.
     * 
     * @return XCore|object
     */
	static public function App()
    {
	    return Loader::get(XCore::class);
    }
}
