# Phalcon Migrations Tests

Welcome to the Phalcon Migrations Testing Suite.

This folder contains all the tests for the Phalcon Migrations.

## Run tests

```bash
vendor/bin/codecept build
vendor/bin/codecept run
```

## Getting Started

Currently, you will need configured MySQL and PostgreSQL databases.

*  Copy .env.example file: 
```bash
cp -p tests/.env.example tests/.env
```
*  Edit credentials to the databases
*  Create manually databases and users (if needed) or use these commands matching default ones:

```bash
docker run --name mysql-container -e MYSQL_ROOT_PASSWORD=root -e MYSQL_DATABASE=phalcon-migrations -p 3306:3306 -d mysql:8.0 --default-authentication-plugin=mysql_native_password
docker run --name postgres-container -e POSTGRES_PASSWORD=postgres -e POSTGRES_DB=postgres -p 5432:5432 -d postgres:latest
docker exec -it postgres-container psql -U postgres -d postgres -c "CREATE SCHEMA migrations;"
```

## Help

Please report any issue if you find out bugs.
Thanks!

<3 Phalcon Framework Team
