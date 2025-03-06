<?php

/**
 * Viewhelper for menu generating
 * Example widget
 */
class XCoreViewhelperMenu extends XCoreViewhelper  {



    public function __construct()
    {
        parent::__construct();
        $this->View = Loader::get(XCoreView::class, XCoreView::TYPE__WIDGET);
    }


    /**
     * Compile the body to output
     *
     * @param string $type Menu type, if we have more than Main
     * @param array $config [wrapItem, glue, ]
     * @return string
     * @throws Exception
     */
    public function render(string $type = 'main', array $config = []): string
    {
        $pages = $this->App->getPagesConfig();
        $menuMainConfig = $this->App->getMenuMain();

        if (!$menuMainConfig || !$pages)
            return '';

        $this->View->setTemplate('menu');
        // collect iterations in array instead of building string, we may need them separately
        $contentMenuMainParts = [];
        foreach ($menuMainConfig as $menuItem) {
            $markers = [
                'TITLE' => $menuItem['pageId'] ? $pages[$menuItem['pageId']]['title'] : ($menuItem['title'] ?? 'NO TITLE!'),
                'HREF' => $menuItem['href'] ?? '?p='.$menuItem['pageId'],
                'LINK_CLASS' => $this->App->getPageObject()->getId() === $menuItem['pageId'] ? 'active' : '',
            ];
            $this->View->assignMultiple($markers);
            $this->View->render();
            $builtItem = $this->View->getOutput();
            if ($config['wrapItem'])
                $builtItem = Util::wrap($builtItem, $config['wrapItem']);
            $contentMenuMainParts[] = $builtItem;
        }

        return implode($config['glue'] ?? '', $contentMenuMainParts) ;
    }
}


