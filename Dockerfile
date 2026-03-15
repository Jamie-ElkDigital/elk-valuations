# Use the official PHP image with Apache
FROM php:8.2-apache

# Install MySQL extension for PDO
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache rewrite module
RUN a2enmod rewrite

# Install Node.js and Puppeteer dependencies
RUN apt-get update && apt-get install -y \
    gnupg \
    wget \
    curl \
    ca-certificates \
    fonts-liberation \
    libasound2 \
    libatk-bridge2.0-0 \
    libatk1.0-0 \
    libc6 \
    libcairo2 \
    libcups2 \
    libdbus-1-3 \
    libexpat1 \
    libfontconfig1 \
    libgbm1 \
    libgcc1 \
    libgconf-2-4 \
    libgdk-pixbuf2.0-0 \
    libglib2.0-0 \
    libgtk-3-0 \
    libnspr4 \
    libnss3 \
    libpango-1.0-0 \
    libpangocairo-1.0-0 \
    libstdc++6 \
    libx11-6 \
    libx11-xcb1 \
    libxcb1 \
    libxcomposite1 \
    libxcursor1 \
    libxdamage1 \
    libxext6 \
    libxfixes3 \
    libxi6 \
    libxrandr2 \
    libxrender1 \
    libxss1 \
    libxtst6 \
    lsb-release \
    xdg-utils \
    --no-install-recommends \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# Set the working directory
WORKDIR /var/www/html/

# Copy your application files to the container
COPY . /var/www/html/

# Install Node dependencies
RUN npm install

# Expose port 8080 (Cloud Run default)
EXPOSE 8080

# Update Apache to listen on port 8080
RUN sed -i 's/80/8080/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf
