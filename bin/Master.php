<?php

require_once("Util.php");
require_once("Dezie.php");

class Master {
  public $latest_array = array();
	public $current_array = array();

  protected $config = array();
  protected $diff = array();

  protected $prefix = '';
  protected $ext = '';
  protected $target_col = array();
  protected $title_col = 0;
  protected $subject = '';

  private $latest_file;
  private $current_file;

  public function __construct( $config ) {
    $this->config = $config;
    return true;
  }


  public function get_diff() {
    // �ŏI�X�V���̒S���҃t�@�C�����擾���Ĕz��Ɋi�[����B
    $debug = $this->config->get_param('debug');
    $data_dir = $this->config->get_param('base_dir') . $this->config->get_param('data_dir');
  	$this->latest_file = Util::get_latest_file( $data_dir, $this->prefix, $this->ext, $debug );
  	$this->latest_array = Util::csv2array($this->latest_file);

  	// �f�a�G�ɃA�N�Z�X���āACSV���擾���z��Ɋi�[����B
    $dezie = new Dezie( $this->config );
  	$dezie_data = $dezie->get_data( $this->prefix );
  	$this->current_file = Util::save_file( $dezie_data, $data_dir, $this->prefix, $this->ext );
  	$this->current_array = Util::csv2array($this->current_file);

  	// �S���҃}�X�^�[�̍������擾����B
  	return $this->compare();
  }

  // �ۊǂ���Ă���ŐV��CSV�ƁA�f�a�G����擾����CSV���r���č�����Ԃ��B
  private function compare() {
  	// �w�b�_�s�͌�Ŕz��Y�����Ɏg���B
  	$header = $this->current_array['header'];

  	// �Q�̔z����r���č����𒊏o����B
  	$diff = array();

  	// �V�K���R�[�h
  	$new_record_array = array_diff_key($this->current_array, $this->latest_array);
  	foreach ($new_record_array as $key => $new_record) {
  		$after = array();
  		$diff[$key]['title'] = $new_record[$this->title_col];
  		foreach($new_record as $index => $value) {
  			// �������o�Ώۂ̃J�����̏ꍇ�͕ʂ̔z��Ɍ��ʂ��i�[����B
  			if(in_array($index, $this->target_col)) {
  				$column = $header[$index];
  				$after[$column] = $new_record[$index];
  			}
  		}
  		$diff[$key]['after'] = $after;
  	}

  	// �폜���R�[�h
  	$deleted_record_array = array_diff_key($this->latest_array, $this->current_array);
  	foreach ($deleted_record_array as $key => $deleted_record) {
  		$before = array();
  		$diff[$key]['title'] = $deleted_record[$this->title_col];
  		foreach($deleted_record as $index => $value) {
  			// �������o�Ώۂ̃J�����̏ꍇ�͕ʂ̔z��Ɍ��ʂ��i�[����B
  			if(in_array($index, $this->target_col)) {
  				$column = $header[$index];
  				$before[$column] = $deleted_record[$index];
  			}
  		}
  		$diff[$key]['before'] = $before;
  	}

  	// �����ɑ��݂��郌�R�[�h�̍����𒊏o
  	// �f�a�GCSV���R�[�h�������[�v
  	foreach($this->current_array as $current_data) {
  		if(empty($current_data[0])) continue;
  		$current_id = $current_data[0];
  		// �ŏI�X�VCSV�t�@�C���̃��R�[�h�������[�v�B
  		foreach($this->latest_array as $latest_data) {
  			// ID��œ˂����킹������B
  			$latest_id = $latest_data[0];
  			if($latest_id === $current_id) {
  				$array_diff = array_diff_assoc($current_data, $latest_data);
  				if(!empty($array_diff)) {
  					$before = array();
  					$after = array();
  					// �����v�f�������[�v
  					foreach($array_diff as $index => $value) {
  						// �������o�Ώۂ̃J�����̏ꍇ�͕ʂ̔z��Ɍ��ʂ��i�[����B
  						if(in_array($index, $this->target_col)) {
  							$column = $header[$index];
  							$before[$column] = $latest_data[$index];
  							$after[$column] = $current_data[$index];
  						}
  					}
  					// ��������������result�Ɋi�[����B
  					if(!empty($before) && !empty($after)) {
  						$diff[$current_id]['title'] = $current_data[$this->title_col];
  						$diff[$current_id]['before'] = $before;
  						$diff[$current_id]['after'] = $after;
  					}
  				}
  				break;
  			}
  		}
  	}

    $this->diff = $diff;
    return true;
  }


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
    			$mail_body .= '��' . $title . '�y�폜�z' . PHP_EOL;
    			foreach ($before as $column => $value) {
    				$mail_body .= $column.': '.$before[$column].PHP_EOL;
    			}
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

  // �ُ�I�����̃t�@�C���폜
  public function remove() {
    if(file_exists($this->current_file)) {
      unlink($this->current_file);
    }
  }
}
