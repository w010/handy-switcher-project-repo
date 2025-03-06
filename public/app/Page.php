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
        $this->View->assignMultiple([
            'STATUS' => '<b class="level-success">UP</b>',  // may be disabled / maintenance / smth
            'REPO_VERSION' => REPO_VERSION,
            'REPO_APP_VERSION' => REPO_APP_VERSION,
            'INFO__READ_WITHOUT_KEY' => '',
            'INFO__DEMO_MODE' => '',
        ]);

        if ($this->App->getSettings()['repo']['read_without_key'])  {
            $this->View->assign('INFO__READ_WITHOUT_KEY', '<p>Option <i class="level-warn">read_without_key</i> is enabled!
				<br><small><i>(That means anybody can fetch the projects without key-authorization)</i></small></p>');
        }

        if ($this->App->getSettings()['repo']['DEMO_MODE'])  {
            $this->View->assign('INFO__DEMO_MODE', '<p>Option <i class="level-warn">DEMO_MODE</i> is enabled!
				<br><small><i>(Limited functionality - all critical and manipulating functionality is disabled, but <br>repo can fake some write-access actions output)</i></small></p>');
        }

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
            $this->App->msg('Unauthorized - permission level too low to go here', 'error');
            return 'ADMIN ALERT';
        }
        // can't use that on this level: / endless loop
        // $this->App->check_access_level('ADMIN');

        $markers = [
            'WIDGET_PROJECTS_LIST' => Loader::get(WidgetProjects::class)->render_projectsList(),
            'WIDGET_DATA_SUMMARY' => Loader::get(WidgetProjects::class)->render_dataSummary(),      // as last
        ];

        $this->View->assignMultiple($markers);
        $this->View->render();

        return $this->View->getOutput();
    }

}


