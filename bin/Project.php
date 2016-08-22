<?php

require_once("Master.php");

class Project extends Master {
  protected $prefix = 'pj_';
	protected $ext = 'csv';
  protected $target_col = array(4, 5);
  protected $title_col = 1;
  protected $subject = "�y�X�N��PJ�}�X�^�[�z�ɂ����Ĉȉ��̃��R�[�h���ύX����܂����B\n\n";

  // �����f�[�^�����[���{���p�ɏo�͂���B
  public function print_diff() {
    $mail_body = '';

    if( !empty($this->diff) ) {
      $mail_body .= $this->subject;

      foreach ($this->diff as $id => $value) {
        $title = $value['title'];
        $before = array_key_exists('before', $value) ? $value['before'] : false;
        $after  = array_key_exists('after', $value) ? $value['after'] : false;

        if(empty($before) && !empty($after)) {
          // �V�K
          $mail_body .= '��' . $title . '�y�V�K�z' . PHP_EOL;
          foreach ($after as $column => $value) {
            $mail_body .= $column.': '.$after[$column].PHP_EOL;
          }
        } elseif(!empty($before) && empty($after)) {
          // �폜
          $mail_body .= '��' . $title . ' ������������܂����B' . PHP_EOL;
          $mail_body .= '�X�e�[�^�X: �^�p�� �� ����'.PHP_EOL;
        } else {
          // �ύX
          $mail_body .= '��' . $title . '�y�ύX�z' . PHP_EOL;
          foreach ($before as $column => $value) {
            $mail_body .= $column.': '.$before[$column].' �� '.$after[$column].PHP_EOL;
          }
        }
        $mail_body .= PHP_EOL;
      }
      $mail_body .= PHP_EOL.PHP_EOL;
    }

    return $mail_body;
  }
}
