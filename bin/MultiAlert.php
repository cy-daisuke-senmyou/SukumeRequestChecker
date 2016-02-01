<?php

require_once("Util.php");

class MultiAlert {
  private $prefix = 'multialert_';
	private $ext = 'json';
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

  public function get_diff() {
    // 最終更新日の関連付けファイルを取得して配列に格納する。
    $debug = $this->config->get_param('debug');
    $data_dir = $this->config->get_param('base_dir') . $this->config->get_param('data_dir');
    $latest_file = Util::get_latest_file($data_dir, $this->prefix, $this->ext, $debug);
    $this->latest_array = Util::file2json($latest_file);

    // デヂエのデータから現在の担当者とPJの関連付けをする。
    $contents = array();
    $all = array();
    foreach( $this->project->current_array as $key => $pj_row ) {
      if($key === 'header') continue;
      $division = $pj_row[$this->division_col];
      $ml_row = $pj_row[$this->ml_col];
      $ml_exploded = explode( ',', $ml_row );
      foreach( $ml_exploded as $ml ) {
        $ml = trim(mb_convert_kana($ml, "s"));
        $ml = trim($ml);
        if(empty($ml)) continue;
        // コンテンツ事業部プロジェクト
        if($division == '1') {
          $contents[] = $ml;
        }
        // 全社
        $all[] = $ml;
      }
    }
    sort($contents);
    sort($all);
    $this->current_array['contents'] = array_unique($contents);
    $this->current_array['all'] = array_unique($all);
    Util::save_file( json_encode($this->current_array), $data_dir, $this->prefix, $this->ext);

    // 関連付けされたデータの差分を抽出する。
  	return $this->compare();
  }

  // 関連付けされたデータの差分を抽出する。
  private function compare() {
    // 差分の内容は問わない、差分があるかどうかだけ判定する。
    $this->diff += array_diff( $this->latest_array['contents'], $this->current_array['contents'] );
    $this->diff += array_diff( $this->current_array['contents'], $this->latest_array['contents'] );
    $this->diff += array_diff( $this->latest_array['all'], $this->current_array['all'] );
    $this->diff += array_diff( $this->current_array['all'], $this->latest_array['all'] );
  }

  // 差分データをメール本文用に出力する。
  public function print_diff() {
    $mail_body = '';

    if( !empty($this->diff) ) {
      $mail_body .= $this->subject;

      // 全社
      $mail_body .= '■全社向け複数コンテンツ障害' . PHP_EOL;
      foreach( $this->current_array['all'] as $ml ) {
        $mail_body .= $ml . PHP_EOL;
      }
      $mail_body .= PHP_EOL;

      // コンテンツ事業部向け
      $mail_body .= '■コンテンツ事業部向け複数コンテンツ障害' . PHP_EOL;
      foreach( $this->current_array['contents'] as $ml ) {
        $mail_body .= $ml . PHP_EOL;
      }
      $mail_body .= PHP_EOL;
    }
    return $mail_body;

  }
}
