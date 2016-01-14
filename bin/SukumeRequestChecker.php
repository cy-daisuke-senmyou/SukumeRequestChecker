<?php

//--------------------------------------
// 共通設定・関数の読み込み
//--------------------------------------

require_once("HTTP/Request2.php");
require_once("Mail.php");

// デバッグフラグ true にするとメール送信は行わず結果を標準出力に表示する。
define('DEBUG_FLG', false);
// ベースディレクトリ
define('BASE_DIR', '/home/cybird/SukumeRequestChecker/');
// 設定ファイルのディレクトリ
define('CONF_FILE', BASE_DIR.'conf/config.ini');
// CSVファイルの一時出力先ディレクトリ
define('DATA_DIR', 	BASE_DIR.'data/');
// メール出力先ディレクトリ
define('MAIL_DIR', 	BASE_DIR.'mail/');
// デヂエのカラム設定
define('PJ_ID_COL', 0);
define('PJ_NAME_COL', 1);
define('PJ_CODE_COL', 2);
define('PJ_CHARGE_COL', "5,10,15,20,25,30,35,40,45,50,55,60,65,70,75,80,85,90,95,100");
define('MEMBER_COL', "0, 1, 2, 3, 4");
define('MEMBER_NAME_COL', 1);
// 一時ファイルの保管日数
define('FILE_KEEP_DAYS', 7);

//--------------------------------------
// 初期化処理
//--------------------------------------

// 設定ファイルの読み込み
$ini = get_ini_data();

// 日本語メールを送る際に必要
mb_language("Japanese");
$mail_body = "";

//--------------------------------------
// 実行エリア
//--------------------------------------
try {
	//////////////////////
	// 担当者マスター
	//////////////////////
	$prefix = 'member_';
	$ext = 'csv';
	$latest_member_array = array();
	$current_member_array = array();

	// 最終更新日の担当者ファイルを取得して配列に格納する。
	$latest_member_file = get_latest_file(DATA_DIR, $prefix, $ext);
	$latest_member_array = csv2array($latest_member_file);

	// デヂエにアクセスして、CSVを取得し配列に格納する。
	$dezie_member_data = get_dezie_data($ini, $prefix);
	$current_member_file = save_file($dezie_member_data, $prefix, $ext);
	$current_member_array = csv2array($current_member_file);

	// 担当者マスターの差分を取得する。
	$member_diff = get_member_diff($latest_member_array, $current_member_array);
	if(!empty($member_diff)) {
		$mail_body .= "【スクメ担当者マスター】において以下のレコードが変更されました。\n\n";
		$mail_body .= print_member_diff($member_diff);
	}

	//////////////////////
	// PJマスター
	//////////////////////
	$prefix = 'pj_';
	$ext = 'csv';
	$latest_pj_array = array();
	$current_pj_array = array();

	// 最終更新日のPJファイルを取得して配列に格納する。
	$latest_pj_file = get_latest_file(DATA_DIR, $prefix, $ext);
	$latest_pj_array = csv2array($latest_pj_file);

	// デヂエにアクセスして、CSVを取得し配列に格納する。
	$dezie_pj_data = get_dezie_data($ini, $prefix);
	$current_pj_file = save_file($dezie_pj_data, $prefix, $ext);
	$current_pj_array = csv2array($current_pj_file);

	//////////////////////
	// PJマスター
	//////////////////////
	$prefix = 'relation_';
	$ext = 'json';

	// 最終更新日の関連付けファイルを取得して配列に格納する。
	$latest_pj_file = get_latest_file(DATA_DIR, $prefix, $ext);
	$latest_relation_data = file2json($latest_pj_file);

	// デヂエのデータから現在の担当者とPJの関連付けをする。
	$current_relation_data = relate_pj_member($current_pj_array, $current_member_array);
	save_file(json_encode($current_relation_data), $prefix, $ext);

	// 関連付けされたデータの差分を抽出する。
	$pj_diff = get_pj_diff($latest_pj_array, $latest_relation_data, $latest_member_array, $current_pj_array, $current_relation_data, $current_member_array);
		if(!empty($pj_diff)) {
		$mail_body .= "【スクメPJマスター】において以下のレコードが変更されました。\n\n";
		$mail_body .= print_pj_diff($pj_diff);
	}

	// 結果をレポート
	report($ini, $mail_body);



	// 古いファイルを削除
	remove_old_file(DATA_DIR);
} catch(Exception $e) {
	if(DEBUG_FLG) {
		print($e->getMessage().PHP_EOL);
	} else {
		mail_send($mailto_array, '【SukumeRequestChecker】 ErrorOccurred.', $e->getMessage());
	}

}



//--------------------------------------
// private関数エリア
//--------------------------------------

