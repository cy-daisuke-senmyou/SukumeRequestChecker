<?php

//--------------------------------------
// ���ʐݒ�E�֐��̓ǂݍ���
//--------------------------------------

require_once("HTTP/Request2.php");
require_once("Mail.php");

// �f�o�b�O�t���O true �ɂ���ƃ��[�����M�͍s�킸���ʂ�W���o�͂ɕ\������B
define('DEBUG_FLG', false);
// �x�[�X�f�B���N�g��
define('BASE_DIR', '/home/cybird/SukumeRequestChecker/');
// �ݒ�t�@�C���̃f�B���N�g��
define('CONF_FILE', BASE_DIR.'conf/config.ini');
// CSV�t�@�C���̈ꎞ�o�͐�f�B���N�g��
define('DATA_DIR', 	BASE_DIR.'data/');
// ���[���o�͐�f�B���N�g��
define('MAIL_DIR', 	BASE_DIR.'mail/');
// �f�a�G�̃J�����ݒ�
define('PJ_ID_COL', 0);
define('PJ_NAME_COL', 1);
define('PJ_CODE_COL', 2);
define('PJ_CHARGE_COL', "5,10,15,20,25,30,35,40,45,50,55,60,65,70,75,80,85,90,95,100");
define('MEMBER_COL', "0, 1, 2, 3, 4");
define('MEMBER_NAME_COL', 1);
// �ꎞ�t�@�C���̕ۊǓ���
define('FILE_KEEP_DAYS', 7);

//--------------------------------------
// ����������
//--------------------------------------

// �ݒ�t�@�C���̓ǂݍ���
$ini = get_ini_data();

// ���{�ꃁ�[���𑗂�ۂɕK�v
mb_language("Japanese");
$mail_body = "";

//--------------------------------------
// ���s�G���A
//--------------------------------------
try {
	//////////////////////
	// �S���҃}�X�^�[
	//////////////////////
	$prefix = 'member_';
	$ext = 'csv';
	$latest_member_array = array();
	$current_member_array = array();

	// �ŏI�X�V���̒S���҃t�@�C�����擾���Ĕz��Ɋi�[����B
	$latest_member_file = get_latest_file(DATA_DIR, $prefix, $ext);
	$latest_member_array = csv2array($latest_member_file);

	// �f�a�G�ɃA�N�Z�X���āACSV���擾���z��Ɋi�[����B
	$dezie_member_data = get_dezie_data($ini, $prefix);
	$current_member_file = save_file($dezie_member_data, $prefix, $ext);
	$current_member_array = csv2array($current_member_file);

	// �S���҃}�X�^�[�̍������擾����B
	$member_diff = get_member_diff($latest_member_array, $current_member_array);
	if(!empty($member_diff)) {
		$mail_body .= "�y�X�N���S���҃}�X�^�[�z�ɂ����Ĉȉ��̃��R�[�h���ύX����܂����B\n\n";
		$mail_body .= print_member_diff($member_diff);
	}

	//////////////////////
	// PJ�}�X�^�[
	//////////////////////
	$prefix = 'pj_';
	$ext = 'csv';
	$latest_pj_array = array();
	$current_pj_array = array();

	// �ŏI�X�V����PJ�t�@�C�����擾���Ĕz��Ɋi�[����B
	$latest_pj_file = get_latest_file(DATA_DIR, $prefix, $ext);
	$latest_pj_array = csv2array($latest_pj_file);

	// �f�a�G�ɃA�N�Z�X���āACSV���擾���z��Ɋi�[����B
	$dezie_pj_data = get_dezie_data($ini, $prefix);
	$current_pj_file = save_file($dezie_pj_data, $prefix, $ext);
	$current_pj_array = csv2array($current_pj_file);

	//////////////////////
	// PJ�}�X�^�[
	//////////////////////
	$prefix = 'relation_';
	$ext = 'json';

	// �ŏI�X�V���̊֘A�t���t�@�C�����擾���Ĕz��Ɋi�[����B
	$latest_pj_file = get_latest_file(DATA_DIR, $prefix, $ext);
	$latest_relation_data = file2json($latest_pj_file);

	// �f�a�G�̃f�[�^���猻�݂̒S���҂�PJ�̊֘A�t��������B
	$current_relation_data = relate_pj_member($current_pj_array, $current_member_array);
	save_file(json_encode($current_relation_data), $prefix, $ext);

	// �֘A�t�����ꂽ�f�[�^�̍����𒊏o����B
	$pj_diff = get_pj_diff($latest_pj_array, $latest_relation_data, $latest_member_array, $current_pj_array, $current_relation_data, $current_member_array);
		if(!empty($pj_diff)) {
		$mail_body .= "�y�X�N��PJ�}�X�^�[�z�ɂ����Ĉȉ��̃��R�[�h���ύX����܂����B\n\n";
		$mail_body .= print_pj_diff($pj_diff);
	}

	// ���ʂ����|�[�g
	report($ini, $mail_body);



	// �Â��t�@�C�����폜
	remove_old_file(DATA_DIR);
} catch(Exception $e) {
	if(DEBUG_FLG) {
		print($e->getMessage().PHP_EOL);
	} else {
		mail_send($mailto_array, '�ySukumeRequestChecker�z ErrorOccurred.', $e->getMessage());
	}

}



