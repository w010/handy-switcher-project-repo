<?php




class XCoreUtil  {

    /**
     * Get configuration option value (don't even try to call this in XCore constructor)
     * @param string $varName
     * @return mixed|null
     */
	static public function getConfVar(string $varName)
    {
	    return XCore::App()->getConfVar($varName) ?? null;
    }


	/**
     * Database connection 
     */
	static public function databaseConnect($auth): ?mysqli
    {
		if ($auth['host'])  {
			$connection = new mysqli($auth['host'], $auth['user'], $auth['password'], $auth['dbname']);
		}
		return $connection ?? null;
	}


	
	
    /**
     * Clean input variable
     * @param $value
     * @return string
     */
	static public function cleanInputVar($value): string
    {
        $value = trim($value);
        $value = strip_tags($value);
        $value = stripslashes($value);
        //$value = SqlInjection::basicBlockSql($value);
        $value = htmlspecialchars($value);
        return $value;
    }
	
	
	    

	// HTTP

    /**
     * @param string $url
     * @param array $config
     */
    static public function redirect(string $url, array $config = []): void
    {
        header('Location: '.$url);
        exit;
	}
	
	
	

    /**
     * Build a nice query string from array of params
     * 
     * @param array $params
     * @param string $uriToProcess
     * @return string
     */
	static public function linkTo_uri(array $params, string $uriToProcess = ''): string
    {
        $uriParts = preg_split('/\?|&/', $uriToProcess);
        // take the first partial - usually domain or url before '?'
        //$uriFinal = array_shift($uriParts);
        // for now, leave all unknown existing params, take only first part + incoming from $params
        /*foreach ($uriParts as $paramValPair)    {
            [$param, $val] = explode('=', $paramValPair);
        }*/
        
        $queryPart = implode('&', array_map(
			function($k, $v) {
				return urlencode($k).'='.urlencode($v); 
			},
			array_keys($params),
			array_values($params)
		));
        
        return $uriParts[0] . ($queryPart ? '?'.$queryPart : ''); 
	}


    /**
     * Build a nice query string from array of params
     *
     * @param string $label
     * @param array $params
     * @param string $uriToProcess
     * @param array $config [aTagParams: string, ]
     * @return string
     */
	static public function linkTo(string $label, array $params, string $uriToProcess = '', array $config = []): string
    {
		$uri = static::linkTo_uri($params, $uriToProcess);
		return '<a '. $config['aTagParams'] .' href="'.$uri.'">'.$label.'</a>';
	}
	
	
	/**
     * Try to determine base url, if not configured
     */
    static public function getCurrentBaseUrl(): string
    {
        return 'http' . ($_SERVER['HTTPS']?'s':'') .'://'. $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	}




	// VIEW & TEMPLATING STUFF

    /**
     * Take care of the ### - adds where needed
     * @param string $marker
     * @param string $wrap Add auto prefix and suffix to marker string
     * @return string
     */
    static public function markerName(string $marker, string $wrap = '###'): string
    {
        return $wrap . $marker . $wrap;
    }


    /**
     * Return given value wrapped in wrap parts / exploded with pipe
     * @param string $value
     * @param string $wrap Add auto prefix and suffix to marker string
     * @return string
     */
    static public function wrap(string $value, string $wrap): string
    {
        [$wrapA, $wrapB] = explode('|', $wrap);
        return $wrapA . $value . $wrapB;
    }
	
    

	
	
	
	// FILESYSTEM

    /**
     * List files / return items as whole paths
     * @param string $dir
     * @param string $ext
     * @return array|false
     */
	static public function getFilesFromDirectory_paths(string $dir = '', string $ext = '*')
    {
		$files = glob($dir.'*.{' . $ext . '}', GLOB_BRACE);	// GLOB_BRACE: match multiple patterns in {comma,list}. NOTE that it's not regexp!
		if (!is_array($files))  $files = [];
		return $files;
	}
	
	/**
     * List files / return as filenames
     * @param string $dir
     * @param string $ext
     * @return array|false
     */
	static public function getFilesFromDirectory_filenames(string $dir = '', string $ext = '*')
    {
		$files = [];
		$filesAll = scandir($dir);
        foreach ($filesAll as $file) {
		    if (substr($file, 0, 1) === '.')    {
		        continue;
            }
    		if ($ext === '*') {
                $files[] = $file;
            }
    		else if (substr($file, -5, strlen($ext)+1) === '.'.$ext) {
                $files[] = $file;
            }
		}
        
		return $files;
	}