// 保管されている最新のCSVと、デヂエから取得したCSVを比較して差分を返す。
function get_member_diff($latest_data_array, $current_data_array) {
	$title_column = 1;
	$target = explode(',', MEMBER_COL);

	// ヘッダ行は後で配列添え字に使う。
	$header = $current_data_array['header'];

	// ２つの配列を比較して差分を抽出する。
	$result = array();

	// 新規レコード
	$new_record_array = array_diff_key($current_data_array, $latest_data_array);
	foreach ($new_record_array as $key => $new_record) {
		$after = array();
		$result[$key]['title'] = $new_record[$title_column];
		foreach($new_record as $index => $value) {
			// 差分抽出対象のカラムの場合は別の配列に結果を格納する。
			if(in_array($index, $target)) {
				$column = $header[$index];
				$after[$column] = $new_record[$index];
			}
		}
		$result[$key]['after'] = $after;
	}

	// 削除レコード
	$deleted_record_array = array_diff_key($latest_data_array, $current_data_array);
	foreach ($deleted_record_array as $key => $deleted_record) {
		$before = array();
		$result[$key]['title'] = $deleted_record[$title_column];
		foreach($deleted_record as $index => $value) {
			// 差分抽出対象のカラムの場合は別の配列に結果を格納する。
			if(in_array($index, $target)) {
				$column = $header[$index];
				$before[$column] = $deleted_record[$index];
			}
		}
		$result[$key]['before'] = $before;
	}


	// 両方に存在するレコードの差分を抽出
	// デヂエCSVレコード数分ループ
	foreach($current_data_array as $current_data) {
		if(empty($current_data[0])) continue;
		$current_id = $current_data[0];
		// 最終更新CSVファイルのレコード数分ループ。
		foreach($latest_data_array as $latest_data) {
			// ID列で突き合わせをする。
			$latest_id = $latest_data[0];
			if($latest_id === $current_id) {
				$array_diff = array_diff_assoc($current_data, $latest_data);
				if(!empty($array_diff)) {
					$before = array();
					$after = array();
					// 差分要素数分ループ
					foreach($array_diff as $index => $value) {
						// 差分抽出対象のカラムの場合は別の配列に結果を格納する。
						if(in_array($index, $target)) {
							$column = $header[$index];
							$before[$column] = $latest_data[$index];
							$after[$column] = $current_data[$index];
						}
					}
					// 差分があったらresuleに格納する。
					if(!empty($before) && !empty($after)) {
						$result[$current_id]['title'] = $current_data[$title_column];
						$result[$current_id]['before'] = $before;
						$result[$current_id]['after'] = $after;
					}
				}
				break;
			}
		}
	}

	return $result;
}


// 関連付けされたデータの差分を抽出する。
function get_pj_diff($latest_pj_array, $latest_relation_data, $latest_member_array, $current_pj_array, $current_relation_data, $current_member_array) {
	$result = array();
	// 追加
	foreach ($current_relation_data as $id => $current_pj_line) {
		// 比較対象が両方揃っていないとarray_diff()が機能しないので空の配列をつくる。
		if(!isset($latest_relation_data[$id])) {
			$latest_relation_data[$id] = array();
		}
		$diff = array_diff($current_pj_line, $latest_relation_data[$id]);
		if (!empty($diff)) {
			foreach ($diff as $pj_id) {
				$member_name = $current_member_array[$id][MEMBER_NAME_COL] . "($id)";
				$pj_code = $current_pj_array[$pj_id][PJ_CODE_COL];
				$pj_name = $current_pj_array[$pj_id][PJ_NAME_COL];
				$result[$member_name]['add'][] = $pj_code . '(' . $pj_name . ')';
			}
		}
	}
	// 削除
	foreach ($latest_relation_data as $id => $latest_relation_line) {
		// 比較対象が両方揃っていないとarray_diff()が機能しないので空の配列をつくる。
		if(!isset($current_relation_data[$id])) {
			$current_relation_data[$id] = array();
		}
		$diff = array_diff($latest_relation_line, $current_relation_data[$id]);
		if (!empty($diff)) {
			foreach ($diff as $pj_id) {
				$member_name = $latest_member_array[$id][MEMBER_NAME_COL] . "($id)";
				$pj_code = $latest_pj_array[$pj_id][PJ_CODE_COL];
				$pj_name = $latest_pj_array[$pj_id][PJ_NAME_COL];
				$result[$member_name]['del'][] = $pj_code . '(' . $pj_name . ')';
			}
		}
	}

	return $result;
}


// 担当者とPJの関連付けをする。
function relate_pj_member($current_pj_array, $current_member_array) {
	// メンバーデータ数分ループ
	$relation_data = array();
	foreach ($current_member_array as $id => $member_info) {
		$relation_data[$id] = array();
		// PJデータ数分ループ
		foreach($current_pj_array as $pj_info) {
			// BacklogIDのカラムを検索
			foreach(explode(',', PJ_CHARGE_COL) as $index) {
				if($id == $pj_info[$index]) {
					$relation_data[$id][] = $pj_info[PJ_ID_COL];
				}
			}
		}
	}
	return $relation_data;
}


