<?php

class Util {

  // �Ώۃf�B���N�g�����ōŐV�̃t�@�C�����擾����B
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

  // CSV�t�@�C����ǂݍ��ݔz��Ɋi�[����B
  public static function csv2array($path) {
  	$fp = fopen($path, "r");
  	$csv_data = array();
  	$count = 1;

  	while (($line = fgetcsv($fp)) !== false) {
  		if($count === 1) {
  			// 1�s�߂��w�b�_�Ƃ��Ċi�[
  			$csv_data['header'] = $line;
  		} else {
  			// BackLog�o�^ID or ID ���L�[�ɂ���B
  			$id = trim($line[0]);
  			$csv_data[$id] = $line;
  		}
  		$count++;
  	}
  	fclose($fp);

  	return $csv_data;
  }

  // json�t�@�C������ǂݍ����json�I�u�W�F�N�g�ɂ���B
  function file2json($path) {
  	$fp = fopen($path, 'r');
  	$json_data = json_decode(fread($fp, filesize($path)), true);
  	return $json_data;
  }

  public static function save_file($body, $path, $prefix, $ext) {
  	// ����ۑ��̂��߈�U�t�@�C���ɏo�͂���B
  	$output_file = $path.$prefix.date("YmdHis").'.'.$ext;
  	$fp = fopen($output_file, "w");
  	fwrite($fp, $body);
  	fclose($fp);

  	return $output_file;
  }


  // �Ώۃf�B���N�g�����ŕۊǓ������߂����t�@�C�����폜����B
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
