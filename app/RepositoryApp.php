<?php
/**
 * Projects Repository app
 * Handy Switcher add-on
 *
 *
 * BE/FE/Env Handy Switcher (TYPO3 dedicated, but is kind of universal)
 * Great help for integrators with many web projects, that runs on multiple parallel environments/contexts.
 *
 *
 * Subpackage: Projects Repository
 *
 * based on XCore version: 0.2.4
 */


// version of repository engine (storage, structure etc)
// - change only when output / communication / repo conf changes
const REPO_VERSION = '0.2.4';

// version of app itself - not interesting for using api
const REPO_APP_VERSION = '0.5.0-dev';



/**
 * wolo.pl '.' studio
 * 2017-2025
 * wolo.wolski(at)gmail.com
 * https://wolo.pl/
 *
 * https://chrome.google.com/webstore/detail/typo3-backend-frontend-ha/ohemimdlihjdeacgbccdkafckackmcmn
 * https://github.com/w010/chrome-typo3-switcher
 *
 *
 *
 * This component here is a simple (but fully functional - in basis) draft of Project Repository serverside.
 * The idea is to centralize/keep in-sync company's webprojects urls, to multiple working environments of each,
 * to make sure every developer in team when starting work on a project has always full set of latest and checked set
 * of urls to projects and its servers / instances / stages to lightspeed jumping between them only swapping the domain
 * within one click, without even need to configure your common projects manually to use. Just wait for someone else
 * do it and fetch ready config ;)
 *
 * (Ok, maybe it kinda sounds like "wtf, why do I even need something like that, when I can make a bookmark
 * on a browser bar like I do for years" but believe me or not, if you maintain dozens of multidomain webprojects on
 * automated parallel environments on everyday basis, do every task on different one of them, then integrate changes
 * one-by-one on every stage server and every client has different naming conventions, this one changed domains recently,
 * that one broke subdomains, the other one never documents anything, and this one over there thinks you remember all
 * of urls to all of important-only-to-him subpages on dev, because you worked there, once, for an hour, three months ago...
 * After a few weeks using this you probably won't imagine your work without this plugin anymore.)
 *
 * You can start to use this script as-is just by putting it somewhere on your webserver (rather http auth protected),
 * upload projects data (as json files, like these exported from Chrome plugin) into /data directory and that's it,
 * basically ready to fetch by your teammates.
 * It may be a good base to write something better, if you need. Ie. as Typo3 plugin and records, using fe_user
 * authentication, or so. (That one might be helpful when I finish push-config-to-repo functionality.)
 */





// todo:
// plus additional error handling on ext side to avoid errors when some trash comes
// htaccess pass




/**
 * Class ProjectsRepository
 */
class RepositoryApp extends XCore  {



    /**
     * Defaults
     * @var array
     */
    protected $defaultSettingsApp = [
        'repo' => [
            'repo_keys' => [],
            'data_dir' => 'data',
            'log_repo_path' => 'data/repo.log',
        ],

        'pages' => [
            'home' => ['title' => 'Home'],
            'maintenance' => ['title' => 'Maintenance'],
        ],

        'menuMain' => [
            ['pageId' => 'home'],
            ['pageId' => 'maintenance'],
        ],
    ];




    protected $actionsAvailable = [
        'handshake', 'logout', 'fetch', 'push', 'audit', 'convert_json', 'project_delete', 'download_all',
    ];


    /**
     * Only dir name
     * @var string
     */
    protected $dataDir = '';


    /**
     * @var string
     */
    protected $dataPath = '';


    public function getDataPath(): string
    {
        return $this->dataPath;
    }



    /**
     * Repo access key
     * @var string
     */
    protected $key = '';

    /**
     * Repo access level
     * @var string
     */
    protected $accessLevel = '';