// 対象ディレクトリ内で最新のファイルを取得する。
function get_latest_file($path, $prefix, $ext) {
	$latest_mtime = 0;
	$latest_file = '';
	foreach (glob($path.$prefix.'*.'.$ext) as $filename) {
		$mtime = filemtime( $filename );
		if( $mtime > $latest_mtime ){
			$latest_mtime = $mtime;
			$latest_file = $filename;
		}
	}

	if(DEBUG_FLG) {
		if($prefix === 'member_') {
			return '/home/cybird/SukumeRequestChecker/data/member_for_test.csv';
		} elseif($prefix === 'pj_') {
			return '/home/cybird/SukumeRequestChecker/data/pj_for_test.csv';
		} else {
			return '/home/cybird/SukumeRequestChecker/data/relation_for_test.json';
		}
	}

	if(empty($latest_file)) {
		return false;
	} else {
		return $latest_file;
	}
}


// カスタムアプリにアクセスして、CSVを取得しファイルに格納する。
function get_dezie_data($ini, $prefix) {
	$url = $ini[$prefix.'dezie_url'];
	$body = "";

	$req = new HTTP_Request2($url);
	$req->setHeader('allowRedirects-Alive', true);   // リダイレクトの許可設定(true/false)
	$req->setHeader('maxRedirects', 3);              // リダイレクトの最大回数

	$response = $req->send();
	if($response->getStatus() == 200) {
		$body = $response->getBody();
	}

	// 通信エラー、権限設定の変更などがあるとエラー画面が表示される。
	if(preg_match('/^<!DOCTYPE html>/', $body)) {
		throw new Exception("デヂエからのデータ取得に失敗しました。");
	}

	// デヂエからのデータに Cookie のデータが入ってしまうので削除
	$body = remove_cookie($body);

	// デヂエから取得したレコードの中に改行が含まれている。
	// レコード中の改行は LF で行末は CR+LF なので前者だけ<br>に置換する。
	$body = lf2br($body);

	return $body;
}

// CSVファイルを読み込み配列に格納する。
function csv2array($path) {
	$fp = fopen($path, "r");
	$csv_data = array();
	$count = 1;

	while (($line = fgetcsv($fp)) !== false) {
		if($count === 1) {
			// 1行めをヘッダとして格納
			$csv_data['header'] = $line;
		} else {
			// BackLog登録ID or ID をキーにする。
			$id = trim($line[0]);
			$csv_data[$id] = $line;
		}
		$count++;
	}
	fclose($fp);

	return $csv_data;
}


// jsonファイルから読み込んでjsonオブジェクトにする。
function file2json($path) {
	$fp = fopen($path, 'r');
	$json_data = json_decode(fread($fp, filesize($path)), true);
	return $json_data;
}


function save_file($body, $prefix, $ext) {
	// 履歴保存のため一旦ファイルに出力する。
	$output_file = DATA_DIR.$prefix.date("YmdHis").'.'.$ext;
	$fp = fopen($output_file, "w");
	fwrite($fp, $body);
	fclose($fp);

	return $output_file;
}


// 差分データをメール本文用に出力する。
function print_member_diff($diff) {
	$mail_body = '';
	foreach ($diff as $id => $value) {
		$title = $value['title'];


		$before = array_key_exists('before', $value) ? $value['before'] : false;
		$after  = array_key_exists('after', $value) ? $value['after'] : false;

		if(empty($before) && !empty($after)) {
			// 新規
			$mail_body .= '■' . $title . '【新規】' . PHP_EOL;
			foreach ($after as $column => $value) {
				$mail_body .= $column.': '.$after[$column].PHP_EOL;
			}
		} elseif(!empty($before) && empty($after)) {
			// 削除
			$mail_body .= '■' . $title . '【削除】' . PHP_EOL;
			foreach ($before as $column => $value) {
				$mail_body .= $column.': '.$before[$column].PHP_EOL;
			}
		} else {
			// 変更
			$mail_body .= '■' . $title . '【変更】' . PHP_EOL;
			foreach ($before as $column => $value) {
				$mail_body .= $column.': '.$before[$column].' → '.$after[$column].PHP_EOL;
			}
		}

		$mail_body .= PHP_EOL.PHP_EOL;
	}
	return $mail_body;
}


