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
  private $subject = "�y�����R���e���c��Q�z���ύX����܂����B\n\n";

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
    // �ŏI�X�V���̊֘A�t���t�@�C�����擾���Ĕz��Ɋi�[����B
    $debug = $this->config->get_param('debug');
    $data_dir = $this->config->get_param('base_dir') . $this->config->get_param('data_dir');
    $latest_file = Util::get_latest_file($data_dir, $this->prefix . $this->mode, $this->ext, $debug);
    $this->latest_array = Util::simple_csv2array($latest_file);

    // �f�a�G�̃f�[�^���猻�݂̒S���҂�PJ�̊֘A�t��������B
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
          // �S�Ќ���
          $ml_list[] = $ml;
        } elseif( $this->mode == 'contents_' && $division == '1' ) {
          // �R���e���c���ƕ��v���W�F�N�g
          $ml_list[] = $ml;
        }
      }
    }
    sort($ml_list);
    $this->current_array = array_unique($ml_list);
    Util::save_file( implode(PHP_EOL, $this->current_array), $data_dir, $this->prefix . $this->mode, $this->ext);

    // �֘A�t�����ꂽ�f�[�^�̍����𒊏o����B
  	return $this->compare();
  }

  // �֘A�t�����ꂽ�f�[�^�̍����𒊏o����B
  private function compare() {
    // �V�K
    $new_record_array = array_diff( $this->current_array, $this->latest_array );
    if( !empty($new_record_array) ) {
      $this->diff['new'] = $new_record_array;
    }

    // �폜���R�[�h
  	$deleted_record_array = array_diff($this->latest_array, $this->current_array);
    if( !empty($deleted_record_array) ) {
      $this->diff['del'] = $deleted_record_array;
    }

    return true;
  }

  // �����f�[�^�����[���{���p�ɏo�͂���B
  public function print_diff() {
    $mail_body = '';

    if( !empty($this->diff) ) {

      if( $this->mode == 'all_') {
        // �S��
        $mail_body .= $this->subject;
        $mail_body .= '���S�Ќ��������R���e���c��Q' . PHP_EOL;
      } else {
        // �R���e���c���ƕ�
        $mail_body .= '���R���e���c���ƕ����������R���e���c��Q' . PHP_EOL;
      }

      foreach( $this->diff['new'] as $ml ) {
        $mail_body .= '�ǉ�: ' . $ml . PHP_EOL;
      }

      foreach( $this->diff['del'] as $ml ) {
        $mail_body .= '�폜: ' . $ml . PHP_EOL;
      }
      $mail_body .= PHP_EOL;
    }
    $mail_body .= PHP_EOL;

    return $mail_body;

  }
}