    protected function configure()
    {
        // no .htaccess
        if (!file_exists(PATH_site.'public/.htaccess'))   {
            $this->msg('- No <b>public/.htaccess</b> file found. Http password is recommended. <i>(NOTE - "Repo keys" are only for internal roles in team)</i>', 'warn');
        }

        // no config file
        if (!file_exists(PATH_site.static::CONFIG_FILE))   {
            $this->msg('- FIRST RUN? No <b>config/app_config.php</b> file found! Copying from app_config.example.php', 'warn');
            copy(PATH_site . 'config/app_config.example.php', PATH_site . static::CONFIG_FILE);
        }

        // setup
        parent::configure();

        // warn about default keys left in config
        if ($this->settings['repo']['repo_keys'][md5('mykey1')] || $this->settings['repo']['repo_keys'][md5('mykey2')] || $this->settings['repo']['repo_keys'][md5('mykey3')]) {
            $this->msg('- You should remove default example <b>repo_keys</b> from app_config.php', 'warn');
        }

        // init & validate setup
        $this->dataDir = trim($this->settings['repo']['data_dir'], '/');
        if (!$this->dataDir)   {
            Throw new Exception('Configuration error! No "data_dir" set', 982634);
        }
        $this->dataPath = PATH_site . $this->dataDir. '/';

        // check data directory / init dummy data
        if (!is_dir($this->dataPath))   {
            $this->msg('- FIRST RUN? No data dir found at: <b>[project]/'.$this->dataDir.'/</b>. - <b>Inserting example data</b>', 'warn');
            mkdir($this->dataPath);
            foreach (glob(PATH_site.'data_example/' . '{.,}*', GLOB_BRACE) as $file) {
                if (is_file($file)) {
                    copy($file, $this->dataPath . basename($file));
                }
            }
        }

        // other configuration
        if ($this->settings['repo']['read_only'])   {
            $this->actionsAvailable = ['handshake', 'logout', 'fetch'];
        }
    }



    /**
     * Init object
     */
    public function init()
    {
        XCoreLog::set('repo', $this->settings['repo']['log_repo_path']);
        parent::init();
        // for testing, but no need to control access
        if (!$this->isAjaxCall  &&  $_GET['ajax']) {
            $this->isAjaxCall = true;
        }

        // authorisation

        session_start();
        $session_webroot = $_SERVER['HTTP_HOST'] . dirname($GLOBALS['_SERVER']['SCRIPT_NAME']);

        // update auth session if new key comes
        $key_incoming = XCoreUtil::cleanInputVar($_SERVER['HTTP_SWITCHER_REPO_KEY'] ?? $_POST['key'] ?? '');
        // in ajax mode check key on every call. in web mode - keep the session
        if ($key_incoming || $this->isAjaxCall) {
            $key_hashed = md5($key_incoming);
            // (in case any problems authorizing with valid key, check what XCoreUtil::cleanInputVar does with incoming var)
            if (in_array($key_hashed, array_keys($this->settings['repo']['repo_keys'])))    {
                // update session
                $_SESSION['repo_auth'][$session_webroot]['key'] = $key_hashed;
                $this->msg('Key - AUTHORIZED', 'info');
            }
            // deauthorise if invalid
            else {
                unset($_SESSION['repo_auth']);
                session_destroy();
                if (!$this->isAjaxCall) {
                    // display this message only in web mode, for ajax - it can work on "read_without_key"
                    $this->msg('UNAUTHORIZED - bad key', 'error');
                }
            }
        }

        // get key from session and check permissions every time - might have changed in the meantime
        $this->key = (string) $_SESSION['repo_auth'][$session_webroot]['key'];
        $this->accessLevel = (string) $this->settings['repo']['repo_keys'][$this->key];

        if (!$this->key && $this->settings['repo']['read_without_key'])    {
            $this->accessLevel = 'READ';
            $this->key = 'fake_read_key';
        }
    }


    /**
     * @return string
     */
    public function getAccessLevel(): string
    {
        return $this->accessLevel;
    }


    /**
     * MAIN RUN
     */
    public function handleRequest()
    {
        // compatibility check - if ajax has requested repository engine in specific version, compare and send special message if not matched
        if (version_compare($_SERVER['HTTP_SWITCHER_REPO_VERSION_REQUEST'], REPO_VERSION) > 0)    {
            $this->msg('Repository engine version ('.REPO_VERSION.') is lower than requested ('.$_SERVER['HTTP_SWITCHER_REPO_VERSION_REQUEST']
                .'). Some features may not work or be available. Please check and update your Repo.', 'compatibility__repo_engine_version', 'compatibility_control');
        }


        // CONTROL ACCESS


        // check if authorized, if checking enabled - keys exists, repo is not public. if it is - is a public read mode (?)
        if ($this->isAjaxCall)  {
            if (!$this->key || !$this->accessLevel)    {
                $this->msg('Unauthorized - invalid repo key', 'error');
                $this->sendContent([
                    'success' => false,
                    'code' => 'INVALID_KEY',
                ]);
                exit;
            }
        }

        // todo: catch exception here
        // should be called independently from ajax, we can call actions also in app interface
        $this->runAction();

        $this->sendContent();
    }

