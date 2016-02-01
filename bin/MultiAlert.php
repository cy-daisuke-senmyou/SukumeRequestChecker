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

  public function get_diff() {
    // �ŏI�X�V���̊֘A�t���t�@�C�����擾���Ĕz��Ɋi�[����B
    $debug = $this->config->get_param('debug');
    $data_dir = $this->config->get_param('base_dir') . $this->config->get_param('data_dir');
    $latest_file = Util::get_latest_file($data_dir, $this->prefix, $this->ext, $debug);
    $this->latest_array = Util::file2json($latest_file);

    // �f�a�G�̃f�[�^���猻�݂̒S���҂�PJ�̊֘A�t��������B
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
        // �R���e���c���ƕ��v���W�F�N�g
        if($division == '1') {
          $contents[] = $ml;
        }
        // �S��
        $all[] = $ml;
      }
    }
    sort($contents);
    sort($all);
    $this->current_array['contents'] = array_unique($contents);
    $this->current_array['all'] = array_unique($all);
    Util::save_file( json_encode($this->current_array), $data_dir, $this->prefix, $this->ext);

    // �֘A�t�����ꂽ�f�[�^�̍����𒊏o����B
  	return $this->compare();
  }

  // �֘A�t�����ꂽ�f�[�^�̍����𒊏o����B
  private function compare() {
    // �����̓��e�͖��Ȃ��A���������邩�ǂ����������肷��B
    $this->diff += array_diff( $this->latest_array['contents'], $this->current_array['contents'] );
    $this->diff += array_diff( $this->current_array['contents'], $this->latest_array['contents'] );
    $this->diff += array_diff( $this->latest_array['all'], $this->current_array['all'] );
    $this->diff += array_diff( $this->current_array['all'], $this->latest_array['all'] );
  }

  // �����f�[�^�����[���{���p�ɏo�͂���B
  public function print_diff() {
    $mail_body = '';

    if( !empty($this->diff) ) {
      $mail_body .= $this->subject;

      // �S��
      $mail_body .= '���S�Ќ��������R���e���c��Q' . PHP_EOL;
      foreach( $this->current_array['all'] as $ml ) {
        $mail_body .= $ml . PHP_EOL;
      }
      $mail_body .= PHP_EOL;

      // �R���e���c���ƕ�����
      $mail_body .= '���R���e���c���ƕ����������R���e���c��Q' . PHP_EOL;
      foreach( $this->current_array['contents'] as $ml ) {
        $mail_body .= $ml . PHP_EOL;
      }
      $mail_body .= PHP_EOL;
    }
    return $mail_body;

  }
}
