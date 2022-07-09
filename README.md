
# Symfony docker compose
An easy way to set up your Symfony application using Docker and docker-compose. This will set up a development environment with nginx, php7.4-fpm, and mysql.


![App Screenshot](https://i.imgur.com/JbX2RnS.png)

## Usage

To get started, make sure you have [Docker](https://docs.docker.com/docker-for-mac/install/) and [Docker Compose](https://docs.docker.com/compose/install/) installed on your system, and then clone this repository.

## Run Locally

Clone the project
```bash
  git clone https://github.com/yasirrose/symfony-docker.git
```

Go to the project directory

```bash
  cd symfony-docker
```
Get and build docker images
```bash
  docker-compose pull
  docker-compose build
```

Install Dependcies

```bash
  cd src
  docker-compose run --rm composer update
```
Apply Database Migrations

```bash
  docker-compose run php bin/console doctrine:migrations:migrate
```

Run the Project
```bash
  docker-compose up -d site
```
Now open the browser and you will see the symphony default page.



## Tech Stack

**Language:** Php

**Framework:** Symfony

**Database:** MySQL

**Webserver:** Nginx

