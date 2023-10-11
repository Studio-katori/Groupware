<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config/config.php';

// ユーザーIDを取得
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;

// データベースから保存先パスを取得
$query = "SELECT user_icon FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $user_icon);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if ($user_icon) {
    $icon_path = "user_icons/" . $user_icon; // アイコンのファイルパス
    $user_icon_html = "<img src='$icon_path' alt='User Icon' width='100' height='100'>";
} else {
    $user_icon_html = "アイコンが設定されていません。";
}

// ユーザーIDを取得
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;

if (!$user_id) {
    // ユーザーIDが不正な場合のエラー処理
    $profile_html = "不正なユーザーIDです。";
} else {
    // ユーザーの情報をデータベースから取得
    $query = "SELECT * FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if (!$user) {
        // ユーザーが存在しない場合のエラー処理
        $profile_html = "指定されたユーザーは存在しません。";
    } else {
        // ユーザーの情報を表示
        $profile_fullname = $user['fullname'];

        // 自己紹介文を表示
        if ($user['bio']) {
            $profile_bio = $user['bio'];
        } else {
            $profile_bio = "<p>自己紹介文はまだ設定されていません。</p>";
        }

        // その他のユーザー情報を表示
        // ...

        $profile_html = "<a href='dashboard.php'>ダッシュボードに戻る</a>";
    }

    mysqli_free_result($result);
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html>

<head>
    <?php include '../theme/head.php'; ?>
    <title>ユーザープロフィール</title>
</head>

<body>
    <!-- ヘッダーをインクルード -->
    <?php include '../theme/header.php'; ?>

    <main>
    <div class="wrapper">
        <div class="container">
            <div class="wrapper-title">
                <h3><?php echo $profile_fullname; ?>さん</h3>
            </div>
            <div class="contact-form">
                <h4>アイコン</h4>
                <?php echo $user_icon_html; ?>
            </div>
            <div class="contact-form">
                <h4>自己紹介文</h4>
                <?php echo $profile_bio; ?>
            </div>
            <?php echo $profile_html; ?>
        </div>
    </div>
    </div>
    </main>
    <!-- フッターをインクルード -->
    <?php include '../theme/footer.php'; ?>
</body>

</html>