<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config/config.php';

// ユーザーのアイコン画像を表示
$user_id = $_SESSION['user_id']; // ユーザーIDをセッションから取得

// データベース接続などの初期化処理を行う

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["icon"])) {
    $user_id = $_SESSION['user_id']; // ユーザーIDをセッションから取得
    $upload_dir = "user_icons/"; // アイコン画像を保存するディレクトリ

    // ファイルの拡張子を取得
    $file_extension = pathinfo($_FILES["icon"]["name"], PATHINFO_EXTENSION);

    // ファイル名をユーザーIDに基づいて一意に生成
    $icon_filename = "user_" . $user_id . "." . $file_extension;

    // アップロードされたファイルを移動
    move_uploaded_file($_FILES["icon"]["tmp_name"], $upload_dir . $icon_filename);

    // データベースにアイコンの保存先パスを更新
    $update_query = "UPDATE users SET user_icon = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "si", $icon_filename, $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // 成功メッセージを表示またはリダイレクト
    header("Location: profile.php?user_id=$user_id");
    exit;
}
?>