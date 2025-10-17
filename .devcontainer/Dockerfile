#FROM mcr.microsoft.com/devcontainers/php:8
#FROM mcr.microsoft.com/devcontainers/php:8.1-bookworm
#FROM mcr.microsoft.com/devcontainers/php:8.2-bookworm
#FROM mcr.microsoft.com/devcontainers/php:8.3-bookworm
# FROM mcr.microsoft.com/devcontainers/php:8.4-bookworm

# FROM php:8.5.0beta3-zts-trixie

# FROM php:8-cli
# FROM php:8-apache

FROM php:8.4-fpm

ENV DEBIAN_FRONTEND=noninteractive \
    DEBCONF_NOWARNINGS="yes" \
    TZ=UTC
#
RUN echo "Packages" && \
    apt-get update && apt-get install --no-install-recommends --assume-yes \
    # tools
    gnupg2 ca-certificates wget git nano jq zsh unzip p7zip \
    # ping + nslookup
    dnsutils \
    # hexdump tools
    xxd tcpdump tshark wireshark-common netcat-openbsd socat nmap \
    # debian tools
    locales \
    # tools
    iproute2 \
    # for PHP extension: intl
    libicu-dev \
    # for PHP extension: gettext
    gettext \
	# for PHP extension: gd
    libpng-dev \
    libjpeg-dev \
    libfreetype-dev \
    libwebp-dev \
    # for PHP extension: pdo_sqlite
    libsqlite3-dev \
    # for PHP extension: bz
    libbz2-dev \
    # Node & NPM
    nodejs npm \
    # Webserver
    nginx && \
    # Cleanup
    apt-get autoremove -y && \
    apt-get clean autoclean && \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Ensure en_US.UTF-8 locale is generated (prevents setlocale warnings)
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone && \
    sed -i 's/^# \?\(en_US.UTF-8 UTF-8\)/\1/' /etc/locale.gen && \
    locale-gen en_US.UTF-8 && \
    update-locale LANG=en_US.UTF-8 || true

# Possible values for ext-name:
# bcmath bz2 calendar ctype curl dba dl_test dom enchant exif ffi fileinfo filter
# ftp gd gettext gmp hash iconv intl json ldap mbstring mysqli odbc opcache
# pcntl pdo pdo_dblib pdo_firebird pdo_mysql pdo_odbc pdo_pgsql pdo_sqlite pgsql
# phar posix random readline reflection session shmop simplexml snmp soap sockets
# sodium spl standard sysvmsg sysvsem sysvshm tidy tokenizer xml xmlreader
# xmlwriter xsl zend_test zip
RUN echo "Install PHP extensions via PECL" && \
     # Install PECL extensions
    pecl channel-update pecl.php.net && \
    # Install Xdebug
    pecl install xdebug && \
    echo "Install PHP extensions via Docker" && \
    docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp && \
    docker-php-ext-install \
    gettext \
    intl \
    pdo \
    pdo_mysql \
    pdo_sqlite \
    gd \
    bz2

# Configure Xdebug default settings (disabled unless requested via XDEBUG_MODE)
RUN { \
    echo 'zend_extension=xdebug.so'; \
    echo 'xdebug.mode=coverage'; \
    echo 'xdebug.start_with_request=no'; \
    echo 'xdebug.client_host=host.docker.internal'; \
} > /usr/local/etc/php/conf.d/99-xdebug.ini

# Increase PHP memory limit for the Clansuite demo
RUN { \
    echo "; Increase memory limit for Clansuite demo"; \
    echo "memory_limit = 64G"; \
} > /usr/local/etc/php/conf.d/99-clansuite-memory.ini

RUN echo "Setup pnpm" && \
    npm install -g pnpm

#
#  8888888888P  .d8888b.  888    888
#        d88P  d88P  Y88b 888    888
#       d88P   Y88b.      888    888
#      d88P     "Y888b.   8888888888
#     d88P         "Y88b. 888    888
#    d88P            "888 888    888
#   d88P       Y88b  d88P 888    888
#  d8888888888  "Y8888P"  888    888
#

# Setup zsh, ohmyzsh, powerline fonts.
# Setup zsh plugins: autosuggestions, autocompletions, history search.

ENV TERM=xterm-256color \
    ZSH_THEME=agnoster

# Setup zsh for the root user
SHELL ["/bin/bash", "-o", "pipefail", "-c"]
RUN echo "ZSH" && \
    apt-get update && apt-get install --no-install-recommends --assume-yes \
    zsh fontconfig && \
    # Download and install fonts-powerline
    # An installation via apt-get is not possible for trixie,
	# because the package is only available in unstable(sid).
    # https://packages.debian.org/sid/fonts-powerline
	# https://api.ftp-master.debian.org/madison?package=fonts-powerline&table=debian
    FONTS_POWERLINE_VERSION=$(curl -s 'https://api.ftp-master.debian.org/madison?package=fonts-powerline&table=debian&s=unstable&f=json' | jq -r '.[0]["fonts-powerline"].unstable | keys[0]'); echo "Latest Version: \"$FONTS_POWERLINE_VERSION\"" && \
    FONTS_POWERLINE_URL="http://ftp.de.debian.org/debian/pool/main/p/powerline/fonts-powerline_${FONTS_POWERLINE_VERSION}_all.deb" && \
    curl -o fonts-powerline.deb -L ${FONTS_POWERLINE_URL} && \
    dpkg -i fonts-powerline.deb && \
    rm fonts-powerline.deb && \
    # Install Oh My Zsh
    curl -fsSL https://raw.githubusercontent.com/ohmyzsh/ohmyzsh/master/tools/install.sh | sh && \
    # Add Zsh plugins
    git clone --depth=1 --no-tags https://github.com/zsh-users/zsh-autosuggestions ${ZSH_CUSTOM:-~/.oh-my-zsh/custom}/plugins/zsh-autosuggestions && \
    git clone --depth=1 --no-tags https://github.com/zsh-users/zsh-completions ${ZSH_CUSTOM:=~/.oh-my-zsh/custom}/plugins/zsh-completions && \
    git clone --depth=1 --no-tags https://github.com/zsh-users/zsh-history-substring-search ${ZSH_CUSTOM:-~/.oh-my-zsh/custom}/plugins/zsh-history-substring-search && \
    # Update .zshrc
    sed -i "s/plugins=(git)/plugins=(git zsh-completions zsh-autosuggestions zsh-history-substring-search)/" /root/.zshrc && \
    sed -i "s/ZSH_THEME=\"agnoster\"/ZSH_THEME=\"robbyrussell\"/" /root/.zshrc && \
	sed -i "s/# DISABLE_AUTO_UPDATE=\"true\"/DISABLE_AUTO_UPDATE=\"true\"/" /root/.zshrc && \
	# Cleanup
	apt-get autoremove -y && \
    apt-get clean autoclean && \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

ENV LANG=en_US.UTF-8 LC_ALL=en_US.UTF-8 \
    # Switch back to dialog for any ad-hoc use of apt-get
    DEBIAN_FRONTEND=dialog \
    # Set the default shell to zsh after it is installed
    SHELL=/bin/zsh \
    # Allow Composer to run as root
    COMPOSER_ALLOW_SUPERUSER=1

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Update Composer
RUN composer self-update
