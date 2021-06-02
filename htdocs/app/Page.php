<?php




class Page extends XCorePage  {

    /**
     * @var RepositoryApp|XCore|object
     */
    protected $App;


    /**
     * @return string
     * @throws Exception
     */
    protected function buildPageContent_home(): string
    {
        $markers = [
            'STATUS' => '<b class="level-success">UP</b>',  // may be disabled / maintenance / smth
            'REPO_VERSION' => REPO_VERSION,
            'REPO_APP_VERSION' => REPO_APP_VERSION,
        ];
        
        $this->View->assignMultiple($markers);
        $this->View->render();

        return $this->View->getOutput();
    }
    
    
    
    /**
     * @return string
     * @throws Exception
     */
    protected function buildPageContent_maintenance(): string
    {
        if ($this->App->getAccessLevel() !== 'ADMIN')   {
            $this->App->msg('Unauthorized - invalid repo key', 'error');
            return 'ADMIN ALERT';
        }

        $markers = [
            'WIDGET_PROJECTS_LIST' => Loader::get(WidgetProjects::class)->render_projectsList(),
            'WIDGET_DATA_SUMMARY' => Loader::get(WidgetProjects::class)->render_dataSummary(),      // as last
        ];

        $this->View->assignMultiple($markers);
        $this->View->render();

        return $this->View->getOutput();
    }

}


