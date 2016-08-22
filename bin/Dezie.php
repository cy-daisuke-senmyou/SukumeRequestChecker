<?php

require_once("HTTP/Request2.php");

class Dezie {
  private $config = array();

  public function __construct( $config ) {
    $this->config = $config;
    return true;
  }

  // �f�a�G�ɃA�N�Z�X���āACSV���擾���t�@�C���Ɋi�[����B
  function get_data( $prefix) {
  	$url = $this->config->get_param( $prefix.'dezie_url' );
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
  	$body = $this->remove_cookie($body);

  	// �f�a�G����擾�������R�[�h�̒��ɉ��s���܂܂�Ă���B
  	// ���R�[�h���̉��s�� LF �ōs���� CR+LF �Ȃ̂őO�҂���<br>�ɒu������B
  	$body = $this->lf2br($body);

  	return $body;
  }

  // "Set-Cookie: " ����n�܂�s���폜����B
  private function remove_cookie($str) {
  	$str = preg_replace("/^Set-Cookie: .*\n/m", "", $str);
  	return $str;
  }


  // LF�݂̂̉��s��<br>�ɒu������BCR+LF �̏ꍇ�͒u�����Ȃ��B
  private function lf2br($str) {
  	$str = preg_replace("/([^\r])\n+/", "\\1, ", $str);
  	return $str;
  }


}
