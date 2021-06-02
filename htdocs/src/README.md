wolo.pl '.' studio
2021


# XCore

## Simple Standalone Micro App Engine

* What's that?
  
> It's a really simple MVC for kickstarting really tiny webapps for your experiments and local use, it's the answer for
> all these yours 10-liners raw php files, which has some simple task like generating some tests and logging results, 
> or like your simple old database-toolbox scripts for server migration, or that one tool you wrote to parse and edit some
> application's xml presets, to not have manually read and cut them to fix the program, which stopped to run because of
> one broken setup, and you really wanted that app, so you wrote. a windows, xml editor in php, on xampp. In a one ugly
> index.php.
> So - with XCore you can now do the same, but in a nice, clean way using modern homo sapiens stuff like read a template
> and replace a marker, or generate simple menu from files or config. With only a few files more.
> It probably sounds kinda stupid, but I find this solution very helpful and is perfect where you don't want to run a big
> "real world" framework for simple thing, but also not the ugly line-after-line index again. This is somewhere between.

* Who would even want to use some weird inventions like that?

> I really don't care if you don't. It's my tool for my needs that I have right at this moment, so this came out.


# Handy Switcher's Project Repository

## Host for exchanging Projects used in my browser ext/addon "(TYPO3) Handy Switcher".

* What does it do?

> This simple webapp is a feed for ajax calls from the addon's options panel. When you have many of Projects set there,
> each with a number of domains and urls for trackers, stages, wikis, notes, basically one day comes, when you will need
> to export, exchange, sync, backup them, or share with your team most recent set, then that brand new feature is a natural
> consequence.
> It stores json configs, it serves them to pull a list, look up, takeout to your config, compare differences, local merge,
> then you can push yours, (if your authorize key gave you a WRITE permission), review conflicting items, duplicates,
> comparing modify time, overwrite etc. All what you need as a team member to have urlsets up to date, all what you need
> as a team leader, to prepare and share url configs to each of your teams and their projects.
> If you authorize with ADMIN permission, you can check database status, integrity and do some necessary conversions
> of storing formats, see and get rid of duplicates and other stuff. 


* How to request Ajax with App/Api version check?

> Call ajax with additional custom header:
> ```header: {'XCore-MyApp-Version-Request': 'x.y.z'},```
> and then in XCore App, in request handling, you perform a check: 
> ```if (version_compare($_SERVER['HTTP_XCORE_MYAPP_VERSION_REQUEST'], APP_VERSION) > 0)```
> and do something about that, like inform user about limited functionality or so.


* How the app knows it is in Ajax mode? Called in browser shows the tech panel, called in Switcher options panel
it returns json. How?

> I call Ajax with additional header ```{'XCore-Request-Type': 'Ajax',}```
> Then on app init I look for it in $_SERVER.
> For testing and debugging you can force ajax mode adding ?ajax=1 to query/url.


* How to use a custom log in app code?

> Define output path in config: ```'log_myapp_path' => 'relative/path/to.log'```
> Then start it at app init like that: ```XCoreLog::set('mylogger', $this->settings['log_myapp_path']);```
> Use: ```XCoreLog... or Log::get('mylogger')->log('Something important happened! ');
