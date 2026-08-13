# Charm Framework

![Charm Framework](https://neoground.com/content/open-source/charm/cover-2x.webp)

[![GitHub release](https://img.shields.io/github/v/release/neoground/charm?sort=semver)](https://github.com/neoground/charm/releases)
[![License](https://img.shields.io/github/license/neoground/charm)](LICENSE.md)
[![Packagist Downloads](https://img.shields.io/packagist/dt/neoground/charm)](https://packagist.org/packages/neoground/charm)
[![GitHub issues](https://img.shields.io/github/issues/neoground/charm)](https://github.com/neoground/charm/issues)
[![GitHub stars](https://img.shields.io/github/stars/neoground/charm?style=social)](https://github.com/neoground/charm)

**A fast, modular PHP framework for ambitious web applications, APIs and SaaS.**  
Developed and used in production by [Neoground](https://neoground.com).

Charm is a full-stack PHP framework built around a straightforward idea: provide the infrastructure a serious
application repeatedly needs, while keeping the execution model compact, predictable and easy to reason about.

It combines a conventional MVC application structure with attribute-based routing, Twig, Eloquent, YAML configuration,
authentication, caching, queues, scheduling, events, storage, HTTP tooling and a modular application system.
Applications can stay small, grow into substantial monoliths, or be divided into explicit modules without changing the
underlying programming model.

Charm is deliberately **compact, not minimal**. It is intended for developers who want an integrated framework without
making framework machinery the dominant complexity of the application.

## What Charm is built for

Charm is a practical foundation for:

- SaaS and business applications
- APIs and application backends
- server-rendered websites with Twig
- hybrid applications with modern JavaScript frontends
- internal tools and operational platforms
- modular monoliths and reusable application modules

Neoground uses Charm as the foundation for its own web platforms, products and client systems. The framework therefore
prioritizes maintainability, operational usefulness and predictable application architecture over abstraction for its
own sake.

## Design principles

### Direct by default

Charm keeps common application operations close to the code that uses them. Routes can live directly on controller
methods, table definitions can live with models, configuration is accessed through a consistent API, and initialized
framework modules are available through `C::`.

```php
use Charm\Vivid\C;

$term = C::Request()->get('term');
$hostname = C::Config()->get('connections:database.hostname');
$queue = C::Queue();
```

The same module model extends to custom modules. Charm does not require deep service graphs or runtime
controller-parameter resolution for ordinary application code.

### Full-stack without unnecessary ceremony

The framework includes the recurring systems needed by production applications rather than leaving every project to
assemble them independently. That includes routing, requests and responses, views, database access, auth, queues,
scheduling, events, cache, storage, mail, validation, logging and development tooling.

The goal is not to hide PHP behind a large abstraction layer. The goal is to remove repetitive infrastructure while
keeping application flow visible.

### Modular when the application needs it

A Charm application can remain one coherent codebase or be split into modules with their own namespaces, configuration
and application structure. Modules are registered through `modules.yaml` and participate in the same initialization and
runtime model as the framework itself.

This makes it possible to structure a larger application by domain without prematurely turning it into a distributed
system.

### Optimized initialization

Charm initializes its modules into a shared application context and uses `AppStorage` for frequently accessed
lightweight runtime data such as configuration and routing metadata.

For production environments, stable initialization data can be generated into an AppStorage cache. This avoids repeating
work that does not need to be rediscovered on every request and keeps framework bootstrapping lean.

## Core capabilities

| Area                      | Included                                                                                   |
|---------------------------|--------------------------------------------------------------------------------------------|
| **Application structure** | MVC foundation, controllers and models with subdirectory support, modular applications     |
| **Routing**               | Attribute-based routes, named routes, groups, filters and module routes                    |
| **Views**                 | Twig templates, layouts, includes, framework helpers and custom responses                  |
| **Database**              | Eloquent ORM, multiple SQL database engines, model-based table definitions and migrations  |
| **Configuration**         | YAML configuration, environment-specific overrides, translations and cached initialization |
| **Modules**               | Built-in and custom modules exposed through the same `C::Module()` interface               |
| **Authentication**        | Web authentication, guards, sessions and token-based API authentication                    |
| **Background work**       | Queue system and scheduled jobs                                                            |
| **Application services**  | Events, caching, Redis, HTTP client, mail, validation, logging and file storage            |
| **Developer tooling**     | Bob CLI, debug mode, exception handling, Debug Bar and Kint integration                    |

Charm uses established libraries where they solve a problem well—including Twig, Eloquent, Symfony components,
Flysystem, Guzzle and Monolog—while providing a coherent Charm application layer around them.

## A small Charm application

A controller route can be defined directly where its behavior lives:

```php
namespace App\Controllers;

use Charm\Vivid\C;
use Charm\Vivid\Controller;
use Charm\Vivid\Kernel\Output\View;
use Charm\Vivid\Router\Attributes\Route;

class WelcomeController extends Controller
{
    #[Route('GET', '/hello', 'welcome.hello')]
    public function hello()
    {
        $name = C::Request()->get('name', 'World');

        return View::make('welcome')->with([
            'name' => $name,
        ]);
    }
}
```

The corresponding Twig view can stay equally direct:

```twig
{% extends "_base/main.twig" %}

{% block content %}
    <h1>Hello {{ name }}</h1>
    <p>{{ c('Config').get('user:site_name') }}</p>
{% endblock %}
```

Models are based on Eloquent and can keep their basic table definition close to the model when that is the clearest
representation of the application:

```php
namespace App\Models;

use Charm\Vivid\Model;
use Illuminate\Database\Schema\Blueprint;

class Project extends Model
{
    protected $table = 'projects';

    protected $fillable = [
        'name',
        'status',
    ];

    public static function getTableStructure(): \Closure
    {
        return function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('status')->default('active');
            $table->timestamps();
        };
    }
}
```

Traditional migrations remain available when a project benefits from a separate migration history.

## Requirements

- PHP **8.4 or later**
- Composer
- required PHP extensions:
    - JSON
    - Fileinfo
    - OpenSSL
- optional, depending on the application:
    - MariaDB, MySQL, SQLite, PostgreSQL or SQL Server
    - Redis
    - `ext-pcntl` for the built-in cron daemon

Charm is developed for current PHP versions and makes use of modern PHP language features.

## Getting started

The recommended way to create a Charm application is with **Bob**, the Charm command-line toolkit, and the official
**Charm Wireframe** project template.

### 1. Install Bob

```bash
curl -fSsL -o bob https://raw.githubusercontent.com/neoground/charm-toolkit/main/bob \
    && chmod +x bob
sudo mv bob /usr/local/bin/bob
```

See the [Charm Toolkit repository](https://github.com/neoground/charm-toolkit) for Bob documentation.

### 2. Create a project

```bash
bob new AcmePortal
```

Bob creates a new application from [charm-wireframe](https://github.com/neoground/charm-wireframe) and guides you
through the initial setup.

### 3. Configure the application

Global configuration lives in:

```text
app/Config/
```

Environment-specific overrides live below:

```text
app/Config/Environments/<Environment>/
```

The active environment is selected through `app/app.env`.

Typical configuration files include:

```text
main.yaml
connections.yaml
modules.yaml
```

### 4. Run the development server

```bash
bob serve
```

The Wireframe also includes example configuration for Nginx and Apache deployments.

## Application structure

A conventional Charm project keeps the important parts easy to locate:

```text
app/
├── Config/
├── Controllers/
├── Models/
├── Views/
└── ...
```

As the project grows, controllers and models can be grouped into subdirectories or application domains can be promoted
into dedicated Charm modules. The framework does not require a change of architectural style simply because the codebase
becomes larger.

## Modules and `C::`

Framework services and loaded application modules share one access pattern:

```php
C::Config();
C::Request();
C::Cache();
C::Queue();
C::Storage();
```

A custom module follows the same model:

```php
C::Billing()->createInvoice($order);
```

This gives applications a small and consistent surface area while keeping modules independently structured and reusable.

`Charm::` is available as the long-form alias of `C::`.

## Production use

Charm is maintained alongside the systems Neoground builds and operates, and its architecture is shaped by production
requirements.

Production deployments can generate the AppStorage cache so stable initialization data is loaded directly rather than
rebuilt on each request:

```bash
php bob.php appstorage:generate
```

The framework also provides integrated logging, caching, queues, scheduled tasks, storage abstraction and debug tooling
for the operational lifecycle around an application.

## Charm 4

Charm 4 is a substantial architectural revision and introduces breaking changes from the 3.x line. Its purpose is to
establish a cleaner long-term foundation for Neoground's applications and for external projects building on Charm.

Charm is already used in production, but projects tracking the development line should expect APIs to continue evolving
until the 4.0 stable release.

## Documentation

- [Charm documentation](https://neoground.com/en/docs/charm)
- [Installation guide](https://neoground.com/en/docs/charm/start/installation)
- [Charm Wireframe](https://github.com/neoground/charm-wireframe)
- [Bob / Charm Toolkit](https://github.com/neoground/charm-toolkit)

The documentation is being revised alongside Charm 4. Some sections may still describe earlier framework versions or
terminology.

## Contributing

Issues, bug reports and focused pull requests are welcome through GitHub.

If you are planning a larger contribution or architectural change, opening an issue first helps keep the work aligned
with the framework's direction.

## License

Charm is open source software released under the [MIT License](LICENSE.md).

## About Neoground

Charm is developed and maintained by [Neoground GmbH](https://neoground.com), a German technology company working across
strategic technology advisory, software systems and digital infrastructure.

We build Charm because we use it: as a durable, understandable foundation for software that has to work beyond the
prototype stage.