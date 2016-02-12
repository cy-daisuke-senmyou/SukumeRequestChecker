<?php

require_once("Util.php");

class MultiAlert {
  private $prefix = 'multialert_';
  private $mode = '';
	private $ext = 'csv';
  private $config;
  private $project;
  private $diff = array();

  private $division_col = 3;
  private $ml_col       = 4;
  private $subject = "【複数コンテンツ障害】が変更されました。\n\n";

  private $latest_array = array();
  private $current_array = array();


  public function __construct( $config ) {
    $this->config = $config;
    return true;
  }

  public function set_project( $project ) {
    $this->project = $project;
    return true;
  }

  public function set_mode( $mode ) {
    $this->mode = $mode . '_';
    return true;
  }

  public function get_diff() {
    // 最終更新日の関連付けファイルを取得して配列に格納する。
    $debug = $this->config->get_param('debug');
    $data_dir = $this->config->get_param('base_dir') . $this->config->get_param('data_dir');
    $latest_file = Util::get_latest_file($data_dir, $this->prefix . $this->mode, $this->ext, $debug);
    $this->latest_array = Util::simple_csv2array($latest_file);

    // デヂエのデータから現在の担当者とPJの関連付けをする。
    $ml_list = array();
    foreach( $this->project->current_array as $key => $pj_row ) {
      if($key === 'header') continue;
      $division = $pj_row[$this->division_col];
      $ml_row = $pj_row[$this->ml_col];
      $ml_exploded = explode( ',', $ml_row );
      foreach( $ml_exploded as $ml ) {
        $ml = trim(mb_convert_kana($ml, "s"));
        $ml = trim($ml);
        if(empty($ml)) continue;
        if( $this->mode == 'all_') {
          // 全社向け
          $ml_list[] = $ml;
        } elseif( $this->mode == 'contents_' && $division == '1' ) {
          // コンテンツ事業部プロジェクト
          $ml_list[] = $ml;
        }
      }
    }
    sort($ml_list);
    $this->current_array = array_unique($ml_list);
    Util::save_file( implode(PHP_EOL, $this->current_array), $data_dir, $this->prefix . $this->mode, $this->ext);

    // 関連付けされたデータの差分を抽出する。
  	return $this->compare();
  }

  // 関連付けされたデータの差分を抽出する。
  private function compare() {
    // 新規
    $new_record_array = array_diff( $this->current_array, $this->latest_array );
    if( !empty($new_record_array) ) {
      $this->diff['new'] = $new_record_array;
    }

    // 削除レコード
  	$deleted_record_array = array_diff($this->latest_array, $this->current_array);
    if( !empty($deleted_record_array) ) {
      $this->diff['del'] = $deleted_record_array;
    }

    return true;
  }

  // 差分データをメール本文用に出力する。
  public function print_diff() {
    $mail_body = '';

    if( !empty($this->diff) ) {

      if( $this->mode == 'all_') {
        // 全社
        $mail_body .= $this->subject;
        $mail_body .= '■全社向け複数コンテンツ障害' . PHP_EOL;
      } else {
        // コンテンツ事業部
        $mail_body .= '■コンテンツ事業部向け複数コンテンツ障害' . PHP_EOL;
      }

      foreach( $this->diff['new'] as $ml ) {
        $mail_body .= '追加: ' . $ml . PHP_EOL;
      }

      foreach( $this->diff['del'] as $ml ) {
        $mail_body .= '削除: ' . $ml . PHP_EOL;
      }
      $mail_body .= PHP_EOL.PHP_EOL;
    }

    return $mail_body;

  }
}
