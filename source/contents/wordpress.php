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

// フォームからのPOSTリクエストを処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['serverName'])) {
    $serverName = $_POST['serverName'];

    // データベースからドキュメントルートを取得
    $query = "SELECT documentRoot FROM domains WHERE serverName = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $serverName);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $documentRoot = rtrim($row['documentRoot'], '/'); // ドキュメントルートの末尾にスラッシュがあれば削除

        // 指定されたドキュメントルートが存在するか確認し、なければ作成
        if (!file_exists($documentRoot)) {
            mkdir($documentRoot, 0755, true);
        }

        // WordPressのダウンロードと展開
        exec("wget https://wordpress.org/latest.tar.gz -O /tmp/wordpress.tar.gz", $output, $return_var);
        exec("tar -xzf /tmp/wordpress.tar.gz -C /tmp", $output, $return_var);
        exec("sudo rsync -av /tmp/wordpress/ $documentRoot", $output, $return_var);
        exec("sudo chown -R apache:apache $documentRoot", $output, $return_var);

        if ($return_var === 0) {
            // データベース作成
            $dbName = str_replace(['.', '-'], '_', $serverName) . '_db';
            $dbUser = 'wp_user';
            $dbPassword = bin2hex(random_bytes(8));

            // データベースが存在しない場合のみ作成
            $checkDbQuery = "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbName'";
            $checkDbResult = mysqli_query($conn, $checkDbQuery);

            if (mysqli_num_rows($checkDbResult) === 0) {
                $createDbQuery = "CREATE DATABASE $dbName";
                mysqli_query($conn, $createDbQuery);
            }

            // ユーザーの存在を確認
            $checkUserQuery = "SELECT EXISTS(SELECT 1 FROM mysql.user WHERE user = '$dbUser') AS userExists";
            $checkUserResult = mysqli_query($conn, $checkUserQuery);
            $userExists = mysqli_fetch_assoc($checkUserResult)['userExists'];

            try {
                if ($userExists) {
                    // ユーザーが存在する場合はパスワードをリセット
                    $resetPasswordQuery = "ALTER USER '$dbUser'@'localhost' IDENTIFIED BY '$dbPassword'";
                    mysqli_query($conn, $resetPasswordQuery);
                } else {
                    // ユーザーが存在しない場合は作成
                    $createUserQuery = "CREATE USER '$dbUser'@'localhost' IDENTIFIED BY '$dbPassword'";
                    mysqli_query($conn, $createUserQuery);
                }

                // 権限付与
                $grantQuery = "GRANT ALL PRIVILEGES ON $dbName.* TO '$dbUser'@'localhost'";
                mysqli_query($conn, $grantQuery);
            } catch (mysqli_sql_exception $e) {
                $message = "エラー：ユーザー作成または更新中に問題が発生しました - " . $e->getMessage();
            }

            // wp-config.phpを設定
            $wpConfig = file_get_contents("$documentRoot/wp-config-sample.php");
            $wpConfig = str_replace('database_name_here', $dbName, $wpConfig);
            $wpConfig = str_replace('username_here', $dbUser, $wpConfig);
            $wpConfig = str_replace('password_here', $dbPassword, $wpConfig);
            file_put_contents("$documentRoot/wp-config.php", $wpConfig);

            $message = "WordPressのインストールが完了しました。";
        } else {
            $message = "エラー：WordPressのインストールに失敗しました。";
        }
    } else {
        $message = "エラー：指定されたドメインが見つかりません。";
    }
}

// ドメイン一覧を取得
$domainList = [];
$query = "SELECT serverName, documentRoot, id FROM domains";
$result = mysqli_query($conn, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $domainList[] = $row;
    }
    mysqli_free_result($result);
} else {
    $message = "エラー：ドメイン一覧の取得に失敗しました。";
}
?>

<head>
    <?php include '../theme/head.php'; ?>
    <title>WordPressインストール</title>
</head>

<body>
    <!-- ヘッダーをインクルード -->
    <?php include '../theme/header.php'; ?>
    <main>
        <div class="wrapper">
            <div class="container">
                <div class="wrapper-title">
                    <h3>WordPressインストール</h3>
                </div>
                <div class="contact-form">
                    <p style="color: red;">
                        <?php echo $message; ?>
                    </p>
                    <form method="post" action="" id="userForm">
                        <table>
                            <tr>
                                <th>ドメイン</th>
                                <td><input type="text" name="serverName" required></td>
                            </tr>
                        </table>
                        <input class="submit-btn" type="submit" value="インストール">
                    </form>
                </div>
                <div class="wrapper-title">
                    <h3>ドメイン一覧</h3>
                </div>
                <div class="scroll">
                    <table class="design01">
                        <tr>
                            <th>ID</th>
                            <th>ドメイン</th>
                            <th>ドキュメントルート</th>
                        </tr>
                        <?php foreach ($domainList as $domain): ?>
                            <tr>
                                <td>
                                    <?php echo $domain['id']; ?>
                                </td>
                                <td>
                                    <?php echo $domain['serverName']; ?>
                                </td>
                                <td>
                                    <?php echo $domain['documentRoot']; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
        </div>
    </main>
    <!-- フッターをインクルード -->
    <?php include '../theme/footer.php'; ?>
</body>

</html>
