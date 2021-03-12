# Charm Framework

Welcome to the Charm - the magnetic framework!

The hassle-free php environment for the real world.

Now including: The magnet! Just entry and explore 
this entire world by starting with C::

## ToDo's
- Own routing engine based on phroute with serialization support
  (route filters can't be a closure right now when you cache the
   appstorage)

- PSR16 Caching engine: https://www.php-fig.org/psr/psr-16/
  Redis cache failover (if redis not present -> just get data manually)

- Option to renew remember me cookie on every page visit for
  another time period

- Cache: When removing entries, remove the associated tags as well,
  if existing
  
- Better Autocomplete, especially in PHPStorm. Create IDE helper file similar to
  barryvdh/laravel-ide-helper: https://github.com/barryvdh/laravel-ide-helper

- Validator:
  Validator for Charm. Validate request fields by data type (like
  php built-in validator or packages), with good error handling

  ```php

  $val = Charm::Validator()->validate([
  	'name' => 'string',
  	'age' => 'numeric'
  ]);

  if(!$val->isValid()) {
  	return Json::make([
  		'type' => 'error',
  		'errors' => $val->getErrors()
  	]);
  }

  getErrors() : array ['request_key' => 'value', 'age' => 'wrongFormat', 'name' => 'empty']
  ```
  
### PHP 8 Roadmap

- Move routes system to controller class + method attributes to make code cleaner + easier
  - go through classes + methods instead of routes files, add filter methods
  - documentation
- Code refactoring: Better / less docblocks, better argument names etc.
