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
$user_info = []; // 新たな配列を作成
$query = "SELECT fullname, user_icon FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $fullname, $user_icon); // ユーザー名とアイコンの取得
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

// ツイートをデータベースに挿入
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['tweet_content']) && !empty($_POST['tweet_content'])) {
        $tweet_content = $_POST['tweet_content'];
        $user_id = $_SESSION['user_id'];

        $query = "INSERT INTO tweets (user_id, content, created_at) VALUES (?, ?, NOW())";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "is", $user_id, $tweet_content);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // POSTリクエストを処理した後、リダイレクトする
        header("Location: tweet.php"); // your_page.php を実際のページ名に置き換えてください
        exit; // リダイレクト後にスクリプトの実行を終了
    }
}

// ツイート一覧を取得
$tweets = [];
$query = "SELECT tweets.*, users.fullname, users.user_icon FROM tweets INNER JOIN users ON tweets.user_id = users.id ORDER BY tweets.created_at DESC";
$result = mysqli_query($conn, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $tweets[] = $row;
    }
    mysqli_free_result($result);
} else {
    $message = "エラー：ツイートの取得に失敗しました。";
}



// リプライがクリックされた場合の処理
if (isset($_POST['reply'])) {
    $parent_tweet_id = $_POST['parent_tweet_id'];
    $user_id = $_SESSION['user_id'];
    $reply_content = $_POST['reply_content'];

    // リプライをデータベースに保存するクエリを実行
    $query = "INSERT INTO tweets (user_id, content, parent_tweet_id, created_at) VALUES (?, ?, ?, NOW())";
    $stmt = mysqli_prepare($conn, $query);

    if ($stmt === false) {
        die("Error: " . mysqli_error($conn)); // エラーメッセージを表示
    }

    mysqli_stmt_bind_param($stmt, "isi", $user_id, $reply_content, $parent_tweet_id);

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);

        // リプライが成功したら、リダイレクトなどの適切な処理を行う
        header("Location: tweet.php"); // リプライ後に表示するページに適切なページ名を設定
        exit;
    } else {
        die("Error: " . mysqli_error($conn)); // エラーメッセージを表示
    }
}

?>

<!DOCTYPE html>
<html>

<head>
    <?php include '../theme/head.php'; ?>
    <title>投稿システム</title>
    <!-- ここにCSSファイルをリンク -->
</head>

<body>
    <!-- ヘッダーをインクルード -->
    <?php include '../theme/header.php'; ?>

    <main>
        <div class="wrapper">
            <div class="container">
                <div class="wrapper-title">
                    <h3>Tweet</h3>
                </div>
                <!-- ユーザアイコンを表示 -->
                <div class="user-icon">
                    <table>
                        <tr>
                            <th><img src="<?php echo "user_icons/{$user_icon}"; ?>" alt="User Icon" width="30"
                                    height="30"></th>
                            <td>
                                <?php echo $fullname; ?>
                            </td>
                        </tr>
                    </table>
                </div>
                <!-- ツイート投稿フォーム -->
                <form method="post" action="">
                    <textarea name="tweet_content" rows="4" cols="40" placeholder="今何をしていますか？"></textarea>
                    <br>
                    <input type="submit" value="ツイート">
                </form>

                <!-- ツイート一覧 -->
                <div class="tweet-list">
                    <?php foreach ($tweets as $tweet): ?>
                        <div class="tweet">
                            <table>
                                <tr>
                                    <th><img src="<?php echo "user_icons/{$tweet['user_icon']}"; ?>" alt="User Icon"
                                            width="30" height="30"></th>
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

                                        <!-- リプライフォーム -->
                                        <form method="post" action="">
                                            <input type="hidden" name="parent_tweet_id" value="<?php echo $tweet['id']; ?>">
                                            <textarea name="reply_content" rows="2" cols="40"
                                                placeholder="返信を入力"></textarea>
                                            <br>
                                            <input type="submit" name="reply" value="返信">
                                        </form>
                                    </td>
                                </tr>

                                <!-- リプライ一覧 -->
                                <?php
                                foreach ($tweets as $reply) {
                                    if ($reply['parent_tweet_id'] == $tweet['id']) {
                                        echo '<tr>';
                                        echo '<th></th>';
                                        echo '<td>';
                                        echo '<p>';
                                        echo '<img src="' . "user_icons/{$reply['user_icon']}" . '" alt="User Icon" width="25" height="25">';
                                        echo $reply['fullname'] . ' - ' . $reply['created_at'] . '<br>';
                                        echo $reply['content'];
                                        echo '</p>';
                                        echo '</td>';
                                        echo '</tr>';
                                    }
                                }
                                ?>
                            </table>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- フッターをインクルード -->
    <?php include '../theme/footer.php'; ?>
</body>

</html>