<?php
session_start();

if (!isset($_SESSION["message"])) {
    echo "集計に失敗しました。";
    exit;
}

// セッションからデータを取得
$message = $_SESSION['message'];
// セッションデータを削除
unset($_SESSION['message']);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>集計結果</title>
</head>
<body>
    <p><?= htmlspecialchars($message) ?></p>

    <a href="log_aggregator.html">集計画面に戻る</a>
</body>
</html>