<?php




/**
 * Shorthand
 * @param mixed $var
 * @param string $label
 * @param int|bool $exit
 * @param int $method - 1 = default - var_export, 2 = var_dump, 3 = print_r
 */
function debuxc(mixed $var, string $label = '', int|bool $exit = false, int $method = 1) {
    switch ($method)    {
        case 1:
        default:
            XCoreDebug::var_export($var, $label, $exit);
            break;
        case 2:
            XCoreDebug::var_dump($var, $label, $exit);
            break;
        case 3:
            XCoreDebug::print_r($var, $label, $exit);
            break;
    }
}


/**
 * Debugging helper
 */
class XCoreDebug {

    /**
     * Simple var_dump but with nice print
     * Parameters are:
     * @param mixed $dumper
     * @param mixed $var
     * @param string $label
     * @param int|bool $exit
     */
    protected static function _var_debug(mixed $dumper, mixed $var, string $label = '', int|bool $exit = false) {
        // get backtrace for the purpose of displaying where it was called. also used to render custom backtrace if exit = true
        $backtrace = debug_backtrace(3, 50);
        // strip first unneeded items
        if ($backtrace[2]['function'] === 'debuxc') {       $backtrace = array_slice($backtrace, 2);    }
        if ($backtrace[0]['function'] === '_var_debug') {   $backtrace = array_slice($backtrace, 1);    }

        $calledAt = basename($backtrace[0]['file']) . " line: {$backtrace[0]['line']}";

        print "<pre class='debugxc' style='border: 1px solid orange; background-color: #e1e1e1; color: #000; padding: 10px;'>";
        $label .= ($label ? " - " : '') . $calledAt;
        if ($label) {
            print "<h5 style='color: #000; margin-top: 0; font-size: 13px; font-weight: bold;'>$label</h5>";
        }

        $dumper($var);
        print "</pre>";

        if ($exit)  {
            // render own simple backtrace log
            foreach ($backtrace as $i => $btitem)  {
                $file = str_replace(
                    str_replace(['//', '\\\\'], ['/', '\\'], trim(PATH_site, '\\/') ),
                    '',
                    $btitem['file']
                );

                $params = [];
                /*foreach ($btitem['args'] as $arg) {
                    if (is_string($arg))    {
                        $param = "'$arg'";
                    }
                    else {
                        $param = (string) $arg;
                    }

                    $params[] = substr($param, 0, 100);
                }*/
                $params_text = implode(', ', $params);

                print "<pre class='debugxc-backtrace' style='margin: .3em 0;'>#$i $file({$btitem['line']}): {$btitem['class']}{$btitem['type']}{$btitem['function']}($params_text)" . '</pre>';
            }

            // native backtrace
            //print "<pre>";
            //debug_print_backtrace();
            //print "</pre>";
            die('debug exit');
        }
    }

    public static function var_dump(mixed $var, string $label = '', int|bool $exit = false) {
        self::_var_debug('var_dump', $var, $label, $exit);
    }

    public static function var_export(mixed $var, string $label = '', int|bool $exit = false) {
        self::_var_debug(fn($v) => print htmlspecialchars(var_export($v, true)), $var, $label, $exit);
    }

    public static function print_r(mixed $var, string $label = '', int|bool $exit = false) {
        self::_var_debug('print_r', $var, $label, $exit);
    }
}


