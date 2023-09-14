# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

---

## [3.2]
### Added
- Handy ViewExtension default functions
- `C::Router()->constructUrl(...)` method to create custom URLs with parameters
- Module specific config files can be overridden by the same config in the App (and its environment config file)
- Add custom config values via `C::Config()->set(...)` which are stored in the AppStorage for runtime only
  (but can also be stored in AppStorage cache depending on user's app)
- Model's `filterBasedOnRequest()` can now also filter fields with custom callback, check for `isnull` / `notnull`
- `C::Formatter()->removeTrailingZeros(...)` method which removes trailing zeros and dots

### Changed
- Improved security of DebugBar
- `C::Formatter()->translate(...)` now returns mixed data, including arrays
- `C::Router()->getCurrentUrl($with_query_params)` now has an optional parameter to decide if you want
  the current URL with or without the query parameters

### Fixed
- Small bug fixes due to wrong return types

---

## [3.1]
### Added
- CommandHelper class with handy commands to easily style input / output of console commands

### Changed
- ViewExtensions are now a lot easier to create and have a common base class

### Fixed
- Choice display in charm creator
- Funding comment which made problems with packagist

---

## [3.0]
### Added
- CharmCreator commands and handling
- Way more documentation
- Suiting, modern charm-wireframe
- Support for global `bob` command

### Changed
- Structure of many modules have been changed

---

## [2.0]
### Added
- Get all available cron jobs via `C::Crown()->getAllCronJobs()`
- Summary of database migrations as console output
- Console commands can now also be in subdirectories of `app/Jobs/Console`

### Changed
- Moved console jobs to according module
- Moved database migration to own `DatabaseMigrator` class
- Moved database code to own module outside the Kernel

### Removed
- Deprecated view Methods (ViewExtension)
  - `buildUrl()` (use `getUrl()` instead)
  - `formatMoney()` (use `formatNumber()` instead)
  - `str_replace()` (use twig's built-in `|replace()` filter instead)
- Deprecated methods
  - `C::Formatter()->formatMoney()` (use `C::Formatter()->formatNumber()` instead)
  - `C::Router()->buildUrl()` (use `C::Router()->getUrl()` instead)
  - `C::Database()->getRedisClient()` (use `C::Redis()->getClient()` instead)
- Deprecated `PathFinder` class
- Dependency on DbDumper, we will create a native one via `mysqldump` soon
- Deprecated `in_string()` function, use built-in `str_contains()` instead

---

## [1.0]
### Added
- First public initial commit of base system
