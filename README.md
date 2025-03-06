
** UPDATE

*** From version 0.4.x to 0.5

- File repo_config.php was merged to app_config.php (key: 'repo')
Migration:
Take all your settings from "return array" in repo_config.php and move them to
return array in app_config.php, under the key 'repo' => []. (see: app_config.example)

- 'DEMO_MODE' option was moved from root conf array to the 'repo' key (now is: `[repo][DEMO_MODE]`)

