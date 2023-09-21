<?php
session_start();
// セッションを破棄してログアウト
session_unset();
session_destroy();
header("Location: login.php");
exit;
?>
