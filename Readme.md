# My Orders App

## Run dev env with sail 

1. Run `composer install`
2. Copy the environment file `cp .env.example .env`
3. Run `./vendor/bin/sail up`
4. In a new terminal, run `./vendor/bin/sail npm install`
5. Run `./vendor/bin/sail npm run dev`
6. In a new terminal, run `./vendor/bin/sail artisan migrate`
