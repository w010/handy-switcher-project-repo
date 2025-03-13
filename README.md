wolo '.' studio

2017-2025


# Handy Switcher's Project Repository

## What's that and what for?
This is an add-on feature for the **Handy Switcher** browser extension.

Remote Repository can store your Projects to help keep them up to date, exchange
setups with your team, sync between browsers (Chrome - Firefox), or just backup.

It comes especially helpful, when you work with dozens of projects, multiple stages
and a number of teammates. But you can take advantages of this feature also when working alone.


## Important - This Repository app

This app is a simple thing which helped me to manage my data at work. It's
nothing professional, but it does its job for me. It's more like proof of concept,
presentation of the idea which I decided to share. If you feel that's something
helpful and worth of using in your work, you can use that API to write something
better, ie. TYPO3 ext to host it more pro way. I still didn't find time for that.

Remember that it's an internal-use thing, made for private purposes, and don't
expect it's secure and solid - it only provides basic user input check.


    **USE ON YOUR OWN RISK, AND DON'T FORGET TO SET HTACCESS PASSWORD.**


## Resources

Example Repo online:
http://wolostudio.free.nf/handyswitcher/repoexample/

HANDY SWITCHER in the Chrome Web Store:
https://chromewebstore.google.com/detail/typo3-befeenv-handy-switc/ohemimdlihjdeacgbccdkafckackmcmn

Source code:
https://github.com/w010/chrome-typo3-switcher/



### UPDATE

*** From version 0.4.x to 0.5

- File repo_config.php was merged to app_config.php (key: 'repo')
Migration:
Take all your settings from "return array" in repo_config.php and move them to
return array in app_config.php, under the key 'repo' => []. (see: app_config.example)

- 'DEMO_MODE' option was moved from root conf array to the 'repo' key (now is: `[repo][DEMO_MODE]`)

