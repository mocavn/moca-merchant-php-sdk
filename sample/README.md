## How to run sample code:
We are using laravel framework for build sample so we follow some step below to run source code:
1. Open the terminal and go to root project: ```cd Php/sample```
1. Create environment: ```cp .env.example .env```
2. Update composer: ```composer install```
3. Run docker composer on route folder (/sample): ```docker-compose up -d```
4. Add encryption for Laravel: ```docker-compose exec app php artisan key:generate```
5. Opend on your browser with link: ```localhost:8888```