    /**
     * List directories
     * @param string $dir
     * @param array $skip
     * @param bool $absolute
     * @return array
     */
	static public function getDirectories($dir = '', $skip = [], $absolute = false)
    {
		$dir = (!$absolute ? PATH_site : '') . $dir;
		$items = (array) scandir($dir);
		foreach ($items as $item) {
			if (preg_match("/(^(([\.]){1,2})$|(\.(svn|git|md))|(Thumbs\.db|\.DS_STORE))$|^deprecation_/iu", $item, $match)
				|| !is_dir($dir . $item)
			)
				$skip[] = $item;
		}
		return array_diff($items, $skip);
	}



	/**
     * Exec shell command 
     */
	private function exec_control($cmd, $saveCmd = true)
    {
        die('exec_control implementation not yet finished');
	    $output = '';
		if ($this->options['dontExecCommands']) {
			$this->msg('command not executed - exec is disabled - @see option dontExecCommands', 'info');
		}
		elseif ($_POST['dontExec']) {
			$this->msg('(command not executed)', 'info');
        }
		else    {
		    if (ini_get('safe_mode')){
			    exec($cmd, $outputArray, $return);
			    $output = implode ("\n", $outputArray);
            }
		    else    {
			    // $output = shell_exec($cmd);
                // 2>&1 displays output even on error, which normally outputs null. comment if causes troubles
                // note that it doesn't work properly when && is used to join multiple commands in one line call
			    $output = shell_exec($cmd . ' 2>&1');
            }
        }

        // var_dump($return);
        // var_dump($output);
        // var_dump($outputArray);
        /*echo exec('whoami');
        echo exec('groups');
        echo exec('sudo -v');
        echo exec('/usr/bin/docker -v');*/

		if ($saveCmd)   {
			$this->cmds[] = ['command' => $cmd, 'output' => $output];
        }
		
		return $output;
	}


	
	
	
	// FORM FIELDS

        /*public function formField_radio($name, $value, $valueDefault = '', $class = '', $id = '', $additionalParams = [])   {
            $params = [
                'type' => 'radio',
                'name' => $name,
                'value' => $value,
            ];
            if ($class)     $params['class'] = $class;
            if ($id)        $params['id'] = $id;
            $params = array_merge($params, $additionalParams);
            if ($_POST[$name] == $value  ||  (!$_POST[$name]  &&  $valueDefault == $value))
                $params['checked'] = '';
            $code = "<input ";
            foreach ($params as $param => $value) {
                $code .= $param . ($value ? '="'.$value.'"' : '');
            }
            $code .= ">";
            return $code;
        }*/
        
        /*public function formField_check($name, $value, $valueDefault = '', $class = '', $id = '', $additionalParams = [])   {
            $params = [
                'type' => 'checkbox',
                'name' => $name,
                'value' => $value,
            ];
            if ($class)     $params['class'] = $class;
            if ($id)        $params['id'] = $id;
            $params = array_merge($params, $additionalParams);
            if ($_POST[$name] == $value  ||  (!$_POST[$name]  &&  $valueDefault == $value))
                $params['checked'] = '';
            $code = "<input ";
            foreach ($params as $param => $value) {
                $code .= $param . ($value ? '="'.$value.'"' : '');
            }
            $code .= ">";
            return $code;
        }*/

    
     

	// INPUT CONTROL

	/* control required params for action */
	/*function paramsRequiredPass($params) {
		$pass = true;
		foreach ($params as $param => $value) {
			if (!$value) {
				$this->msg('error: <b>' . $param . '</b> must be set. ', 'error', $param);
				$pass = false;
			}
		}
		return $pass;
	}*/

	/* check if error of given field exists */
	/*function checkFieldError($param)	{
		if (array_key_exists($param, $this->messages))
			return true;
	}*/

	/* prints error class on form input, if present */
	/*function checkFieldError_printClass($param, $classes = '')	{
		if ($this->checkFieldError($param))
			$classes .= ' error';
		return ' class="'.$classes.'"';
	}*/


}


