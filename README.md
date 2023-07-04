## About portapporta

Portapporta is a Laravel application. 

Laravel is a web application framework with expressive, elegant syntax. 

## 1 Getting Started

### 1.1 Prerequisites

To develop this project make sure you have PHP version 8.0.x installed and running on your machine.

### 1.2 Installation

To install the project you will need to:

- clone the repository

`git clone git@github.com:webmappsrl/portapporta.git`

- install the dependencies

`composer install`

- create the local database

`createdb [database_name]`

- configure the postgres access
    - `sudo -u postgres psql`
    - `create user 'myuser' with encrypted password 'mypass';`
    - `grant all privileges on database 'database_name' to 'myuser';`

- configure the project environment:
    - `cp .env.example .env`
    - set the local database configuration (the `DB_*` variables)
- run the migrations

`php artisan migrate`

If you already have a database backup file, you can run the following command:
```sh
 createdb -U postgres [database_name] && psql -U postgres -d [database_name] -f path/to/your/file.sql
```

- run the project in a local environment

`php artisan serve`

## 2 Development

All the development work must be done in the develop branch following the GitFlow Workflow

## 3 Built With
- [Laravel](https://laravel.com)
- [Laravel Nova](https://nova.laravel.com)

##  License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details


## CHANGE LOG

Version 22.04.05: first version
