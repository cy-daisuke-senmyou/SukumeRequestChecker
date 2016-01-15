<?php

class Util {

  // 対象ディレクトリ内で最新のファイルを取得する。
  public static function get_latest_file($path, $prefix, $ext, $debug) {
  	$latest_mtime = 0;
  	$latest_file = '';
  	foreach (glob($path.$prefix.'*.'.$ext) as $filename) {
  		$mtime = filemtime( $filename );
  		if( $mtime > $latest_mtime ){
  			$latest_mtime = $mtime;
  			$latest_file = $filename;
  		}
  	}

  	if($debug) {
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

  // CSVファイルを読み込み配列に格納する。
  public static function csv2array($path) {
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

  public static function save_file($body, $path, $prefix, $ext) {
  	// 履歴保存のため一旦ファイルに出力する。
  	$output_file = $path.$prefix.date("YmdHis").'.'.$ext;
  	$fp = fopen($output_file, "w");
  	fwrite($fp, $body);
  	fclose($fp);

  	return $output_file;
  }


  // 対象ディレクトリ内で保管日数を過ぎたファイルを削除する。
  public static function remove_old_file( $path, $file_keep_days ) {
  	$ext_array = array('csv', 'json');
  	$limit_time = time() -  ($file_keep_days * 24 * 60 * 60);
  	foreach ($ext_array as $ext) {
  		foreach (glob($path.'*.'.$ext) as $filename) {
  			$mtime = filemtime( $filename );
  			if( $mtime < $limit_time ){
  				unlink($filename);
  			}
  		}
  	}
  }

}
