# almalinuxのイメージを利用する
FROM almalinux/almalinux
LABEL maintainer=groupware

SHELL ["/bin/bash", "-o", "pipefail", "-c"]"

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

# PHP資源をコピー
COPY ./source /var/www/html

#PHP 8.1
# https://www.tsuda1.com/blog/2020/09/08/php%E3%82%A4%E3%83%B3%E3%82%B9%E3%83%88%E3%83%BC%E3%83%AB/
RUN dnf -y install https://rpms.remirepo.net/enterprise/remi-release-8.rpm
RUN dnf -y module install php:remi-8.1
RUN dnf -y install php php-cli php-fpm php-devel php-pear php-curl php-mysqlnd php-gd php-opcache php-zip php-intl php-common php-bcmath php-imagick php-xmlrpc php-json php-readline php-memcached php-redis php-mbstring php-apcu php-xml php-dom php-redis php-memcached php-memcache php-process