//--------------------------------------
// private�֐��G���A
//--------------------------------------

// �ۊǂ���Ă���ŐV��CSV�ƁA�f�a�G����擾����CSV���r���č�����Ԃ��B
function get_member_diff($latest_data_array, $current_data_array) {
	$title_column = 1;
	$target = explode(',', MEMBER_COL);

	// �w�b�_�s�͌�Ŕz��Y�����Ɏg���B
	$header = $current_data_array['header'];

	// �Q�̔z����r���č����𒊏o����B
	$result = array();

	// �V�K���R�[�h
	$new_record_array = array_diff_key($current_data_array, $latest_data_array);
	foreach ($new_record_array as $key => $new_record) {
		$after = array();
		$result[$key]['title'] = $new_record[$title_column];
		foreach($new_record as $index => $value) {
			// �������o�Ώۂ̃J�����̏ꍇ�͕ʂ̔z��Ɍ��ʂ��i�[����B
			if(in_array($index, $target)) {
				$column = $header[$index];
				$after[$column] = $new_record[$index];
			}
		}
		$result[$key]['after'] = $after;
	}

	// �폜���R�[�h
	$deleted_record_array = array_diff_key($latest_data_array, $current_data_array);
	foreach ($deleted_record_array as $key => $deleted_record) {
		$before = array();
		$result[$key]['title'] = $deleted_record[$title_column];
		foreach($deleted_record as $index => $value) {
			// �������o�Ώۂ̃J�����̏ꍇ�͕ʂ̔z��Ɍ��ʂ��i�[����B
			if(in_array($index, $target)) {
				$column = $header[$index];
				$before[$column] = $deleted_record[$index];
			}
		}
		$result[$key]['before'] = $before;
	}


	// �����ɑ��݂��郌�R�[�h�̍����𒊏o
	// �f�a�GCSV���R�[�h�������[�v
	foreach($current_data_array as $current_data) {
		if(empty($current_data[0])) continue;
		$current_id = $current_data[0];
		// �ŏI�X�VCSV�t�@�C���̃��R�[�h�������[�v�B
		foreach($latest_data_array as $latest_data) {
			// ID��œ˂����킹������B
			$latest_id = $latest_data[0];
			if($latest_id === $current_id) {
				$array_diff = array_diff_assoc($current_data, $latest_data);
				if(!empty($array_diff)) {
					$before = array();
					$after = array();
					// �����v�f�������[�v
					foreach($array_diff as $index => $value) {
						// �������o�Ώۂ̃J�����̏ꍇ�͕ʂ̔z��Ɍ��ʂ��i�[����B
						if(in_array($index, $target)) {
							$column = $header[$index];
							$before[$column] = $latest_data[$index];
							$after[$column] = $current_data[$index];
						}
					}
					// ��������������resule�Ɋi�[����B
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


// �֘A�t�����ꂽ�f�[�^�̍����𒊏o����B
function get_pj_diff($latest_pj_array, $latest_relation_data, $latest_member_array, $current_pj_array, $current_relation_data, $current_member_array) {
	$result = array();
	// �ǉ�
	foreach ($current_relation_data as $id => $current_pj_line) {
		// ��r�Ώۂ����������Ă��Ȃ���array_diff()���@�\���Ȃ��̂ŋ�̔z�������B
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
	// �폜
	foreach ($latest_relation_data as $id => $latest_relation_line) {
		// ��r�Ώۂ����������Ă��Ȃ���array_diff()���@�\���Ȃ��̂ŋ�̔z�������B
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


// �S���҂�PJ�̊֘A�t��������B
function relate_pj_member($current_pj_array, $current_member_array) {
	// �����o�[�f�[�^�������[�v
	$relation_data = array();
	foreach ($current_member_array as $id => $member_info) {
		$relation_data[$id] = array();
		// PJ�f�[�^�������[�v
		foreach($current_pj_array as $pj_info) {
			// BacklogID�̃J����������
			foreach(explode(',', PJ_CHARGE_COL) as $index) {
				if($id == $pj_info[$index]) {
					$relation_data[$id][] = $pj_info[PJ_ID_COL];
				}
			}
		}
	}
	return $relation_data;
}


// �Ώۃf�B���N�g�����ōŐV�̃t�@�C�����擾����B
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


// �J�X�^���A�v���ɃA�N�Z�X���āACSV���擾���t�@�C���Ɋi�[����B
function get_dezie_data($ini, $prefix) {
	$url = $ini[$prefix.'dezie_url'];
	$body = "";

	$req = new HTTP_Request2($url);
	$req->setHeader('allowRedirects-Alive', true);   // ���_�C���N�g�̋��ݒ�(true/false)
	$req->setHeader('maxRedirects', 3);              // ���_�C���N�g�̍ő��

	$response = $req->send();
	if($response->getStatus() == 200) {
		$body = $response->getBody();
	}

	// �ʐM�G���[�A�����ݒ�̕ύX�Ȃǂ�����ƃG���[��ʂ��\�������B
	if(preg_match('/^<!DOCTYPE html>/', $body)) {
		throw new Exception("�f�a�G����̃f�[�^�擾�Ɏ��s���܂����B");
	}

	// �f�a�G����̃f�[�^�� Cookie �̃f�[�^�������Ă��܂��̂ō폜
	$body = remove_cookie($body);

	// �f�a�G����擾�������R�[�h�̒��ɉ��s���܂܂�Ă���B
	// ���R�[�h���̉��s�� LF �ōs���� CR+LF �Ȃ̂őO�҂���<br>�ɒu������B
	$body = lf2br($body);

	return $body;
}

// CSV�t�@�C����ǂݍ��ݔz��Ɋi�[����B
function csv2array($path) {
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


function save_file($body, $prefix, $ext) {
	// ����ۑ��̂��߈�U�t�@�C���ɏo�͂���B
	$output_file = DATA_DIR.$prefix.date("YmdHis").'.'.$ext;
	$fp = fopen($output_file, "w");
	fwrite($fp, $body);
	fclose($fp);

	return $output_file;
}


// �����f�[�^�����[���{���p�ɏo�͂���B
function print_member_diff($diff) {
	$mail_body = '';
	foreach ($diff as $id => $value) {
		$title = $value['title'];


		$before = array_key_exists('before', $value) ? $value['before'] : false;
		$after  = array_key_exists('after', $value) ? $value['after'] : false;

		if(empty($before) && !empty($after)) {
			// �V�K
			$mail_body .= '��' . $title . '�y�V�K�z' . PHP_EOL;
			foreach ($after as $column => $value) {
				$mail_body .= $column.': '.$after[$column].PHP_EOL;
			}
		} elseif(!empty($before) && empty($after)) {
			// �폜
			$mail_body .= '��' . $title . '�y�폜�z' . PHP_EOL;
			foreach ($before as $column => $value) {
				$mail_body .= $column.': '.$before[$column].PHP_EOL;
			}
		} else {
			// �ύX
			$mail_body .= '��' . $title . '�y�ύX�z' . PHP_EOL;
			foreach ($before as $column => $value) {
				$mail_body .= $column.': '.$before[$column].' �� '.$after[$column].PHP_EOL;
			}
		}

		$mail_body .= PHP_EOL.PHP_EOL;
	}
	return $mail_body;
}


// �����f�[�^�����[���{���p�ɏo�͂���B
function print_pj_diff($pj_diff) {
	$mail_body = '';
	foreach ($pj_diff as $id => $record) {
		$mail_body .= '��' . $id . PHP_EOL;
		if(array_key_exists('add', $record)) {
			foreach ($record['add'] as $value) {
				$mail_body .= '�ǉ�: ' . $value . PHP_EOL;
			}
		}
		if(array_key_exists('del', $record)) {
			foreach ($record['del'] as $value) {
				$mail_body .= '�폜: ' . $value . PHP_EOL;
			}
		}
		$mail_body .= PHP_EOL.PHP_EOL;
	}

	return $mail_body;
}


// �ݒ�t�@�C���̓ǂݍ��݁B�Ώۃf�B���N�g������ *.ini �t�@�C����S�ēǂݍ��ݔz��Ɋi�[����B
function get_ini_data() {
	if(is_file(CONF_FILE) && preg_match("/.*\.ini$/", CONF_FILE) == true) {
		$ini_data = parse_ini_file(CONF_FILE);
	} else {
		throw new Exception("�ݒ�t�@�C���̃I�[�v���Ɏ��s���܂����B");
		return false;
	}
	// �L�[������Ń\�[�g
	ksort($ini_data);

	return $ini_data;
}


// �Ώۃf�B���N�g�����ŕۊǓ������߂����t�@�C�����폜����B
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


// "Set-Cookie: " ����n�܂�s���폜����B
function remove_cookie($str) {
	$str = preg_replace("/^Set-Cookie: .*\n/m", "", $str);
	return $str;
}


// LF�݂̂̉��s��<br>�ɒu������BCR+LF �̏ꍇ�͒u�����Ȃ��B
function lf2br($str) {
	$str = preg_replace("/([^\r])\n+/", "\\1<br>", $str);
	return $str;
}


// �����Ώۂ̕������ explode() ���Ă��� trim() ����B
function explode_with_trim($delim, $str) {
	$array = explode($delim, $str);
	$array_trimed = array();
	foreach($array as $value) {
		$value = trim($value);
		array_push($array_trimed, $value);
	}
	return $array_trimed;
}


// ���[���{���Ƀt�b�^��ǉ�����
function add_mail_footer($mail_body) {
	$mail_body .= "\n\n\n";
	$mail_body .= "---------\n";
	$mail_body .= "\n";
	return $mail_body;
}


// ���ʂ����|�[�g
function report($ini, $mail_body) {
	if(!empty($mail_body)) {
		// �ϐ�������
		$mailto_array		= array();
		$mail_subject		= '';

		// Subject
		$mail_subject = $ini['mail_subject'];
		// �s���̃^�u�����s�ɒu��
		$mail_body = preg_replace("/\t\n/m", "\n", $mail_body);
		// �t�b�^
		$mail_body = add_mail_footer($mail_body);
		// ���M��͕����ݒ肳��Ă���ꍇ������
		$mailto_array = explode_with_trim(",", $ini['mailto']);
		// ���[�����M
		if(DEBUG_FLG) {
			mail_send_debug($mailto_array, $mail_subject, $mail_body);
		} else {
			mail_send($mailto_array, $mail_subject, $mail_body);
		}
	} else {
		print("�O�񂩂�̍����͂���܂���ł����B" . PHP_EOL);
	}
}


// ���[�����M
function mail_send($mailto_array, $mail_subject, $mail_body) {
	// ������w�肵�Ȃ���mb_encode_mimeheader()�Ő������G���R�[�h����Ȃ�
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
	// ���ɖ߂�
	mb_internal_encoding('SJIS');
}


// ���[�����M�̃e�X�g�Ƃ��ăt�@�C���ɏo�͂���
function mail_send_debug($mailto_array, $mail_subject, $mail_body) {
	$out = "";
	// �f�[�^���`
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

	// �W���o��
	print($out);

	return true;
}
