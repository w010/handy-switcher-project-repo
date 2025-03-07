<?php


/**
 * Simple multi-log. Use:
 * Init:
 * XCoreLog::set('mylogger', $this->settings['log_custom_path']);
 * 
 * Use:
 * XCoreLog::get('mylogger')->log('Important message!');
 * 
 * Class XCoreLog
 */
class XCoreLog implements XCoreSingleton  {

    protected $file = '';

    static protected $loggers = [];



    public function __construct($file)
    {
        $this->file = $file;
    }


    /**
     * Add a message to a log instance
     * @param string $message
     */
    public function log(string $message): void
    {
        $filePointer = fopen($this->file, "a");

        $logMsg = date('Y-m-d H:i:s') . "\t\t" . $message . "\n";

        //rewind($filePointer);
        fwrite($filePointer, $logMsg);
        fclose($filePointer);
    }


    /**
     * Setup logger instance. You can init many of them, each one instance has one log file.
     * @param string $loggerName Identifier of a logger instance
     * @param string $logPath Relative path to log file for chosen instance
     * @return object|XCoreLog
     */
    static public function set(string $loggerName = 'app', string $logPath = 'app.log')
    {
        if (static::$loggers[$loggerName]['instance'])    {
            return static::$loggers[$loggerName]['instance'];
        }
	    return static::$loggers[$loggerName]['instance'] = Loader::get(XCoreLog::class, $logPath);
    }
    
    
    /**
     * Returns given logger instance
     * @param string $loggerName
     * @return object|XCoreLog
     */
    static public function get(string $loggerName = 'app')
    {
        return self::set($loggerName);
    }
}
