<?php

require_once("Master.php");

class Project extends Master {
  protected $prefix = 'pj_';
	protected $ext = 'csv';
  protected $target_col = array(4, 5);
  protected $title_col = 1;
  protected $subject = "【スクメPJマスター】において以下のレコードが変更されました。\n\n";

  // 差分データをメール本文用に出力する。
  public function print_diff() {
    $mail_body = '';

    if( !empty($this->diff) ) {
      $mail_body .= $this->subject;

      foreach ($this->diff as $id => $value) {
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
          $mail_body .= '■' . $title . ' が無効化されました。' . PHP_EOL;
          $mail_body .= 'ステータス: 運用中 → 無効'.PHP_EOL;
        } else {
          // 変更
          $mail_body .= '■' . $title . '【変更】' . PHP_EOL;
          foreach ($before as $column => $value) {
            $mail_body .= $column.': '.$before[$column].' → '.$after[$column].PHP_EOL;
          }
        }
        $mail_body .= PHP_EOL;
      }
      $mail_body .= PHP_EOL.PHP_EOL;
    }

    return $mail_body;
  }
}
