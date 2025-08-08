FROM wordpress:php8.2-apache

# Install Git and other development tools
RUN apt-get update && apt-get install -y \
    git \
    vim \
    nano \
    curl \
    wget \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install Node.js and npm (optional, for frontend development)
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www/html

# Enable Apache mod_rewrite for WordPress permalinks
RUN a2enmod rewrite

# Add Git safe directory configuration to avoid ownership issues
RUN git config --global --add safe.directory /workspace
RUN git config --global --add safe.directory /var/www/html/wp-content/plugins/mobility-trailblazers