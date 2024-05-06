# Setting up the app
#### Versions
1. PHP `v7.4`
2. MYSQL `v8`

### Git
#### Pull latest changes from the repository
1. `git pull origin develop/master`
    * `master` for production
    * `develop` for development

### Laravel Application
#### Setting up environment
1. `composer install`
2. `composer dump-autoload`
3. should create/update `.env` file provide the necessary username and password
4. `php artisan migrate:fresh --seed`
5. `php artisan key:generate`
