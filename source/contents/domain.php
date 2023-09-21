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

// ユーザーIDを取得して、必要な情報をデータベースから取得するなどの処理がここに入ります
// この例では、ログインユーザーの情報を取得して表示するだけのシンプルなダッシュボードとします
$query = "SELECT username FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $username);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

// 初期化
$message = '';

// 設定フォームからのPOSTリクエストを処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['serverName']) && isset($_POST['documentRoot'])) {
    // ドメイン情報を取得
    $serverName = $_POST['serverName'];
    $documentRoot = $_POST['documentRoot'];

    // データベースにバーチャルドメイン情報を登録前に、同じサーバー名が既に存在するか確認
    $query = "SELECT COUNT(*) AS count FROM domains WHERE serverName = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $serverName);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $count);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if ($count > 0) {
        // 同じサーバー名が既に存在する場合は、エラーメッセージを表示
        $message = "エラー：同じドメイン名が既に存在します。別のドメイン名を入力してください。";
    } else {
        // Apacheの設定ファイルを生成
        $virtualHostConfig = "
        <VirtualHost *:80>
            ServerName $serverName
            DocumentRoot $documentRoot
        
            ErrorLog $documentRoot/$serverName-error.log
            CustomLog $documentRoot/$serverName-access.log customformat env=!no_log
            LogLevel warn
        
            LogFormat \"%h %l %u %t \\\"%r\\\" %>s %b \\\"%{Referer}i\\\" \\\"%{User-Agent}i\\\"\" customformat
        </VirtualHost>
        ";

        // 設定ファイルを保存
        $virtualHostFileName = "/etc/httpd/conf.d/virtualhost-99-$serverName.conf";
        if (file_put_contents($virtualHostFileName, $virtualHostConfig)) {
            // データベースにバーチャルドメイン情報を登録
            $serverName = mysqli_real_escape_string($conn, $_POST['serverName']);
            $documentRoot = mysqli_real_escape_string($conn, $_POST['documentRoot']);
            $query = "INSERT INTO domains (serverName, documentRoot) VALUES ('$serverName', '$documentRoot')";
            if (mysqli_query($conn, $query)) {
                // Apacheを再読込して設定を反映
                exec('sudo apachectl graceful', $output, $return_var);

                if ($return_var === 0) {
                    $message = "ドメインの設定が完了しました。Apacheが再読込されました。";
                } else {
                    $message = "エラー：Apacheの再読込に失敗しました。";
                }
            } else {
                $message = "エラー：データベースへの登録に失敗しました。";
            }
        } else {
            $message = "エラー：設定ファイルの保存に失敗しました。";
        }
    }
}

// 削除フォームからのPOSTリクエストを処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteDomain'])) {
    $domainId = $_POST['deleteDomain'];

    // データベースからドメイン情報を取得
    $query = "SELECT serverName FROM domains WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $domainId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $serverName);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if ($serverName) {
        // SSL証明書ファイルとApacheの設定ファイルを削除
        $configFile = "/etc/httpd/conf.d/virtualhost-99-$serverName.conf";
        $sslConfigFile = "/etc/httpd/conf.d/virtualhost-99-{$serverName}_ssl.conf";
        $sslDir = "/etc/letsencrypt/live/$serverName";

        if (file_exists($configFile)) {
            unlink($configFile);
        }

        if (file_exists($sslConfigFile)) {
            unlink($sslConfigFile);
            // Apacheを再読込して設定を反映
            exec('sudo apachectl graceful', $output, $return_var);
        }

        if (file_exists($sslDir)) {
            exec("sudo certbot delete --cert-name $serverName --non-interactive");
        }

        // データベースからレコードを削除
        $query = "DELETE FROM domains WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $domainId);
        if (mysqli_stmt_execute($stmt)) {
            $message = "ドメインとSSL設定の削除が完了しました。";
        } else {
            $message = "エラー：ドメインとSSL設定の削除に失敗しました。";
        }
        mysqli_stmt_close($stmt);
    } else {
        $message = "エラー：指定されたドメインが見つかりません。";
    }
}


// バーチャルドメイン一覧を取得
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
    <title>ドメイン設定</title>
</head>

<body>
    <!-- ヘッダーをインクルード -->
    <?php include '../theme/header.php'; ?>
    <main>
        <div class="wrapper">
            <div class="container">
                <div class="wrapper-title">
                    <h3>ドメイン設定</h3>
                </div>
                <p style="color: red;">
                    <?php echo $message; ?>
                </p>
                <div class="contact-form">
                    <form method="post">
                        <table>
                            <tr>
                                <th>ドメイン</th>
                                <td><input type="text" name="serverName" required></td>
                            </tr>
                            <tr>
                                <th>ドキュメントルート</th>
                                <td><input type="text" name="documentRoot" required></td>
                            </tr>
                        </table>
                        <input class="submit-btn" type="submit" value="設定を保存">
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
                            <th>操作</th>
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
                                <td>
                                    <form method="post">
                                        <input type="hidden" name="deleteDomain" value="<?php echo $domain['id']; ?>">
                                        <button type="submit"
                                            onclick="return confirm('ドメイン <?php echo $domain['serverName']; ?> を削除してもよろしいですか？')">削除</button>
                                    </form>
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