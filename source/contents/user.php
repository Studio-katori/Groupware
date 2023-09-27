<?php
session_start();
require_once '../config/config.php';

// ログインしていない場合はログインページにリダイレクト
if (!isset($_SESSION['user_id'])) {
    header("Location: ../config/login.php");
    exit;
}

// フォームが送信された場合の処理（パスワード変更）
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // 現在のパスワードをデータベースから取得し、入力されたパスワードと照合する
    $query = "SELECT password FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $hashed_password);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    // パスワードの照合と新しいパスワードの確認
    if ($current_password === $hashed_password && $new_password === $confirm_password) {
        // 新しいパスワードをデータベースに保存
        $query = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "si", $new_password, $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // パスワード変更成功のメッセージを表示してダッシュボードにリダイレクト
        echo "パスワードが変更されました。";
        header("Location: ../contents/dashboard.php");
        exit;
    } else {
        // パスワード変更失敗のエラーメッセージ
        $password_error_message = "現在のパスワードが間違っているか、新しいパスワードが一致しません。";
    }
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
    // アイコンのアップロード処理
    // ...
}

// ユーザーのアイコンを表示
$user_id = $_SESSION['user_id']; // ユーザーIDをセッションから取得

// データベースから保存先パスを取得
$query = "SELECT user_icon FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $user_icon);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);
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
                        <table>
                            <tr>
                                <th>現在のパスワード</th>
                                <td><input type="password" id="current_password" name="current_password" required></td>
                            </tr>
                            <tr>
                                <th>新しいパスワード</th>
                                <td><input type="password" id="new_password" name="new_password" required></td>
                            </tr>
                            <tr>
                                <th>確認用パスワード</th>
                                <td><input type="password" id="confirm_password" name="confirm_password" required></td>
                            </tr>
                        </table>
                        <input type="submit" name="change_password" value="パスワード変更">
                    </form>
                </div>
                <!-- 自己紹介文変更フォーム -->
                <div class="contact-form">
                    <h4>自己紹介文変更</h4>
                    <form method="post" action="" id="bioForm">
                        <textarea name="bio" rows="4" cols="50"><?php echo $user['bio']; ?></textarea>
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