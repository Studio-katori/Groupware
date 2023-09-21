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

    // Let's Encryptの証明書取得とApacheの設定
    exec("sudo certbot --apache -d $serverName");

    // Apacheを再読込して設定を反映
    exec('sudo systemctl reload httpd', $output, $return_var);

    if ($return_var === 0) {
        // SSL設定が成功した場合、データベースのsslEnabledカラムを更新
        $query = "UPDATE domains SET sslEnabled = 1 WHERE serverName = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $serverName);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // データベースからDocumentRootを取得
        $query = "SELECT documentRoot FROM domains WHERE serverName = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $serverName);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $documentRoot);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        // Apache SSL設定ファイルの作成
        $sslConfigPath = "/etc/httpd/conf.d/virtualhost-99-{$serverName}_ssl.conf";
        $sslConfigContents = "
<VirtualHost *:443>
    ServerName $serverName
    DocumentRoot $documentRoot
    
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/$serverName/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/$serverName/privkey.pem
    
    # Other SSL settings (if needed)
</VirtualHost>";

        file_put_contents($sslConfigPath, $sslConfigContents);

        $message = "SSL設定が完了しました。Apacheが再読込されました。";
    } else {
        $message = "エラー：SSL設定またはApacheの再読込に失敗しました。";
    }
}
// バーチャルドメイン一覧を取得
$domainList = [];
$query = "SELECT serverName, documentRoot, sslEnabled, id FROM domains";
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
    <title>ドメインSSL設定</title>
</head>

<body>
    <!-- ヘッダーをインクルード -->
    <?php include '../theme/header.php'; ?>
    <main>
        <div class="wrapper">
            <div class="container">
                <div class="wrapper-title">
                    <h3>ドメインSSL設定</h3>
                </div>
                <div class="contact-form">
                    <p style="color: red;">
                        <?php echo $message; ?>
                    </p>
                    <p>DNS登録をしてから実行してください</p>
                    <form method="post" action="" id="userForm">
                        <table>
                            <tr>
                                <th>ドメイン</th>
                                <td><input type="text" name="serverName" required></td>
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
                            <th>SSL</th>
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
                                    <?php echo $domain['sslEnabled'] ? '有効' : '無効'; ?>
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