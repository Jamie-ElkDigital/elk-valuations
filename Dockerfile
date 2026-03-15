# Use the official PHP image with Apache
FROM php:8.2-apache

# Install MySQL extension for PDO
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache rewrite module
RUN a2enmod rewrite

# Install Node.js, Chrome, and dependencies
# We install google-chrome-stable because it automatically handles all the complex 'lib' dependencies
RUN apt-get update && apt-get install -y wget gnupg curl ca-certificates --no-install-recommends \
    && curl -fsSL https://dl-ssl.google.com/linux/linux_signing_key.pub | gpg --dearmor -o /usr/share/keyrings/google-chrome.gpg \
    && echo "deb [arch=amd64 signed-by=/usr/share/keyrings/google-chrome.gpg] http://dl.google.com/linux/chrome/deb/ stable main" > /etc/apt/sources.list.d/google-chrome.list \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get update && apt-get install -y \
    nodejs \
    google-chrome-stable \
    fonts-liberation \
    --no-install-recommends \
    && rm -rf /var/lib/apt/lists/*

# Set the working directory
WORKDIR /var/www/html/

# Copy package files first to leverage Docker cache
COPY package*.json ./

# Install Node dependencies
RUN npm install

# Copy your application files to the container
COPY . /var/www/html/

# Expose port 8080 (Cloud Run default)
EXPOSE 8080

# Update Apache to listen on port 8080
RUN sed -i 's/80/8080/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf
