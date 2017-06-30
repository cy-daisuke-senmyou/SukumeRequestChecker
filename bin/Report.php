<?php

require_once("Mail.php");

class Report {
  private $config;

  public function __construct( $config ) {
    $this->config = $config;
    return true;
  }

  // 結果をレポート
  public function out($mail_body) {
    $debug = $this->config->get_param('debug');
    if(!empty($mail_body)) {
      // 変数初期化
      $mailto_array    = array();
      $mail_subject    = '';

      // Subject
      $mail_subject = $this->config->get_param('mail_subject');
      // 行末のタブを改行に置換
      $mail_body = preg_replace("/\t\n/m", "\n", $mail_body);
      // フッタ
      $mail_body = $this->add_mail_footer($mail_body);
      // 送信先は複数設定されている場合がある
      $mailto_array = $this->explode_with_trim(",", $this->config->get_param('mailto'));
      // メール送信
      if($debug) {
        $this->mail_send_debug($mailto_array, $mail_subject, $mail_body);
      } else {
        $this->mail_send($mailto_array, $mail_subject, $mail_body);
      }
    } else {
      print("前回からの差分はありませんでした。" . PHP_EOL);
    }
  }


  // メール送信
  private function mail_send($mailto_array, $mail_subject, $mail_body) {
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
      "From" => "noreply@cybird.ne.jp",
      "To" => implode(',', $mailto_array),
      "Subject" => mb_encode_mimeheader(mb_convert_encoding($mail_subject, 'ISO-2022-JP', "UTF-8"))
    );
    $mail_body = mb_convert_kana($mail_body, "K", "UTF-8");
    $mail_body = mb_convert_encoding($mail_body, "ISO-2022-JP", "UTF-8");
    $mailObject -> send($mailto_array, $headers, $mail_body);
    // 元に戻す
    mb_internal_encoding('SJIS');
  }


  // メール送信のテストとしてファイルに出力する
  private function mail_send_debug($mailto_array, $mail_subject, $mail_body) {
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


  // メール本文にフッタを追加する
  private function add_mail_footer($mail_body) {
    $mail_body .= "---------\n";
    $mail_body .= "\n";
    return $mail_body;
  }


  // 分割対象の文字列を explode() してから trim() する。
  private function explode_with_trim($delim, $str) {
    $array = explode($delim, $str);
    $array_trimed = array();
    foreach($array as $value) {
      $value = trim($value);
      array_push($array_trimed, $value);
    }
    return $array_trimed;
  }

}
