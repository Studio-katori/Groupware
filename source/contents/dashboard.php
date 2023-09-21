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

// ユーザー情報を取得
$user_info = [];
$query = "SELECT fullname, user_icon, user_role FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $fullname, $user_icon, $user_role);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

// ページネーションの設定
$items_per_page = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

// ツイートをデータベースから取得
$tweets = [];
$query = "SELECT tweets.*, users.fullname, users.user_icon FROM tweets INNER JOIN users ON tweets.user_id = users.id ORDER BY tweets.created_at DESC LIMIT ? OFFSET ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $items_per_page, $offset);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {
    $tweets[] = $row;
}

mysqli_free_result($result);
mysqli_stmt_close($stmt);

// 全体のツイート数を取得
$query = "SELECT COUNT(*) AS total_tweets FROM tweets";
$result = mysqli_query($conn, $query);
$total_tweets = mysqli_fetch_assoc($result)['total_tweets'];
mysqli_free_result($result);

// 全体のページ数を計算
$total_pages = ceil($total_tweets / $items_per_page);
?>

<!DOCTYPE html>
<html>

<head>
    <?php include '../theme/head.php'; ?>
    <title>ダッシュボード</title>
</head>

<body>
    <!-- ヘッダーをインクルード -->
    <?php include '../theme/header.php'; ?>
    <main>
        <div class="wrapper">
            <div class="container">
                <h2>ようこそ、
                    <?php echo $fullname; ?> さん！
                </h2>
                <div class="wrapper-title">
                    <h3>ダッシュボード</h3>
                </div>
                <div class="boxs">
                    <a href="domain.php" class="box">
                        <i class="fa-solid fa-globe icon"></i>
                        <p>ドメイン</p>
                    </a>
                    <a href="ssl.php" class="box">
                        <i class="fa-brands fa-expeditedssl icon"></i>
                        <p>SSL</p>
                    </a>
                    <a href="user.php" class="box">
                        <i class="fa-solid fa-user icon"></i>
                        <p>パスワード</p>
                    </a>
                    <a href="tweet.php" class="box">
                        <i class="fa-brands fa-twitter icon"></i>
                        <p>Tweet</p>
                    </a>
                    <?php
                    if ($user_role === 'admin') {
                        // adminユーザーの場合のみユーザーページへのリンクを表示
                        echo '<a href="usersettings.php" class="box">';
                        echo '<i class="fa-solid fa-users-gear icon"></i>';
                        echo '<p>ユーザー</p>';
                        echo '</a>';
                    }
                    ?>
                </div>
                <div class="wrapper-title">
                    <h3>ツイート一覧</h3>
                </div>
                <!-- ツイート一覧 -->
                <div class="tweet-list">
                    <?php foreach ($tweets as $tweet): ?>
                        <div class="tweet">
                            <table>
                                <tr>
                                    <!-- プロフィールへのリンクを追加 -->
                                    <th><a href="profile.php?user_id=<?php echo $tweet['user_id']; ?>"><img
                                                src="<?php echo "user_icons/{$tweet['user_icon']}"; ?>" alt="User Icon"
                                                width="30" height="30"></a></th>
                                    <td>
                                        <p class="tweet-info">
                                            <?php echo $tweet['fullname']; ?> -
                                            <?php echo $tweet['created_at']; ?>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <th></th>
                                    <td>
                                        <p>
                                            <?php echo $tweet['content']; ?>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    <?php endforeach; ?>
                </div>
                <!-- ページネーション -->
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </main>
    <!-- フッターをインクルード -->
    <?php include '../theme/footer.php'; ?>
</body>

</html>