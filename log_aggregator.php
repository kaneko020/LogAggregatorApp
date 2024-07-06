<?php
session_start();

// 何も受け取れなかった場合
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
	// 結果をセッションに保存
	$_SESSION['message'] = "リクエストの送信に失敗しました。";

	// リダイレクト
	header("Location: result.php");
	exit;
}

// ユーザーが選択した集計間隔を取得
$type = $_POST['type'];

// ログファイルが格納されているディレクトリのパス
$directoryPath = "logs";
// 検索文字列
$searchString = "POST '/Web/User/Login'";
// 日時のパターン
$pattern = "/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/";

// 現在時刻
$currentDateTime = date("Ymd_His");
// 出力するCSVファイルのパス
$csvFile = "csv/" . $type . "_aggregated_results_" . $currentDateTime . ".csv";

if ($type == "hour") {
	$formatType = "H";
	// 集計実行
	aggregateLogsByHour($directoryPath, $searchString, $pattern, $formatType, $csvFile);
} else {
	if ($type == "minute") {
		$formatType = "H:i";
	} else if ($type == "second") {
		$formatType = "H:i:s";
	}

	// 集計実行
	aggregateLogsByMinuteOrSecond($directoryPath, $searchString, $pattern, $formatType, $csvFile);
}

// 結果をセッションに保存
$_SESSION['message'] = "集計が正常に終了しました。\n$csvFile";

// リダイレクト
header("Location: result.php");
exit;

function aggregateLogsByHour($directoryPath, $searchString, $pattern, $formatType, $csvFile) {
	try {
		$countPerTime = [];
		// ディレクトリ内のフォルダを取得してソート
		$folders = [];
		$iterator = new DirectoryIterator($directoryPath);
		foreach ($iterator as $fileinfo) {
			if ($fileinfo->isDir() && !$fileinfo->isDot()) {
				$folders[] = $fileinfo->getPathname();
			}
		}
		natsort($folders); // 自然順ソート

		// 各フォルダ内のログファイルを処理
		foreach ($folders as $folderPath) {
			// サブディレクトリ内の全てのログファイルを処理
			foreach (glob("$folderPath/*.log") as $logFilePath) {
				// ファイルを読み込む
				$lines = file($logFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

				// 各行を処理
				foreach ($lines as $line) {
					if (preg_match($pattern, $line, $matches)) {
						$dateTime = new DateTime($matches[1]);
						// 時間部分を取得する
						$time = $dateTime->format($formatType);

						// 時間をキーに配列を作成
						if (!isset($countPerTime[$time])) {
							$countPerTime[$time] = 0;
						}

						// 部分文字列が含まれているかを判定
						if (stripos($line, $searchString) !== false) {
							$countPerTime[$time]++;
						}
					}
				}
			}
		}
		// ソート
		ksort($countPerTime);

		// ファイルを開く（書き込みモード）
		$fileHandle = fopen($csvFile, "w");
		// ヘッダーを書き込む
		$times = array_keys($countPerTime);
		fputcsv($fileHandle, $times);
		// 結果を出力
		$counts = array_values($countPerTime);
		fputcsv($fileHandle, $counts);
		// ファイルを閉じる
		fclose($fileHandle);
	} catch (Exception $e) {
		// 結果をセッションに保存
		$_SESSION['message'] = "集計に失敗しました。";

		// リダイレクト
		header("Location: result.php");
		exit;
	}
}

function aggregateLogsByMinuteOrSecond($directoryPath, $searchString, $pattern, $formatType, $csvFile) {
	try {
		// ファイルを開く（書き込みモード）
		$fileHandle = fopen($csvFile, "w");

		// ディレクトリ内のフォルダを取得してソート
		$folders = [];
		$iterator = new DirectoryIterator($directoryPath);
		foreach ($iterator as $fileinfo) {
			if ($fileinfo->isDir() && !$fileinfo->isDot()) {
				$folders[] = $fileinfo->getPathname();
			}
		}
		// ソート
		natsort($folders);
	
		// 各フォルダ内のログファイルを処理
		foreach ($folders as $folderPath) {
			$countPerTime = [];

			// サブディレクトリ内の全てのログファイルを処理
			foreach (glob("$folderPath/*.log") as $logFilePath) {
				// ファイルを読み込む
				$lines = file($logFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

				// 各行を処理
				foreach ($lines as $line) {
					if (preg_match($pattern, $line, $matches)) {
						$dateTime = new DateTime($matches[1]);
						// 時間部分を取得する
						$time = $dateTime->format($formatType);

						// 時間をキーに配列を作成
						if (!isset($countPerTime[$time])) {
							$countPerTime[$time] = 0;
						}

						// 部分文字列が含まれているかを判定
						if (stripos($line, $searchString) !== false) {
							$countPerTime[$time]++;
						}
					}
				}
			}

			// 結果を出力
			foreach ($countPerTime as $time => $count) {
				fputcsv($fileHandle, [$time, $count]);
			}
			
			// // ヘッダーを書き込む
			// $times = array_keys($countPerTime);
			// fputcsv($fileHandle, $times);
			// // 結果を出力
			// $counts = array_values($countPerTime);
			// fputcsv($fileHandle, $counts);
		}

		// ファイルを閉じる
		fclose($fileHandle);
	} catch (Exception $e) {
		// 結果をセッションに保存
		$_SESSION['message'] = "集計に失敗しました。";

		// リダイレクト
		header("Location: result.php");
		exit;
	}
}
