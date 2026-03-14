# Use the official PHP image with Apache
FROM php:8.2-apache

# Install MySQL extension for PDO
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache rewrite module
RUN a2enmod rewrite

# Copy your application files to the container
COPY . /var/www/html/

# Set the working directory
WORKDIR /var/www/html/

# Expose port 8080 (Cloud Run default)
EXPOSE 8080

# Update Apache to listen on port 8080
RUN sed -i 's/80/8080/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf
