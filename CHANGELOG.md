# CHANGELOG

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