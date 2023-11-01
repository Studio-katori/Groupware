<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config/config.php';

// ログインしていない場合はログインページにリダイレクト
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// フォームが送信された場合の処理（パスワード変更）
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    // ... （以前のコードはそのまま）
}

// フォームが送信された場合の処理（自己紹介文変更）
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_bio'])) {
    $bio = $_POST['bio'];

    // 自己紹介文をデータベースに保存
    $query = "UPDATE users SET bio = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "si", $bio, $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // 自己紹介文変更成功のメッセージを表示
    echo "自己紹介文が変更されました。";
}

// フォームが送信された場合の処理（アイコン変更）
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_icon'])) {
    // ... （以前のコードはそのまま）
}

// ユーザーのアイコンと自己紹介文を表示
$user_id = $_SESSION['user_id']; // ユーザーIDをセッションから取得

// データベースから保存先パスと自己紹介文を取得
$query_user = "SELECT user_icon, bio FROM users WHERE id = ?";
$stmt_user = mysqli_prepare($conn, $query_user);
mysqli_stmt_bind_param($stmt_user, "i", $user_id);
mysqli_stmt_execute($stmt_user);
mysqli_stmt_bind_result($stmt_user, $user_icon, $user_bio);
mysqli_stmt_fetch($stmt_user);
mysqli_stmt_close($stmt_user);
?>

<head>
    <?php include '../theme/head.php'; ?>
    <title>プロフィール編集</title>
</head>

<body>
    <!-- ヘッダーをインクルード -->
    <?php include '../theme/header.php'; ?>

    <main>
        <div class="wrapper">
            <div class="container">
                <div class="wrapper-title">
                    <h3>プロフィール編集</h3>
                </div>
                <!-- パスワード変更フォーム -->
                <div class="contact-form">
                    <h4>パスワード変更</h4>
                    <form method="post" action="" id="passwordForm">
                        <!-- パスワード変更フォームの内容 -->
                        <!-- ... -->
                    </form>
                </div>
                <!-- 自己紹介文変更フォーム -->
                <div class="contact-form">
                    <h4>自己紹介文変更</h4>
                    <form method="post" action="" id="bioForm">
                        <textarea name="bio" rows="4" cols="50"><?php echo $user_bio; ?></textarea>
                        <input type="submit" name="change_bio" value="自己紹介文変更">
                    </form>
                </div>
                <!-- アイコン変更フォーム -->
                <div class="contact-form">
                    <h4>アイコン変更</h4>
                    <!-- アイコン表示 -->
                    <?php
                    if ($user_icon) {
                        $icon_path = "user_icons/" . $user_icon; // アイコンのファイルパス
                        echo "<img src='$icon_path' alt='User Icon' width='30' height='30'>";
                    } else {
                        echo "アイコンが設定されていません。";
                    }
                    ?>
                    <form method="post" action="upload.php" id="iconForm" enctype="multipart/form-data">
                        <!-- アイコン変更フォームの内容 -->
                        <input type="file" name="icon" id="icon">
                        <input type="submit" name="change_icon" value="アイコン変更">
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- フッターをインクルード -->
    <?php include '../theme/footer.php'; ?>
</body>

</html>