<?php

/**
 * General View object
 */
class XCoreView  {

    const TYPE__BASE = 'base';
    const TYPE__PAGE = 'page';
    const TYPE__WIDGET = 'widget';

    /**
     * @var string View type - just in case, set if we are on Page view or Widget view
     */
    protected $type = '';

    /**
     * @var string View generated output
     */
    protected $output = '';

    /**
     * @var string Template
     */
    protected $template = '';

    /**
     * @var array Items to replace in view
     */
    protected $markers = [];


    /**
     * @var string Path to template directory
     */
    protected $templatesPath = '';



    /**
     * XCore App
     * @var XCore|object 
     */
    protected $App = null;


    /**
     * XCoreView constructor.
     * @param string $type ::TYPE__BASE|::TYPE__PAGE|::TYPE__WIDGET
     */
    public function __construct(string $type)
    {
        $this->App = XCore::App();
        $this->templatesPath = PATH_site . rtrim(
            (XCoreUtil::getConfVar('templatesPath') ?? 'templates')
                , '/').'/';
    }





    /**
     * @param string $marker Without ###
     * @param $value
     * @param string $wrap Add auto prefix and suffix to marker string
     * @return XCoreView
     */
    public function assign(string $marker, $value, string $wrap = '###'): XCoreView
    {
        if (!is_string($value)) {
            $value = '[XCoreView] Possible bad value - check the marker "'.$marker.'", which came with value of type: '.gettype($value).' and value: '.(string) $value;
        }
        $marker = XCoreUtil::markerName($marker, $wrap);
        $this->markers[$marker] = $value;
        return $this;
    }

    /**
     * @param array $markers As key => value pairs  (Without ###)
     * @param string $wrap Add auto prefix and suffix to marker string
     * @return XCoreView
     */
    public function assignMultiple(array $markers, string $wrap = '###'): XCoreView
    {
        foreach ($markers as $marker => $value) {
            $this->assign($marker, $value, $wrap);
        }
        return $this;
    }



    /**
     * Read template by name (omit file ext etc.)
     * @param string $templateName
     * @return string
     * @throws Exception
     */
    protected function readTemplate(string $templateName): string
    {
        return $this->readTemplateFile($templateName . '.html');
    }
    
    /**
     * @param string $fileName
     * @return string
     * @throws Exception
     */
    protected function readTemplateFile(string $fileName): string
    {
        if (!file_exists($this->templatesPath . $fileName)) {
            Throw new Exception('Template error - File doesn\'t exist: '.$fileName, 3459835);
        }
        if (!$template = file_get_contents($this->templatesPath . $fileName))    {
            Throw new Exception('Template error - Cannot read file: '.$fileName, 3459836);
        }
        return $template;
    }


    /**
     * Read and sets the template for the current View
     * @param string $templateName Template name (not filename)  
     * @return XCoreView
     * @throws Exception
     */
    public function setTemplate(string $templateName): XCoreView
    {
        $fileName = $templateName . '.html';
        $this->template = $this->readTemplateFile($fileName);
        return $this;
    }
    
    
    
    
    /**
     * Display generated messages with class if set 
     */
	public function displayMessages(): string
    {
		$content = '';
		foreach ($this->App->getMessages() as $message) {
			$content .= '<p'.($message[1] ? ' class="'.$message[1].'">':'>') . $message[0] . '</p>';
		}
		return $content;
	}
	
    
    // TEMPLATING

    /**
     * typo3-like standard replace marker method
     * @param string $subject
     * @param array $markerArray
     * @return string
     */
	function substituteMarkerArray(string $subject, array $markerArray): string
    {
		return str_replace(array_keys($markerArray), array_values($markerArray), $subject);
	}


	/**
     * Compile the body to output
     */
    public function render(): XCoreView
    {
        $this->output = $this->substituteMarkerArray($this->template, $this->markers);
        return $this;
    }
	
	
    /**
     * @return string
     */
    public function getOutput(): string
    {
        return $this->output;
    }
    
}


