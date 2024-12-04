Requirements: 
* Docker https://docs.docker.com/engine/install/

1. `docker-compose up -d`

Try to connect to localhost. If you get the error: `Fatal error: Uncaught Error: Call to undefined function mysqli_connect() in /var/www/html/index.php:3 Stack trace: #0 {main} thrown in /var/www/html/index.php on line 3` Go to Step 3.

Otherwise skip to Step 6.

2. `docker container ls`

3. Find the container that's running `www`

4. `docker exec -it containername /bin/bash`

5. `docker-php-ext-install mysqli && docker-php-ext-enable mysqli && apachectl restart`

6. Browser: `localhost:8001`

7. Login to PHP My Admin  with: `php_docker` `password`

8. Copy contents of ./db/questions_table.sql, click on php_docker database and paste it into PHP My Admin SQL interface. Same with users_table.sql

9. Browser: `localhost/hash_passwords,php`

10. Browser: `localhost`
