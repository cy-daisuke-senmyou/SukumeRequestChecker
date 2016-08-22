<?php

require_once("HTTP/Request2.php");

class Dezie {
  private $config = array();

  public function __construct( $config ) {
    $this->config = $config;
    return true;
  }

  // デヂエにアクセスして、CSVを取得しファイルに格納する。
  function get_data( $prefix) {
  	$url = $this->config->get_param( $prefix.'dezie_url' );
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
  	$body = $this->remove_cookie($body);

  	// デヂエから取得したレコードの中に改行が含まれている。
  	// レコード中の改行は LF で行末は CR+LF なので前者だけ<br>に置換する。
  	$body = $this->lf2br($body);

  	return $body;
  }

  // "Set-Cookie: " から始まる行を削除する。
  private function remove_cookie($str) {
  	$str = preg_replace("/^Set-Cookie: .*\n/m", "", $str);
  	return $str;
  }


  // LFのみの改行を<br>に置換する。CR+LF の場合は置換しない。
  private function lf2br($str) {
  	$str = preg_replace("/([^\r])\n+/", "\\1, ", $str);
  	return $str;
  }


}
