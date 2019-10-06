FROM php:7.3-cli-alpine

RUN apk add --no-cache git

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php --install-dir=/usr/local/bin --filename=composer
RUN php -r "unlink('composer-setup.php');"

ADD composer.json .

RUN composer install

ADD pasteur pasteur

ADD pasteur.bin .

CMD ./pasteur.bin