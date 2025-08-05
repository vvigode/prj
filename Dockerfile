FROM php:8.2-apache

# Установка системных зависимостей
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && rm -rf /var/lib/apt/lists/*

# Установка PHP расширений
RUN docker-php-ext-install pdo pdo_pgsql

# Установка Redis расширения
RUN pecl install redis && docker-php-ext-enable redis

# Включение необходимых модулей Apache
RUN a2enmod rewrite headers

# Копирование файлов проекта
COPY . /var/www/html/

# Права доступа
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Настройка PHP
RUN echo "max_execution_time = 300" >> /usr/local/etc/php/php.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/php.ini

EXPOSE 80

CMD ["apache2-foreground"]