# Charm Framework

Welcome to the Charm framework!

The hassle-free php environment for the real world.





## ToDo's
- Own routing engine based on phroute with serialization support
  (route filters can't be a closure right now when you cache the
   appstorage)

- PSR16 Caching engine: https://www.php-fig.org/psr/psr-16/
  Redis cache failover (if redis not present -> just get data manually)