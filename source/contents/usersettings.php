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

// 初期化
$message = '';

// アクセス権限チェック
if ($_SESSION['user_role'] !== 'admin') {
    // 管理者以外はアクセス不可
    header("Location: ../contents/access_denied.php"); // アクセス拒否ページへリダイレクト
    exit;
}

// フォームからのPOSTリクエストを処理
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['username']) && isset($_POST['fullname']) && isset($_POST['password']) && isset($_POST['user_role'])) {
        $username = $_POST['username'];
        $fullname = $_POST['fullname'];
        $password = $_POST['password'];
        $user_role = $_POST['user_role'];

        // ユーザー名の重複チェック
        $query_check_username = "SELECT id FROM users WHERE username = ?";
        $stmt_check_username = mysqli_prepare($conn, $query_check_username);
        mysqli_stmt_bind_param($stmt_check_username, "s", $username);
        mysqli_stmt_execute($stmt_check_username);
        mysqli_stmt_store_result($stmt_check_username);

        if (mysqli_stmt_num_rows($stmt_check_username) > 0) {
            $message = "このユーザー名は既に登録されています。別のユーザー名を選択してください。";
        } else {
            // ユーザー名の重複がない場合のみデータベースに登録
            $query = "INSERT INTO users (username, fullname, password, user_role) VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ssss", $username, $fullname, $password, $user_role);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $message = "ユーザーが登録されました。";
        }

    }
}
// ユーザ一覧を取得
$usersList = [];
$query = "SELECT id, username, fullname, password, user_role FROM users";
$result = mysqli_query($conn, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $usersList[] = $row;
    }
    mysqli_free_result($result);
} else {
    $message = "エラー：ドメイン一覧の取得に失敗しました。";
}
// ユーザー削除の処理
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['delete_user_id'])) {
        $delete_user_id = $_POST['delete_user_id'];

        // ユーザー削除
        $query_delete_user = "DELETE FROM users WHERE id = ?";
        $stmt_delete_user = mysqli_prepare($conn, $query_delete_user);
        mysqli_stmt_bind_param($stmt_delete_user, "i", $delete_user_id);
        mysqli_stmt_execute($stmt_delete_user);
        mysqli_stmt_close($stmt_delete_user);

        // ユーザー一覧を再取得
        $usersList = [];
        $query = "SELECT id, username, fullname, password, user_role FROM users";
        $result = mysqli_query($conn, $query);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $usersList[] = $row;
            }
            mysqli_free_result($result);
        } else {
            $message = "エラー：ユーザー一覧の取得に失敗しました。";
        }
    }
}
?>

<head>
    <?php include '../theme/head.php'; ?>
    <title>ユーザー登録</title>
</head>

<body>
    <!-- ヘッダーをインクルード -->
    <?php include '../theme/header.php'; ?>
    <main>
        <div class="wrapper">
            <div class="container">
                <div class="wrapper-title">
                    <h3>ユーザー登録</h3>
                </div>
                <div class="contact-form">
                    <p style="color: red;">
                        <?php echo $message; ?>
                    </p>
                    <form method="post" action="" id="userForm">
                        <table>
                            <tr>
                                <th>ロール</th>
                                <td>
                                    <input type="radio" id="user_role_user" name="user_role" value="user" required>
                                    <label for="user_role_user">User</label>
                                    <input type="radio" id="user_role_admin" name="user_role" value="admin" required>
                                    <label for="user_role_admin">Admin</label>
                                </td>
                            </tr>
                            <tr>
                                <th>ユーザーID</th>
                                <td><input type="text" id="username" name="username" required></td>
                            </tr>
                            <tr>
                                <th>ユーザー名</th>
                                <td><input type="text" id="fullname" name="fullname" required></td>
                            </tr>
                            <tr>
                                <th>パスワード</th>
                                <td><input type="password" id="password" name="password" required></td>
                            </tr>
                        </table>
                        <input class="submit-btn" type="submit" value="登録">
                    </form>
                </div>
                <div class="wrapper-title">
                    <h3>ユーザ一覧</h3>
                </div>
                <div class="scroll">
                    <table class="design01">
                        <tr>
                            <th>ID</th>
                            <th>ユーザID</th>
                            <th>ユーザ名</th>
                            <th>ロール</th>
                            <th>操作</th>
                        </tr>
                        <?php foreach ($usersList as $users): ?>
                            <tr>
                                <td>
                                    <?php echo $users['id']; ?>
                                </td>
                                <td>
                                    <?php echo $users['username']; ?>
                                </td>
                                <td>
                                    <?php echo $users['fullname']; ?>
                                </td>
                                <td>
                                    <?php echo $users['user_role']; ?>
                                </td>
                                <td>
                                    <!-- ユーザー削除フォーム -->
                                    <form method="post">
                                        <input type="hidden" name="delete_user_id" value="<?php echo $users['id']; ?>">
                                        <button type="submit"
                                            onclick="return confirm('ユーザ <?php echo $users['username']; ?> を削除してもよろしいですか？')">削除</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
        </div>
    </main>
    <footer>
        <!-- フッターをインクルード -->
        <?php include '../theme/footer.php'; ?>
    </footer>
</body>

</html>