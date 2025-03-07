<?php



class XCoreWidget  {

    protected $name = '';
    protected $content = '';

    /**
     * @var XCore|object
     */
    protected $App;

    /**
     * @var XCoreView|object
     */
    protected $View;


    public function __construct(string $name = '')
    {
        $this->App = XCore::App();

        $this->name = preg_replace('/[^a-zA-Z0-9_-]+/', '_', strtolower($name));
        if (!$this->name) {
            throw new \InvalidArgumentException('XCoreWidget() parameter error - widget $name is empty!', 5685642);
        }

        $this->View = Loader::get(XCoreView::class, XCoreView::TYPE__WIDGET);
        $this->View->setTemplate('widget_'.$this->name);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
    
    
    /**
     * Returns result of Widget rendering operations. Usually ->content property,
     * possibly also some control / debug / info data
     * @return array
     */
    public function getOutput(): array
    {
        return ['content' => $this->content];
    }


    /**
     * Compile Widget from template, todo later: optional response data returned from actions called
     * @param array $response
     * @return XCoreWidget
     * @throws Exception
     */
    public function buildWidgetContent(array $response = []): XCoreWidget
    {
        $this->View->render();
        $this->content = $this->View->getOutput();
        return $this;
    }

}