    /**
     * Compiles main App output to display
     *
     * @param array $response Data returned from actions or other operations
     * @throws Exception
     */
    public function buildAppOutput(array $response = []): void
    {
        parent::buildAppOutput($response);
        $this->View->assignMultiple([
            'APP_TITLE' => $this->settings['repo']['repo_name'] ?: 'Projects Repository',
            'MENU_MAIN' => Loader::get(XCoreViewhelperMenu::class)->render('main', [
                'wrapItem' => '',
                'glue' => ' | ',
            ]),
            'AUTH_LEVEL' => $this->getAccessLevel() ?: 'UNAUTHORIZED',
            'AUTH_LEVEL_CLASS' => $this->getAccessLevel() === 'ADMIN' ? 'level-success' : ($this->getAccessLevel() === 'WRITE' ? 'level-success' : ($this->getAccessLevel() === 'READ' ? 'level-info' : 'level-error' )),
            'LINK_LOGOUT' => $this->linkTo('End session/logout', ['action' => 'logout']),
        ]);
    }

    /**
     * Output xhr or html body
     * @param array $response Response data to include in output (both ajax and frontend)
     * @throws Exception
     */
    protected function sendContent(array $response = [])
    {
        $commonOutput = [
            'repo_version' => REPO_VERSION,
            'access_level' => $this->accessLevel,
        ];

        // additional static data, in special cases attached to all output
        if ($compatibilityMessage = $this->messages['compatibility_control'])   {
            $commonOutput['compatibility'] = [$compatibilityMessage[0], $compatibilityMessage[1], 'check-engine'];
        }


        // add standard common response parts (move them to top)
        parent::sendContent($commonOutput + $response);
    }


    /**
     * Custom action - HANDSHAKE
     * To check connection and authorisation/permissions
     */
    protected function action_handshake()
    {
        $this->sendContent([
            'success' => true,
            'result' => [
                'say' => 'HELLO'
            ]]);
    }


    /**
     * Custom action - FETCH    / zmienic raczej na pull, a fetch by pobieral sama liste z nazwami i uids
     */
    protected function action_fetch()
    {
        $this->sendContent([
            'success' => true,
            'result' => Util::getProjects_assoc($this->dataPath)
        ]);
    }


    /**
     * Custom action - PUSH
     */
    protected function action_push()
    {
        $this->check_access_level('WRITE');

        $defaultResponse = [
            'success' => false,
        ];
        $projectDataArray = (array) $_POST['projectData'];

        if (!count($projectDataArray))  {
            $this->msg('No incoming data to make a Project from.', 'error');

            $this->sendContent([
                    'code' => 'EXCEPTION_DATA_ERROR',
                ] + $defaultResponse); // + operator doesn't override values!
        }

        try {
            // create Project object, which will validate the incoming data itself
            $Project = Loader::get(ModelProject::class, $projectDataArray);

        } catch (Exception $e)  {
            $this->msg('Data validation exception: ' . $e->getMessage(), 'error');

            $this->sendContent([
                    'code' => 'EXCEPTION_DATA_ERROR',
                ] + $defaultResponse);
            exit;
        }

        // if not force overwrite, do some additional validation against conflicts / and overall data integrity
        // todo: merge GP vars
        if (!$_POST['force'])    {
            // look in current repo data
            foreach(Util::getProjects($this->dataPath) as $projectTestItem)  {
                // check if UUID already exists
                if ($projectTestItem->getUuid()  &&  $projectTestItem->getUuid() === $Project->getUuid())   {
                    // send back data to feed diff / merge dialog
                    $this->msg('Uuid already exist', 'error');
                    $this->sendContent($defaultResponse + [
                            'code' => 'CONFLICT_UUID',
                            'result' => [
                                'project_conflicted' => $projectTestItem->toArray(),
                            ],
                        ] + $defaultResponse);
                }

                if (stristr($projectTestItem->getName(), $Project->getName()))    {
                    // send back data to feed diff / merge dialog
                    $this->msg('Similar name exist, review', 'error');
                    $this->sendContent([
                            'code' => 'CONFLICT_NAME',
                            'result' => [
                                'project_conflicted' => $projectTestItem->toArray(),
                            ],
                        ] + $defaultResponse);
                }
            }
        }


        // store project
        $success = $Project->store();
        XCoreLog::get('repo')->log('INPUT: Project was pushed & stored. UUID: ' . $Project->getUuid());

        // todo? : after storing recheck duplicates state - maybe now is ok and we can disable warning

        $this->sendContent([
            'success' => $success,
            'code' => 'PROJECT_STORED',
        ]);
    }


    /**
     * End key-authorized user session
     */
    protected function action_logout()
    {
        // clear session
        $_SESSION = [];

        // redirect to current page (excluding 'action' param)
        XCoreUtil::redirect(
            XCoreUtil::linkTo_uri(
                [], XCoreUtil::getCurrentBaseUrl()
            ));
    }


