<?php
// MySQLデータベースへの接続情報を設定します
$host = "localhost"; // ホスト名
$username = "root"; // MySQLユーザー名
$password = "Password123"; // MySQLパスワード
$database = "groupware_db"; // データベース名

// MySQLへの接続を行います
$conn = mysqli_connect($host, $username, $password, $database);

// 接続エラーがあればエラーメッセージを表示します
if (mysqli_connect_errno()) {
    die("MySQL接続エラー: " . mysqli_connect_error());
}
?>