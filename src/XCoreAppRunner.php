<?php


/**
 * Class XCoreAppRunner
 */
abstract class XCoreAppRunner
{
    /**
     * Webroot path, relative to project root
     * Set to ie. "public" if webroot entry point (index.php) is in subdirectory
     */
    protected static string $webroot_path = '';


    /**
     * Define webroot path
     * To be used in index.php, if running from other directory than project root
     * @param string $path
     */
    static public function webroot(string $path): void
    {
        static::$webroot_path = trim($path, '/');
    }


    /**
     * Prepare system environment and make sure it's ready and operational + other tech stuff
     * Also setup XCore low-level essentials like filesystem, autoload etc.
     * Often customized in local AppRunner, but no need to.
     */
    static public function ready(): void
    {
        static::setErrorHandling();
        static::doTheClassicInit();
        static::runClassLoader();
    }

    /**
     * Create the App - prepare App/XCore object
     * XCore is basically a singleton, so only touch it, to make it builds instance and possibly does some init
     */
    abstract static public function set(): void;


    /**
     * Run the App.
     * Main method called to start operation.
     * Usually it calls App->handleRequest() or does something similar.
     */
    abstract static public function go(): void;



    /**
     * Set error display
     */
    static protected function setErrorHandling(): void
    {
        error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING ^ E_STRICT ^ E_DEPRECATED);
        // on some projects / envs might be needed to see what's happening when you 500)
        //error_reporting(-1); // reports all errors
        ini_set('display_errors', '1'); // shows all errors
        //ini_set('log_errors', 1);
    }


    /**
     * Do the classic init
     */
    static protected function doTheClassicInit(): void
    {
        // return;
        if (!defined('PATH_site'))	{
            define('PATH_thisScript', str_replace('//', '/', str_replace('\\', '/',
                (PHP_SAPI == 'fpm-fcgi' || PHP_SAPI == 'cgi' || PHP_SAPI == 'isapi' || PHP_SAPI == 'cgi-fcgi') &&
                ($_SERVER['ORIG_PATH_TRANSLATED'] || $_SERVER['PATH_TRANSLATED']) ?
                ($_SERVER['ORIG_PATH_TRANSLATED'] ?: $_SERVER['PATH_TRANSLATED']) :
                ($_SERVER['ORIG_SCRIPT_FILENAME'] ?: $_SERVER['SCRIPT_FILENAME']))));
            // define('PATH_site', realpath(dirname(PATH_thisScript)).'/');
            // define('PATH_site', str_replace('/', '//', realpath(dirname(PATH_thisScript)).'/'));
            // define('PATH_site', rtrim(realpath(dirname(PATH_thisScript).'/'), '/') . '/');

            // if the webroot != project root, we must strip the webroot segment from final path
            $thisScript_dirname__webrootPathStripped = strlen(static::$webroot_path) 
                    ? str_replace('/'.static::$webroot_path, '', dirname(PATH_thisScript))
                    : dirname(PATH_thisScript);
            define('PATH_site', str_replace('\\', '\\\\', 
                    rtrim(realpath($thisScript_dirname__webrootPathStripped.'/'), '/')
                                . '/'));
        }
    }


    /**
     * Initialize Class Loader
     */
    static protected function runClassLoader(): void
    {
        require_once PATH_site . '/src/XCoreLoader.php';
        require_once PATH_site . '/app/Loader.php';
        
        Loader::includeClasses();
    }
    
}


