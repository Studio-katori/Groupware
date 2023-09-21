# Groupware環境構築手順

## 1.構築の実行
下記のコマンドを実行して自動構築！

> docker build . -t webapp:Groupware


## 2.イメージの実行
下記コマンドでイメージを実行!

> docker run --privileged -d -p 22:22 -p 80:80 -p 8080:8080 -p 443:443 --name webapp_alma webapp:Groupware /sbin/init


## 3.TeraTarmでログイン
ユーザ：root

PW：Password123


## 4.Mysql初期設定
下記コマンドで初期設定を実行!

> mysql_secure_installation

下記入力項目が出たら、「Password123」を入力

それ以外の項目については、空Enter

New password:

Re-enter new password:


## 5.必要なデータベース投入
> mysql -u root -p < /docker-entrypoint-initdb.d/mysql-init.sql


## 6.ブラウザアクセス
http://localhost

ユーザ：shun

PW:Password123


## 7.apache操作権限付与　※任意実行
下記コマンドでテキストエディターを実行!

> visudo

テキストの最下行に移動し、下記内容を入力して保存

apache ALL=(ALL) NOPASSWD: /usr/sbin/apachectl graceful

apache ALL=(ALL) NOPASSWD: /usr/bin/certbot

apache ALL=(ALL) NOPASSWD: /usr/bin/certbot delete --cert-name *

apache ALL=(ALL) NOPASSWD: /bin/rm


## 8.フォルダ権限変更　※任意実行
下記コマンドで権限変更を実行!

> chown apache:apache /etc/letsencrypt/live

> chown apache:apache /etc/httpd/conf.d


## 9.FTP接続とソースファイルの配置
ホスト:localhost

ユーザ：root

PW：Password123

ポート:22

ドキュメントルート:/var/www/html

