<?php

require_once("Util.php");

class Relation {
  private $prefix = 'relation_';
	private $ext = 'json';
  private $config;
  private $member;
  private $project;
  private $diff = array();

  private $relate_col = array(6, 11, 16, 21, 26, 31, 36, 41, 46, 51, 56, 61, 66, 71, 76, 81, 86, 91, 96, 101);
  private $pj_id_col = 0;
  private $pj_name_col = 1;
  private $pj_code_col = 2;
  private $member_name_col = 1;
  private $subject_diff     = "【スクメ担当者の割り当て】が変更されました。\n\n";
  private $subject_validate = "【スクメPJマスター】において不正な担当者データが検出されました。\n\n";

  private $latest_relation_data = array();
  private $current_relation_data = array();
  private $latest_relation_file;
  private $current_file;


  public function __construct( $config ) {
    $this->config = $config;
    return true;
  }

  public function set_member($member) {
    $this->member = $member;
    return true;
  }

  public function set_project($project) {
    $this->project = $project;
    return true;
  }

  // BackLogIDが入力されているにもかかわらず担当者名が未入力のデータを検出する。
  public function validate() {
    $mail_body = '';
    // PJデータ数分ループ
    foreach( $this->project->current_array as $pj_info ) {
      // BacklogIDのカラムを検索
      foreach($this->relate_col as $index_id) {
        // ID列の次が担当者名
        $index_name = $index_id + 1;
        if( !empty($pj_info[$index_id]) && empty($pj_info[$index_name]) ) {
          $mail_body .= $pj_info[$this->pj_code_col] . '(' . $pj_info[$this->pj_name_col] . ')' . PHP_EOL;
          continue;
        }
      }
    }
    if(!empty($mail_body)) {
      $mail_body = $this->subject_validate . $mail_body . PHP_EOL.PHP_EOL;
    }
    return $mail_body;
  }


  public function get_diff() {
    // 最終更新日の関連付けファイルを取得して配列に格納する。
    $debug = $this->config->get_param('debug');
    $data_dir = $this->config->get_param('base_dir') . $this->config->get_param('data_dir');
  	$this->latest_relation_file = Util::get_latest_file($data_dir, $this->prefix, $this->ext, $debug);
  	$this->latest_relation_data = Util::file2json($this->latest_relation_file);

  	// デヂエのデータから現在の担当者とPJの関連付けをする。
  	$this->current_relation_data = $this->relate_pj_member($this->project->current_array, $this->member->current_array);
  	$this->current_file = Util::save_file(json_encode($this->current_relation_data), $data_dir, $this->prefix, $this->ext);

  	// 関連付けされたデータの差分を抽出する。
  	return $this->compare();
  }


  // 差分データをメール本文用に出力する。
  public function print_diff() {
  	$mail_body = '';

    if( !empty($this->diff) ) {
  		$mail_body .= $this->subject_diff;

    	foreach ($this->diff as $id => $record) {
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
      $mail_body .= PHP_EOL;
    }
  	return $mail_body;
  }


  // 担当者とPJの関連付けをする。
  private function relate_pj_member($current_pj_array, $current_member_array) {
  	// メンバーデータ数分ループ
  	$relation_data = array();
  	foreach ($current_member_array as $id => $member_info) {
  		$relation_data[$id] = array();
  		// PJデータ数分ループ
  		foreach($current_pj_array as $pj_info) {
  			// BacklogIDのカラムを検索
  			foreach($this->relate_col as $index) {
  				if($id == $pj_info[$index]) {
  					$relation_data[$id][] = $pj_info[$this->pj_id_col];
  				}
  			}
  		}
  	}
  	return $relation_data;
  }


  // 関連付けされたデータの差分を抽出する。
  private function compare() {
  	// 追加
  	foreach ($this->current_relation_data as $id => $current_pj_line) {
  		// 比較対象が両方揃っていないとarray_diff()が機能しないので空の配列をつくる。
  		if(!isset($this->latest_relation_data[$id])) {
  			$this->latest_relation_data[$id] = array();
  		}
  		$new_record_array = array_diff($current_pj_line, $this->latest_relation_data[$id]);
  		if (!empty($new_record_array)) {
  			foreach ($new_record_array as $pj_id) {
  				$member_name = $this->member->current_array[$id][$this->member_name_col] . "($id)";
  				$pj_code = $this->project->current_array[$pj_id][$this->pj_code_col];
  				$pj_name = $this->project->current_array[$pj_id][$this->pj_name_col];
  				$this->diff[$member_name]['add'][] = $pj_code . '(' . $pj_name . ')';
  			}
  		}
  	}
  	// 削除
  	foreach ($this->latest_relation_data as $id => $latest_relation_line) {
  		// 比較対象が両方揃っていないとarray_diff()が機能しないので空の配列をつくる。
  		if(!isset($this->current_relation_data[$id])) {
  			$this->current_relation_data[$id] = array();
  		}
  		$deleted_record_array = array_diff($latest_relation_line, $this->current_relation_data[$id]);
  		if (!empty($deleted_record_array)) {
  			foreach ($deleted_record_array as $pj_id) {
  				$member_name = $this->member->latest_array[$id][$this->member_name_col] . "($id)";
  				$pj_code = $this->project->latest_array[$pj_id][$this->pj_code_col];
  				$pj_name = $this->project->latest_array[$pj_id][$this->pj_name_col];
  				$this->diff[$member_name]['del'][] = $pj_code . '(' . $pj_name . ')';
  			}
  		}
  	}

  	return true;
  }


  // 異常終了時のファイル削除
  public function remove() {
    if(file_exists($this->current_file)) {
      unlink($this->current_file);
    }
  }
}
