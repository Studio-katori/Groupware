<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once './config/config.php';

// もし既にログイン済みの場合、ログイン後のページにリダイレクト
if (isset($_SESSION['user_id'])) {
    header("Location: ./contents/dashboard.php");
    exit;
}

// ログインフォームの送信ボタンが押された場合の処理
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input_username = $_POST['username'];
    $input_password = $_POST['password'];

    // データベースからユーザー情報を取得して認証を行います
    $query = "SELECT id, user_role FROM users WHERE username = ? AND password = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ss", $input_username, $input_password);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) == 1) {
        // ログイン成功したらセッションにユーザーIDを保存してログイン後のページにリダイレクト
        mysqli_stmt_bind_result($stmt, $user_id, $user_role);
        mysqli_stmt_fetch($stmt);
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_role'] = $user_role;
        header("Location: ./contents/dashboard.php");
        exit;
    } else {
        // ログイン失敗の場合のエラーメッセージ
        $error_message = "ユーザー名またはパスワードが間違っています";
    }
}
?>

<head>
<link rel="stylesheet" href="./theme/styles.css">
    <title>管理画面ログイン</title>
</head>

<body>
    <div class="login-wrapper" id="login">
        <div class="container">
            <div class="login">
                <div class="login-wrapper-title">
                    <h3>ログイン</h3>
                </div>
                    <form class="login-form" method="post" action="">
                    <?php if (isset($error_message)) : ?>
                        <p style="color: red;"><?php echo $error_message; ?></p>
                    <?php endif; ?>
                    <div class="form-group">
                        <p>ユーザー名</p>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <p>パスワード</p>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-submit" >ログイン</button>
                </form>
            </div>
        </div>
    </div>
</body>

</html>