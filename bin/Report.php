<?php

require_once("Mail.php");

class Report {
  private $config;

  public function __construct( $config ) {
    $this->config = $config;
    return true;
  }

  // ���ʂ����|�[�g
  public function out($mail_body) {
    $debug = $this->config->get_param('debug');
  	if(!empty($mail_body)) {
  		// �ϐ�������
  		$mailto_array		= array();
  		$mail_subject		= '';

  		// Subject
  		$mail_subject = $this->config->get_param('mail_subject');
  		// �s���̃^�u�����s�ɒu��
  		$mail_body = preg_replace("/\t\n/m", "\n", $mail_body);
  		// �t�b�^
  		$mail_body = $this->add_mail_footer($mail_body);
  		// ���M��͕����ݒ肳��Ă���ꍇ������
  		$mailto_array = $this->explode_with_trim(",", $this->config->get_param('mailto'));
  		// ���[�����M
  		if($debug) {
  			$this->mail_send_debug($mailto_array, $mail_subject, $mail_body);
  		} else {
  			$this->mail_send($mailto_array, $mail_subject, $mail_body);
  		}
  	} else {
  		print("�O�񂩂�̍����͂���܂���ł����B" . PHP_EOL);
  	}
  }


  // ���[�����M
  private function mail_send($mailto_array, $mail_subject, $mail_body) {
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
  		"From" => "noreply@cybird.ne.jp",
      "To" => implode(',', $mailto_array),
  		"Subject" => mb_encode_mimeheader(mb_convert_encoding($mail_subject, 'ISO-2022-JP', "SJIS"))
  	);
  	$mail_body = mb_convert_kana($mail_body, "K", "SJIS");
  	$mail_body = mb_convert_encoding($mail_body, "ISO-2022-JP", "SJIS");
  	$mailObject -> send($mailto_array, $headers, $mail_body);
  	// ���ɖ߂�
  	mb_internal_encoding('SJIS');
  }


  // ���[�����M�̃e�X�g�Ƃ��ăt�@�C���ɏo�͂���
  private function mail_send_debug($mailto_array, $mail_subject, $mail_body) {
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


  // ���[���{���Ƀt�b�^��ǉ�����
  private function add_mail_footer($mail_body) {
  	$mail_body .= "---------\n";
  	$mail_body .= "\n";
  	return $mail_body;
  }


  // �����Ώۂ̕������ explode() ���Ă��� trim() ����B
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
