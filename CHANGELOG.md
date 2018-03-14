# Changelog

The project follows Semantic Versioning (http://semver.org/)

## 5.0.0 - tbd
### Added
- Vrok\EntityLink - functionality to search, display and link to entities, replaces
  Vrok\Service\Owner in combination with the new vrok/ref-helper
### Changed
- moved the Vrok\Service\Exception classes to the more general Vrok\Exception
### Fixed
- use Router directly instead of url() controller plugin in UserManager
- unique index names, SQLite requires uniqueness across the database, not only table
- phpunit bootstrap to include the testconfig correctly and prime the SQLite memory db
### Removed
- Vrok\RandomLib\Source\Mcrypt - mcrypt is deprecated and removed with PHP 7.2
- Vrok\Service\Owner and related classes - use Vrok\EntityLink and vrok/ref-helper instead

## 4.2.0 - 2018-02-15
### Added
- Vrok\Stdlib\FileUtils - open files, delete files and directories(recursively)
- Vrok\Stdlib\StringUtils::slugify - create slugs from string input
- Vrok\View\Helper\TexEscape - to escape string to be used in LaTeX documents
- UserManager: Allow to logout from session only, keeping the remember-me cookie

### Fixed
- additional untranslated message showed up for invalid emails with invalid
  local name
- AuthorizationRedirectStrategy: logout from session only so remember-me is
  respected

## 4.1.0 - 2017-10-10
### Added
- Travis config
- code style config

### Fixed
- NumberFormat did not use preset decimals

### Changed
- updated ZF and other dependencies
- replace RandomLib with random_bytes() in Stdlib\Random
- applied code style fixer
- upgraded phpunit tests
- NumberFormatterStrategy::hydrate now rounds values

## 4.0.1 - 2017-02-02
### Fixed
- updated ZF and other dependencies

## 4.0.0 - 2017-02-02
### Added
- Vrok\Service\NotificationService that handles pushing/sending of notifications
  to the user

### Fixed
- Vrok\Stdlib\ErrorHandler::shutdownHandler did not log all fatal errors

### Changed
- reworked Vrok\Entity\Notification to persist the notification text variants
  that are created by formatters, @see Vrok\Notification\FormatterInterface
- Vrok\Entity\User now has properties allowing the user to configure if he wants
  email notifications and to enable HTTP push notifications
- moved getNotificationFilter & getNotificationRepository from UserManager to
  NotificationService
- DB schema update is required
- require PHP 7.1+

## 3.2.0 - 2016-12-29
### Added
- Vrok\Mvc\View\Http\ErrorLoggingStrategy that allows logging of exceptions/
  errors that occur within the application but are handled by the application
  itself instead of bubbleing up to the ErrorHandler
- Vrok\Entity\Notification that allows to store notifications that should be
  displayed to the user at the next occassion (login/next page/push notification)

## 3.1.1 - 2016-10-28
### Fixed
- Delegator config for ZF3
- stripTags filter for user displayName wasn't applied

## 3.1.0 - 2016-10-14
### Added
- Vrok\Hydrator\Strategy\DateTimeFormatterStrategy & NumberFormatterStrategy to
  support converting localized form inputs with date & numbers to database
  format and back

### Fixed
- bin\schema-update.sh executable flag
- renamed LICENSE and CHANGELOG to *.md
- RandomLib: custom sources compatibility with version 1.2.0

### Removed
- Vrok\RandomLib\Source\Php7 - included in new version 1.2.0

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