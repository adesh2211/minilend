#!/bin/bash

mkdir ../public/swagger 
#php ../vendor/bin/swagger --bootstrap ./swagger-constants.php --output ../public/swagger ./swagger-v1.php ../app/Http/Controllers
php ../vendor/zircote/swagger-php/bin/swagger --bootstrap ./swagger-constants.php --output ../public/swagger/swagger.json --exclude vendor ../public/swagger ./swagger-v1.php ../app/Http/Controllers