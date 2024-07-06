<?php
// ログファイルが格納されているディレクトリのパス
$logDirPath = "logs";

// 検索文字列
$searchString = "POST '/Web/User/Login'";

// 日時のパターン
$pattern = "/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/";

// 集計用の配列
$countPerTime = [];

// 指定されたディレクトリ内の全てのサブディレクトリを処理
foreach (new DirectoryIterator($logDirPath) as $dirInfo) {
  	if ($dirInfo->isDir() && !$dirInfo->isDot()) {
      	$subDirPath = $dirInfo->getPathname();
      
      	// サブディレクトリ内の全てのログファイルを処理
      	foreach (glob("$subDirPath/*.log") as $logFilePath) {
			// ファイルを読み込む
			$lines = file($logFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

			// 各行を処理
			foreach ($lines as $line) {
				if (preg_match($pattern, $line, $matches)) {
					$dateTime = new DateTime($matches[1]);
					// 時間部分を取得する
					$time = $dateTime->format('H');

					// 部分文字列が含まれているかを判定
					if (stripos($line, $searchString) !== false) {
						if (!isset($countPerTime[$time])) {
							$countPerTime[$time] = 0;
						}
						$countPerTime[$time]++;
					}
				}
			}
		}
	}
}

// 結果を表示
foreach ($countPerTime as $time => $count) {
	echo "$time" . "　" . "$count<br/>";
}


