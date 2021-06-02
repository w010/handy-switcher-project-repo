<?php

namespace PHPSTORM_META {
    //metadata directives
    
    override(\XCoreLoader::get(), map([
        '' => '@'
    ]));

    override(\Loader::get(), map([
        '' => '@'
    ]));
    
    override(\XCore::App(), map([
        '' => '@App.php'
    ]));
}
