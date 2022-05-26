# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

---

## [0.2.0]
### Added
- Get all available cron jobs via `C::Crown()->getAllCronJobs()`

### Changed
- Moved console jobs to according module

### Removed
- Deprecated view Methods (ViewExtension)
  - `buildUrl()` (use `getUrl()` instead)
  - `formatMoney()` (use `formatNumber()` instead)
- Deprecated methods
  - `C::Formatter()->formatMoney()` (use `C::Formatter()->formatNumber()` instead)


## [0.1.0]
### Added
- First public initial commit of base system