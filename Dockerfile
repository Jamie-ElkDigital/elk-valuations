# Use the official PHP image with Apache
FROM php:8.2-apache

# Install System Dependencies & MySQL extension
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_mysql zip

# Enable Apache rewrite module
RUN a2enmod rewrite

# Install Node.js, Chrome, and dependencies for PDF generation
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

# Node dependencies for Puppeteer
COPY package*.json ./
RUN npm install

# Copy application files
COPY . /var/www/html/

# Expose port 8080
EXPOSE 8080

# Update Apache port
RUN sed -i 's/80/8080/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Pass environment variables to PHP
RUN echo "PassEnv SMTP_PASS SMTP_USER APP_COMMIT_SHA DB_PASS DB_HOST DB_NAME DB_USER" >> /etc/apache2/apache2.conf
