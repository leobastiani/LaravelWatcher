# LaravelWatcher
Project to rank Laravel and Symfony repositories
![image](https://user-images.githubusercontent.com/8663061/57359152-e2fbba00-716e-11e9-8ccb-62601f4e6939.png)

## Installing project on Windows with docker
Create all php and mysql containers:
```batch
docker run -d -p 0.0.0.0:3306:3306 --name lw-mysql -e MYSQL_ROOT_PASSWORD=root -it mysql:5.7
docker run -d -p 0.0.0.0:3000:80 --link lw-mysql:mysql --name lw-php -v "PROJECT_PATCH":/var/www/html/ -w /var/www/html/ php:5-apache
```
Install apache dependencies:
```batch
docker exec -it lw-php a2enmod cgi
docker exec -it lw-php docker-php-ext-install mysqli pdo pdo_mysql mysql
```
Stop all containers and rerun with:
```batch
docker start lw-mysql
docker start -i lw-php
```
PHPMyAdmin is useful to debug:
```batch
docker run --rm -d --name myadmin --link lw-mysql:mysql -e PMA_HOST=mysql -e PMA_USER=root -e PMA_PASSWORD=root -p 0.0.0.0:8080:80 phpmyadmin/phpmyadmin
```
Create databases before start to use:
```batch
docker exec -i lw-mysql mysql -u root --password=root < sql/create_tables.sql
```
### Removing containers and finishing your project:
```batch
docker rm -v lw-mysql lw-php
```
