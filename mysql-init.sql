-- データベースを作成
CREATE DATABASE groupware_db;

-- データベースを使用
USE groupware_db;

-- ユーザーテーブルを作成
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    user_role VARCHAR(255) NOT NULL,
    fullname VARCHAR(255) NOT NULL,
    bio TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    user_icon VARCHAR(255) DEFAULT 'default.png'
);

-- ユーザーを作成
INSERT INTO users (username, password, user_role, fullname, bio, user_icon) VALUES ('shun', 'Password123', 'admin', 'katori shun', 'テストメッセージ', 'default.png');

-- ドメイン情報テーブル作成
CREATE TABLE domains (
    id INT AUTO_INCREMENT PRIMARY KEY,
    serverName VARCHAR(255) NOT NULL,
    documentRoot VARCHAR(255) NOT NULL,
    sslEnabled VARCHAR(255) DEFAULT '0'
);

-- tweetsテーブル作成
CREATE TABLE tweets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    content TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    parent_tweet_id INT DEFAULT 0
);

