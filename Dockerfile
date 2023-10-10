# almalinuxのイメージを利用する
FROM almalinux/almalinux
LABEL maintainer=groupware

SHELL ["/bin/bash", "-o", "pipefail", "-c"]"

ENV HOSTNAME groupware

# 累積アップデートの実行
RUN dnf -y upgrade

# ベース、開発ツールパッケージ群インストール
RUN dnf -y groupinstall base "Development tools"

#ROOTパスワード
RUN echo "root:Password123" | chpasswd;

#sshのインストール
RUN dnf install -y openssh-server
RUN systemctl enable sshd

#Apacheのインストール
RUN dnf install -y httpd
RUN systemctl enable httpd

#MariaDBのインストール
RUN dnf install -y mariadb-server mariadb
RUN systemctl enable mariadb

# データベースとテーブルを作成するSQLスクリプトをコピーします
COPY ./mysql-init.sql /docker-entrypoint-initdb.d/

# MariaDBサーバー設定ファイルをコピーします
COPY ./mariadb-server.cnf /etc/my.cnf.d/

# MariaDBクライアント設定ファイルをコピーします
COPY ./client.cnf /etc/my.cnf.d/

#PHP 8.1
RUN dnf -y install https://rpms.remirepo.net/enterprise/remi-release-8.rpm
RUN dnf -y module install php:remi-8.1
RUN dnf -y install php php-cli php-fpm php-devel php-pear php-curl php-mysqlnd php-gd php-opcache php-zip php-intl php-common php-bcmath php-imagick php-xmlrpc php-json php-readline php-memcached php-redis php-mbstring php-apcu php-xml php-dom php-redis php-memcached php-memcache php-process

# Composerをインストール
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# その他インストール
RUN dnf -y install unzip git

# Composerのグローバルインストールパスを設定
ENV PATH="/root/.composer/vendor/bin:${PATH}"

# Laravelプロジェクトをインストール
RUN composer create-project --prefer-dist laravel/laravel /var/www/html

# アプリケーションの設定を有効にする
RUN cp /var/www/html/.env.example /var/www/html/.env
RUN php /var/www/html/artisan key:generate

# sourceコピー
COPY ./source /var/www/html/source

# ディレクトリ権限変更
RUN chown -R apache:apache /var/www/html

