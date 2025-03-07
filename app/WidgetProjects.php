<?php

/**
 * Projects multiple admin widgets
 */
class WidgetProjects extends XCoreViewhelper {


    /**
     * View 
     * @var View|null 
     */
	protected $View = null;


    /**
     * Collected projects items
     * @var array
     */
	protected $projects = [];



    public function __construct()
    {
        parent::__construct();
        $this->View = Loader::get(View::class, View::TYPE__WIDGET);
    }


    /**
     * Compile the body to output
     *
     * @return string
     * @throws Exception
     */
    public function render(): string
    {
        // not used here, only individual methods
    }
    
    
    /**
     * Compile the body to output
     * 
     * @return string
     * @throws Exception
     */
    public function render_projectsList(): string
    {
        $this->projects = Util::getProjects( $this->App->getDataPath() );
        $this->View->setTemplate('widget_projectsList');
        
        $content = '';
        foreach ($this->projects as $project) {
            $this->View->assignMultiple([
                    'NAME' => $project->getName(),
                    'UUID' => $project->getUuid(),
                    'CLASS' => $project->getAdditionalData('uuid_duplicated') ? 'uuid_duplicated' : '',
                    'CONTEXTS' => $this->render_projectsList_contexts($project->getContexts()),
                    'LINKS' => $this->render_projectsList_contexts($project->getLinks()),
                    'LINK_DELETE' => $this->App->linkTo_uri(['action' => 'project_delete', 'uuid' => $project->getUuid()], '', true),
                    //'DETAILS' => 'details....',
                // todo: display modify time
            ]);
            $content .= $this->View->render()->getOutput();
        }

        return $content;
    }


        protected function render_projectsList_contexts(array $items): string
        {
            $content = '';
            foreach ($items as $item)   {
                $content .= '<div class="row">';
                $content .= '<span class="contextItem col-4">' . $item->getName() . '</span><span class="contextItem col-8">' . $item->getUrl() . '</span>';
                $content .= '</div>';
            }
            return $content;
        }
    
    
    
    /**
     * Compile the body to output
     * 
     * @return string
     * @throws Exception
     */
    public function render_dataSummary(): string
    {
        $this->projects = Util::getProjects( $this->App->getDataPath() );
        $this->View->setTemplate('widget_dataSummary');
        
        $repoDataSummary = Util::getRepoDataSummary();
        
        $this->View->assignMultiple([
                'NAME' => 'name',
                'COUNTER' => (string) count($this->projects),
                'META' => json_encode($repoDataSummary['repoDataMeta'], JSON_PRETTY_PRINT),
        ]);


        $audit = $repoDataSummary['audit'];

        $f_audit_status = function ($val){ switch ($val){
            case -1: return 'FAILED';
            case 1: return 'PASSED';
            default: return 'UNKNOWN';
        }};
        $f_audit_class = function ($val){ switch ($val){
            case -1: return 'failed';
            case 1: return 'passed';
            default: return 'unknown';
        }};


        $this->View->assign('LINK_RECHECK', $this->App->linkTo_uri(['action' => 'audit'], '', true));
        $this->View->assign('LINK_DOWNLOAD_ALL', $this->App->linkTo_uri(['action' => 'download_all'], '', true));
        
        $this->View->assignMultiple([
                'AUDIT__LAST_CHECK' => $audit['last_check'] > 0 ? date('d.m.Y H:i:s', $audit['last_check']) : 'NEVER',
                'AUDIT__LAST_CHECK__CLASS' => $f_audit_class( $audit['last_check'] ? 1 : -1),
                'AUDIT__LAST_CHECK__INFO' => $audit['last_check'] === -1 ? 'It\'s recommended to run repo audit, to detect known problems' :'',
        ]);

        $this->View->assignMultiple([
                'AUDIT__UUID_UNIQUENESS' => $f_audit_status( $audit['uuid_uniqueness'] ),
                'AUDIT__UUID_UNIQUENESS__CLASS' => $f_audit_class( $audit['uuid_uniqueness'] ),
                'AUDIT__UUID_UNIQUENESS__INFO' => $audit['uuid_uniqueness'] === -1 ? 'Duplicated uuids exists in database, review - look at projects list and clean up.' :'',
        ]);

        $this->View->assignMultiple([
                'AUDIT__MULTIPROJECT_JSON_FILES' => $f_audit_status( $audit['multiproject_json_files'] ),
                'AUDIT__MULTIPROJECT_JSON_FILES__CLASS' => $f_audit_class( $audit['multiproject_json_files'] ),
                'AUDIT__MULTIPROJECT_JSON_FILES__INFO' => $audit['multiproject_json_files'] === -1 ? 'Multi-project json file(s) exist.' :'',
                'AUDIT__MULTIPROJECT_JSON_FILES__LINK_CONVERT' => $audit['multiproject_json_files'] === -1 ? $this->App->linkTo('CONVERT JSON', ['action' => 'convert_json'], '', true, ['aTagParams' => 'class="btn"']) :'',
        ]);
        $content = $this->View->render()->getOutput();

        return $content;
    }
}


