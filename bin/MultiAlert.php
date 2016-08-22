<?php

require_once("Util.php");

class MultiAlert {
  private $prefix = 'multialert_';
  private $mode = '';
	private $ext = 'csv';
  private $config;
  private $project;
  private $member;
  private $diff = array();

  private $division_col = 3;
  private $ml_col       = 4;
  private $member_col   = array(6, 11, 16, 21, 26, 31, 36, 41, 46, 51, 56, 61, 66, 71, 76, 81, 86, 91, 96, 101);
  private $subject = "�y�����R���e���c��Q�z���ύX����܂����B\n\n";

  private $latest_array = array();
  private $current_array = array();
  private $latest_file;
  private $current_file;


  public function __construct( $config ) {
    $this->config = $config;
    return true;
  }

  public function set_project( $project ) {
    $this->project = $project;
    return true;
  }

  public function set_member( $member ) {
    $this->member = $member;
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
    $this->latest_file = Util::get_latest_file($data_dir, $this->prefix . $this->mode, $this->ext, $debug);
    $this->latest_array = Util::simple_csv2array($this->latest_file);

    // �f�a�G�̃f�[�^���猻�݂̒S���҂�PJ�̊֘A�t��������B
    $to_list = array();
    foreach( $this->project->current_array as $key => $pj_row ) {
      if($key === 'header') continue;
      $division = $pj_row[$this->division_col];

      // �z�M��ML�J�����̎擾
      $ml_row = $pj_row[$this->ml_col];
      $ml_exploded = $this->validate_address( $ml_row );
      foreach( $ml_exploded as $ml ) {
        $ml = trim(mb_convert_kana($ml, "s"));
        $ml = trim($ml);
        if(empty($ml)) continue;
        if( $this->mode == 'all_') {
          // �S�Ќ���
          $to_list[] = $ml;
        } elseif( $this->mode == 'contents_' && $division == '1' ) {
          // �R���e���c���ƕ��v���W�F�N�g
          $to_list[] = $ml;
        }
      }

      // �S���҃J�����̎擾
      foreach( $this->member_col as $col ) {
        $member_id = $pj_row[$col];
        if(empty($member_id)) continue;

        if( $this->mode == 'all_') {
          // �S�Ќ���
          $to = $this->validate_address( $this->member->current_array[$member_id][2] );   // PC���A�h
          $to_list = array_merge($to_list, $to);
          $to = $this->validate_address( $this->member->current_array[$member_id][3] );   // �g�у��A�h
          $to_list = array_merge($to_list, $to);
        } elseif( $this->mode == 'contents_' && $division == '1' ) {
          // �R���e���c���ƕ��v���W�F�N�g
          $to = $this->validate_address( $this->member->current_array[$member_id][2] );   // PC���A�h
          $to_list = array_merge($to_list, $to);
          $to = $this->validate_address( $this->member->current_array[$member_id][3] );   // �g�у��A�h
          $to_list = array_merge($to_list, $to);
        }
      }
    }

    sort($to_list);
    $this->current_array = array_unique($to_list);
    $this->current_file = Util::save_file( implode(PHP_EOL, $this->current_array), $data_dir, $this->prefix . $this->mode, $this->ext);

    // �֘A�t�����ꂽ�f�[�^�̍����𒊏o����B
  	return $this->compare();
  }

  // �f�a�G�̂P�J�����ɕ������R�[�h�������Ă���ꍇ�ɕ�������
  private function validate_address($str) {
    $result = array();
    $exploded = explode( ',', $str );
    foreach( $exploded as $address ) {
      $address = mb_ereg_replace ('[^0-9a-z_./?\-@]', '', $address);
      if(!empty($address)) {
        $result[] = $address;
      }
    }
    return $result;
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

      if( isset($this->diff['new']) ) {
        foreach( $this->diff['new'] as $ml ) {
          $mail_body .= '�ǉ�: ' . $ml . PHP_EOL;
        }
      }

      if( isset($this->diff['del']) ) {
        foreach( $this->diff['del'] as $ml ) {
          $mail_body .= '�폜: ' . $ml . PHP_EOL;
        }
      }

      $mail_body .= PHP_EOL.PHP_EOL;
    }

    return $mail_body;

  }

  // �ُ�I�����̃t�@�C���폜
  public function remove() {
    if(file_exists($this->current_file)) {
      unlink($this->current_file);
    }
  }
}
