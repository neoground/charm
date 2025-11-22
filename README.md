# Charm Framework

![Header Banner](https://neoground.com/data/projects/charm/assets/banner.jpg)

---

![GitHub release (latest SemVer)](https://img.shields.io/github/v/release/neoground/charm?sort=semver)
![GitHub license](https://img.shields.io/github/license/neoground/charm)
![Packagist Downloads](https://img.shields.io/packagist/dt/neoground/charm)
![GitHub issues](https://img.shields.io/github/issues/neoground/charm)
![GitHub stars](https://img.shields.io/github/stars/neoground/charm?style=social)

## A performant, modern PHP Framework

Version 4 of this framework is a rewrite with breaking changes. 
We adapt modern practices and optimize the codebase for usage with PHP 8.5.
While doing so, we also modernize and professionalize the framework overall to be a future-proof foundation.

Below you find the legacy Readme.

## 🚀 A Galactic Adventure in PHP Web Development

In a galaxy far, far away, there was a PHP web framework that 
changed the way developers explored the vast universe of web development.
The Charm Framework brings balance to the Force, combining power, performance, 
and a touch of artistry to create an extraordinary tool for your 
intergalactic adventures in web development.

Programming is not just about writing lines of code; it's an art form that 
requires creativity, passion, and the courage to explore uncharted territories. 
With the Charm Framework, you'll embark on an epic journey through the cosmos of 
web development, discovering new ways to create and innovate while having fun along the way.

We've infused the Charm Framework with the spirit of Sci-Fi, timeless tales of 
heroes and villains, triumph and tragedy, that has captured the hearts of millions across the galaxy. 
Our goal is to bring the magic and excitement of this beloved saga to the world of PHP web development, 
inspiring you to embrace your inner Jedi and become a true master of your craft.

As you delve into the Charm Framework, 
you'll encounter a rich and diverse ecosystem of tools and features designed to help you build powerful, 
high-performance web applications that can stand the test of time. With the wisdom of Yoda, 
the tenacity of Luke Skywalker, and the grace of Princess Leia, 
the Charm Framework will guide you on your path to web development mastery.

So strap in, grab your lightsaber, and prepare to embark on an unforgettable 
journey through the world of PHP web development. Together, we'll conquer even the most daunting 
coding challenges, unlocking the full potential of the Charm Framework and paving the way 
for a brighter future in the galaxy of web development.

May the Force be with you, always.


## 🌐 About

Charm is a blazing-fast PHP framework optimized for building business web 
applications and APIs. With its lightweight design and fast router, 
Charm is optimized for high-performance, making it a powerful tool for 
developers who need to build applications that can handle heavy loads with ease.

Charm provides its own Twig views, but it can also easily be used with a 
single-page JS app. The framework is built using a combination of popular libraries 
and slim, optimized solutions, including the Eloquent ORM for database tasks, 
Redis caching for high performance, and a built-in user and auth system.

Charm also includes many convenience functionalities without overloading it, 
such as Cron and Queue systems for scheduling tasks, and a great debugging help with Kint,
Whoops, and Debugbar. All app init data can be stored in a single file to increase boot 
up even more, and config files are in YAML. Modules and own packages based on this 
framework are easily possible and integrated into other projects in seconds.

But Charm is more than just a fast and powerful PHP framework. We've designed our 
documentation to be engaging and enjoyable to read. 
We believe that learning a new technology 
should be a fun and rewarding experience, and our documentation reflects that 
commitment to making the learning process as enjoyable as possible.

Whether you're a seasoned developer or just getting started with PHP, 
Charm is the perfect tool for building high-performance web applications and APIs. 
With its slim, optimized design and powerful features, 
Charm is the ideal choice for developers who want to build fast, 
scalable, and maintainable applications with ease.


##  🎉 Getting Started

Please see our [official documentation](https://neoground.com/docs/charm/index)
and its included [Getting Started Guide](https://neoground.com/docs/charm/start.installation).

### Requirements: Fuel for Your Galactic Adventure

To ensure a smooth journey with the Charm Framework, make sure your system meets the following requirements:

- PHP 8.3 or later (ideally with Redis module)
  - PHP installation needs basic extensions. Make sure you don't have "php-psr" installed, since this
    conflicts with Monolog's Logging engine.
- Composer
- Depending on your app:
    - Database: MariaDB, MySQL, SQLite, PostgreSQL or SQL Server
    - Redis

### Installation: As Easy as the Kessel Run

To install the Charm Framework, you first need to install Bob toolkit on your machine.

In a galaxy not so far away, Bob (short for Binary Operations Butler) was created
to serve as the ultimate command-line companion for Charm Framework developers.

Run the following command to install Bob:

```bash
curl -fSsL -o bob https://raw.githubusercontent.com/neoground/charm-toolkit/main/bob && chmod +x bob
sudo mv bob /usr/local/bin/bob
```

For more information on this, see the [Bob documentation](https://github.com/neoground/charm-toolkit).

Once installed, run the following command to create a new project:

```bash
bob new GalacticArchive
```

This command will generate a new project called `GalacticArchive` based on the [charm-wireframe](https://github.com/neoground/charm-wireframe)
template and put it in the new created directory `GalacticArchive`. The wireframe serves as
a foundation for all Charm Framework applications, empowering you to build incredible
web applications in the universe.

The setup assistant then guides you through the process.

### Configuration: Fine-Tuning the Hyperdrive

Now that your project is set up, you can check and adjust the global configuration
by navigating to the `app/Config` directory. For environment-specific settings,
explore the `app/Config/Environments/Local` directory.

The active environment is determined by the `app/app.env` file, which contains
the name of the environment in use, e.g. `ENVIRONMENT=Prod`. The auto setup process takes care of this for you.

### Web Server Setup: Powering Up the Millennium Falcon

To get your web server up and running, you might need to adjust its configuration.
The charm-wireframe comes with a sample `.htaccess` and `nginx.conf` file to help you get started.

For a local development server, simply type `bob serve` in the project directory, and you'll be good to go!

May the Force guide you, young Jedi!


## 🚧 Beta Notice

Please note that Charm is currently in beta.
We are hard at work on version 4.0, which will be the first full stable release. 

Starting at version 3.7, charm is in a stable beta. We didn't experience bigger bugs in the
last few months, and it runs very well on our production apps. Huge, breaking changes are unlikely from now on.

## ☕ Support Charm's Development

We're committed to making Charm the best PHP framework out there, 
and we could use your help! By becoming a sponsor or making a donation, 
you can help us accelerate the development process and bring Charm 4.0 to life. 
Your support allows us to dedicate more time and resources to the project, 
ensuring that Charm continues to evolve and improve.

To make a donation or become a sponsor, check out our [official documentation](https://neoground.com/docs/charm/index).
Thank you for your support and for helping us make Charm even better!
