# CHANGELOG

## v1.4.8

* Fix bug in User::updateProfileSetting

## v1.4.7

* Correct a type conversion error in BuildsErrorResponses::isDebugMode
* Detect when request is running in console mode and let artisan process and display any Exception
* Account for some strange changes coming down the pipe in Scheduling\Scheduler; was resulting in a binding bug 
* Explicitly load config/app in container bootstrap

## v1.4.6

* Add composer.stub for Bamboo plugin generation

## v1.4.5

* Change Router::$resourcesActions from static property to instance property Router::$resourceAction; this way, a plugin developer can set global resource actions to his liking

## v1.4.4

* Fix metadata bug

## v1.4.3

* Added post-create-project script for generating bootstrap and plugin subclass for Bamboo

## v1.4.2

* Fix bugs in our MailServiceProvider implementation

## v1.4.1

* Version bump to signify bugfixes that I thought I had successfully force-overridden

## v1.4.0

* Upgraded Laravel dependencies from 5.3 to 5.4
* Made WP test installation file executable

## v1.3.16

* Enable built-in cache and queue commands; cache:clear command has been tested; queue:* commands have not been, and probably do not work yet

## v1.3.15

* Fix bug in SimpleProfileSection: was missing import statement for CanBeSavedToProfile

## v1.3.14

* Simplify access to user email with accessor/mutator for User::$email
* Stop passing Route object to controller invocations, and instead, start using parameter names to try to pull bounded objects from Container, which is fucking awesome

## v1.3.13

* Smarter default error handling: clearer messaging, and different output for debugging mode vs. standard users

## v1.3.12

* Implement super basic UrlGenerator; still need to implement some of the contract, but it's enough for now just to have URL::current(), URL::to(), and URL::secure()

## v1.3.11

* Fix travis CI by using vendor-loaded phpunit instead of global

## v1.3.10

* Fixed: the localized messages file for validation errors was missing
* Added generators for using the CLI to quickly create new CPTs and Console commands
* Added Concern called CanRetryThings to introduce a simple API for calling some arbitrary Closure a certain number of times before failing gracefully to some other system
* Removed dependency between default exception Handler and Router by moving error response building to a Concern called BuildsErrorResponses
* Added alias for "post_name" field to Post model; now you can just call $post->name
* Fixed: Logging, so that we can see what the hell is going on
* Autodetect Bugsnag logger, and if found, use its multi-logger for logging errors to both Bugsnag and the local log file
* Fixed: Invoking artisan commands was requiring some strange syntax for argument passing; this has now been normalized, and these commands now run almost identically to the way they do in a standard Laravel or Lumen context—from argument processing, to color-coding

## v1.3.9

* Add Plugin::setCLICommand for modifying default command namespace

## v1.3.8

* Fix the Profile API

## v1.3.7

* Always load Gravatar via HTTPs

## v1.3.6

* Put storage path inside WordPress content path

## v1.3.5

* Bug fix: calling function that doesn't exist: Plugin::config(), should be Plugin::$config->get()

## v1.3.4

* Under some circumstances, plugins are activated before global $wp_rewrite has been initialized; in these instances, calling flush_rewrite_rules() results in a fatal error; stop doing that

## v1.3.3

* Trap theme-dir errors in test runner

## v1.3.2

* Add package illuminate/mail
* Add proper JSON-encoded output to our custom Exception Handler
* Add support for Exception Handling registration to base Plugin
* Remove old pattern of loading Validator translation from plugin project, favoring instead loading the default from the validation package
* Rough-in pattern for customizable Comment Types
* New Shortcode baseclass: for creating and registering object-oriented shortcodes
* New FieldGroup baseclass: for creating and registering object-oriented ACF field groups (need to spec fluent builder API)
* Add support for custom rewrite rules to Post class
* Add API to base User model for creating and validating stored, expiring private links
* Fix bug in Router::api and Router::resource—wasn't returning RouteGroup

## v1.3.1

* Remove incomplete test case

## v1.3.0

* Fixed a number of previously undiscovered issues with the use of global function helpers inside of core Providers
* Flushed some more dead code from early prototypes
* Added Scout dependency and setup provider and aliases to be available to all Plugins
* Renamed `CustomTaxonomy` to `Taxonomy`
* Refactored `CustomPostType` into `Post` class, reducing complexity
* Created `CustomSchema` interface to unify registration of Taxonomy and PostType subclasses
* Modified `Plugin::register` to accept both `ServiceProvider` classes as well as `CustomSchema` implementers
* Added support for running Commands through Artisan Console via WP-CLI
* Now using `plugin_basename($this->mainFile)` to generate default router namespace
* Global `plugin($name)` function can be used to load any bootstrapped instance of a Bamboo Plugin
* Setup unit testing and Travis-CI to guarantee future build stability
* Added `FatPanda\Illuminate\WordPress\TestCase` class to ease some plugin unit testing tasks, e.g., REST API endpoint testing
* Setup default Exception Handler: `FatPanda\Illuminate\Support\Exceptions\Handler`; still needs some work, e.g., detecting type of request and reporting the error accordingly
* For clarity, renamed `Plugin::setRouterNamespace` and `Plugin::setRouterVersion` to `Plugin::setRestNamespace` and `Plugin::setRestVersion`, respectively

## v1.2.2

* Fixed another angry namespacing issue
* I had been placing `$plugin` in a global namespace; didn't think that through, and fixed it

## v1.2.1

* Decided not to be such a dick about the namespaces

## v1.2.0

* Changed the way Plugin subclasses are bootstrapped
* Fix a bug in router version number detection and assignment

## v1.1.1

* Added support for token substitution to Router::rewrite, just like Router::get, Router::post, etc.
* Add session support to the Plugin baseclass, including a SessionHandler that stores session data in WordPress transients

## v1.1.0

* Not promoted

## v1.0.6

* Fixed bugs in CustomPostType
* Introduced CustomTaxonomy
* Established naming convention for Plugin hooks: on{ActionName} for actions, and filter{FilterName} for filters, with support for @priority codedoc
* Deprecated Bridge::enableEloquent in favor of Bridge::bootEloquent (matches Laravel method name for the same)
* Refactored the way database connections are created, hopefully ensuring that there are never more than two opened for one WordPress request

## v1.0.5

* Refactored Plugin baseclass out of scaffolding project and into this one

## v1.0.4

* Bumping the verison number to test auto-updating

## v1.0.3

* Changed package name from `fatpanda/illuminate-wordpress` to `withfatpanda/illuminate-wordpress`
* Added CHANGELOG.md