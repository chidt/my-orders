# My Orders App

## Run dev env with sail 

1. Run `composer install`
2. Copy the environment file `cp .env.example .env`
3. Run `./vendor/bin/sail up`
4. In a new terminal, run `./vendor/bin/sail npm install`
5. Run `./vendor/bin/sail npm run dev`
6. In a new terminal, run `./vendor/bin/sail artisan migrate`

## Database Migrations

- Migration files are located in `database/migrations/`
- To reset and re-run all migrations, use:

```sh
./vendor/bin/sail artisan migrate:fresh
```

- This will drop all tables and re-run all migration files.
- To reset and re-run all migrations and seed the database, use:

```sh
./vendor/bin/sail artisan migrate:fresh --seed
```

- This will drop all tables, re-run all migration files, and run the DatabaseSeeder
