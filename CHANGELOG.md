# Changelog

The project follows Semantic Versioning (http://semver.org/)

## 3.1.0 - 2016-10-14
### Added
- Vrok\Hydrator\Strategy\DateTimeFormatterStrategy & NumberFormatterStrategy to
  support converting localized form inputs with date & numbers to database
  format and back

### Fixed
- bin\schema-update.sh executable flag
- renamed LICENSE and CHANGELOG to *.md

## 3.0.0 - 2016-10-13
### Added
- SlmQueue\JobProviderInterface for module classes to automatically inject job
  factories into the slmQueue JobManager

### Changed
- require PHP 7.0+
- require ZF3, implemented ZF3 compatibility
- updated dependencies

### Removed
- SlmQueue\AbstractJob - use a factory to inject dependencies instead

## 2.0.0 - 2016-09-02
### Removed
- currentUser() view helper & controller plugin

## 1.0.1 - 2016-09-01
### Added
- redirect on auth failure in XHR request

### Fixed
- do not enable all form elements when removing the loading animation

## 1.0.0 - 2016-08-29