    /**
     * Check data
     */
    protected function action_audit()
    {
        $this->check_access_level('ADMIN');


        // read meta
        try     {
            $repoDataStatus = Util::getRepoDataMetaFile();
        } catch (Exception $e)  {
            $this->msg('Data Meta read problem: '.$e->getMessage(), 'error');
            $this->msg('- Creating new empty .meta file', 'warn');
            Util::saveRepoDataStatusFile(new stdClass());
            $repoDataStatus = Util::getRepoDataMetaFile();
        }

        // get projects, collect uuids
        $uuids = [];
        $projects = Util::getProjects($this->dataPath, $uuids);


        // AUDIT:
        // test for uuid duplicate

        // Ensure 'audit' exists as an object
        if (!isset($repoDataStatus->audit) || !is_object($repoDataStatus->audit)) {
            $repoDataStatus->audit = new stdClass();
        }

        $repoDataStatus->audit->duplicated_uuids = 1;
        $uuidsDuplicates = [];
        foreach ($projects as $project) {
            if (count(array_keys($uuids, $project->getUuid())) > 1) {
                $uuidsDuplicates[] = $project->getUuid();
            }
        }

        if (count($uuidsDuplicates))   {
            // store this fact in data status file (/data/.repo)
            $repoDataStatus->audit->duplicated_uuids = -1;
            XCoreLog::get('repo')->log('INTEGRITY: Duplicated UUIDs detected! uuids: '.implode(', ', $uuidsDuplicates));
        }



        // AUDIT:
        // detect wrong format files / big multi-project json

        $repoDataStatus->audit->multiproject_files = 1;
        foreach (Util::getFilesFromDirectory_paths($this->dataPath, 'json') as $file) {
            $fileContent = file_get_contents($file);
            if (preg_match('/^\[/', trim($fileContent)))    {
                $repoDataStatus->audit->multiproject_files = -1;
                XCoreLog::get('repo')->log('INTEGRITY: Wrong file format found / multi-project json!');
                break;
            }
        }

        $repoDataStatus->audit->last_check = time();

        Util::saveRepoDataStatusFile($repoDataStatus);
    }



    /**
     * Convert multi-project jsons to single-project
     */
    protected function action_convert_json()
    {
        $this->check_access_level('ADMIN');

        foreach (Util::getFilesFromDirectory_paths($this->dataPath, 'json') as $file) {
            $fileContent = file_get_contents($file);
            if (preg_match('/^\[/', trim($fileContent)))    {

                $fileParsedArray = (array) @json_decode($fileContent, true);

                $convertCount = 0;
                foreach ($fileParsedArray as $projectItem)  {
                    $Project = Loader::get(ModelProject::class, $projectItem);
                    $Project->store();
                    $convertCount++;
                }

                rename($file, $file . '.converted');
                XCoreLog::get('repo')->log('CONVERT: Multi-project json: '.basename($file).' - convert to single files, import count: '.$convertCount);
            }
        }

        $repoDataStatus = Util::getRepoDataMetaFile();
        $repoDataStatus->audit->multiproject_files = 1;
        Util::saveRepoDataStatusFile($repoDataStatus);
    }


    /**
     * Download all (for admins)
     */
    protected function action_download_all()
    {
        $this->check_access_level('ADMIN');
// todo: check utf8 stuff
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename: handyswitcher-repo-allprojects-'.time().'.json');
        print json_encode(Util::getProjects_assoc($this->dataPath));
        exit;
    }



    /**
     * Delete project
     */
    protected function action_project_delete()
    {
        $this->check_access_level('ADMIN');

        $uuid = Util::cleanInputVar($_GET['uuid']);

        if (!$uuid) {
            $this->msg('Wrong or no Uuid given!', 'error');
            $this->sendContent([
                'success' => false,
                'code' => 'INVALID_UUID',
            ]);
            exit;
        }

        foreach (Util::getFilesFromDirectory_paths($this->dataPath, 'json') as $file) {
            $fileContent = file_get_contents($file);
            $fileParsedArray = (array)@json_decode($fileContent, true);
            if ($fileParsedArray['uuid'] === $uuid) {
                rename ($file, $file . '.'.time().'-deleted');
                XCoreLog::get('repo')->log('DELETE: '.$uuid . ' file: ' . basename($file));
                $this->msg('Project deleted', 'success');
                $this->sendContent([
                    'success' => true,
                    'code' => 'DELETED',
                ]);
            }
        }
    }



    public function check_access_level(string $level): void
    {
        if ($level === 'WRITE' && $this->accessLevel === 'ADMIN')
            return;

        if ($this->accessLevel !== $level)    {
            $this->msg('Unauthorized - permission level too low for this operation', 'error');
            $this->sendContent([
                'success' => false,
                'code' => 'AUTH_LEVEL_TOO_LOW',
            ]);
            exit;
        }
    }
}