// 差分データをメール本文用に出力する。
function print_pj_diff($pj_diff) {
	$mail_body = '';
	foreach ($pj_diff as $id => $record) {
		$mail_body .= '■' . $id . PHP_EOL;
		if(array_key_exists('add', $record)) {
			foreach ($record['add'] as $value) {
				$mail_body .= '追加: ' . $value . PHP_EOL;
			}
		}
		if(array_key_exists('del', $record)) {
			foreach ($record['del'] as $value) {
				$mail_body .= '削除: ' . $value . PHP_EOL;
			}
		}
		$mail_body .= PHP_EOL.PHP_EOL;
	}

	return $mail_body;
}


// 設定ファイルの読み込み。対象ディレクトリ内の *.ini ファイルを全て読み込み配列に格納する。
function get_ini_data() {
	if(is_file(CONF_FILE) && preg_match("/.*\.ini$/", CONF_FILE) == true) {
		$ini_data = parse_ini_file(CONF_FILE);
	} else {
		throw new Exception("設定ファイルのオープンに失敗しました。");
		return false;
	}
	// キー文字列でソート
	ksort($ini_data);

	return $ini_data;
}


// 対象ディレクトリ内で保管日数を過ぎたファイルを削除する。
function remove_old_file($path) {
	$ext_array = array('csv', 'json');
	$limit_time = time() -  (FILE_KEEP_DAYS * 24 * 60 * 60);
	foreach ($ext_array as $ext) {
		foreach (glob($path.'*.'.$ext) as $filename) {
			$mtime = filemtime( $filename );
			if( $mtime < $limit_time ){
				unlink($filename);
			}
		}
	}
}


// "Set-Cookie: " から始まる行を削除する。
function remove_cookie($str) {
	$str = preg_replace("/^Set-Cookie: .*\n/m", "", $str);
	return $str;
}


// LFのみの改行を<br>に置換する。CR+LF の場合は置換しない。
function lf2br($str) {
	$str = preg_replace("/([^\r])\n+/", "\\1<br>", $str);
	return $str;
}


// 分割対象の文字列を explode() してから trim() する。
function explode_with_trim($delim, $str) {
	$array = explode($delim, $str);
	$array_trimed = array();
	foreach($array as $value) {
		$value = trim($value);
		array_push($array_trimed, $value);
	}
	return $array_trimed;
}


// メール本文にフッタを追加する
function add_mail_footer($mail_body) {
	$mail_body .= "\n\n\n";
	$mail_body .= "---------\n";
	$mail_body .= "\n";
	return $mail_body;
}


// 結果をレポート
function report($ini, $mail_body) {
	if(!empty($mail_body)) {
		// 変数初期化
		$mailto_array		= array();
		$mail_subject		= '';

		// Subject
		$mail_subject = $ini['mail_subject'];
		// 行末のタブを改行に置換
		$mail_body = preg_replace("/\t\n/m", "\n", $mail_body);
		// フッタ
		$mail_body = add_mail_footer($mail_body);
		// 送信先は複数設定されている場合がある
		$mailto_array = explode_with_trim(",", $ini['mailto']);
		// メール送信
		if(DEBUG_FLG) {
			mail_send_debug($mailto_array, $mail_subject, $mail_body);
		} else {
			mail_send($mailto_array, $mail_subject, $mail_body);
		}
	} else {
		print("前回からの差分はありませんでした。" . PHP_EOL);
	}
}


// メール送信
function mail_send($mailto_array, $mail_subject, $mail_body) {
	// これを指定しないとmb_encode_mimeheader()で正しくエンコードされない
	mb_language('ja');
	mb_internal_encoding('ISO-2022-JP');
	$params = array(
		//"host" => "mail.sf.cybird.ne.jp",
		"host" => "libml3.sf.cybird.ne.jp",
		"port" => 25,
		"auth" => false,
		"username" => "",
		"password" => ""
	);
	$mailObject = Mail::factory("smtp", $params);
	$headers = array(
		"From" => "nobody@cybird.co.jp",
		"Subject" => mb_encode_mimeheader(mb_convert_encoding($mail_subject, 'ISO-2022-JP', "SJIS"))
	);
	$mail_body = mb_convert_kana($mail_body, "K", "SJIS");
	$mail_body = mb_convert_encoding($mail_body, "ISO-2022-JP", "SJIS");
	$mailObject -> send($mailto_array, $headers, $mail_body);
	// 元に戻す
	mb_internal_encoding('SJIS');
}


// メール送信のテストとしてファイルに出力する
function mail_send_debug($mailto_array, $mail_subject, $mail_body) {
	$out = "";
	// データ整形
	$out .= "<mailto>\n";
	foreach($mailto_array as $mailto) {
		$out .= $mailto.", ";
	}
	$out .= "\n\n";
	$out .= "<subject>\n";
	$out .= $mail_subject;
	$out .= "\n\n";
	$out .= "<body>\n";
	$out .= $mail_body;
	$out .= "";

	// 標準出力
	print($out);

	return true;
}
