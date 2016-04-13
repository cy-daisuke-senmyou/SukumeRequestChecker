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
    // 最終更新日の担当者ファイルを取得して配列に格納する。
    $debug = $this->config->get_param('debug');
    $data_dir = $this->config->get_param('base_dir') . $this->config->get_param('data_dir');
  	$this->latest_file = Util::get_latest_file( $data_dir, $this->prefix, $this->ext, $debug );
  	$this->latest_array = Util::csv2array($this->latest_file);

  	// デヂエにアクセスして、CSVを取得し配列に格納する。
    $dezie = new Dezie( $this->config );
  	$dezie_data = $dezie->get_data( $this->prefix );
  	$this->current_file = Util::save_file( $dezie_data, $data_dir, $this->prefix, $this->ext );
  	$this->current_array = Util::csv2array($this->current_file);

  	// 担当者マスターの差分を取得する。
  	return $this->compare();
  }

  // 保管されている最新のCSVと、デヂエから取得したCSVを比較して差分を返す。
  private function compare() {
  	// ヘッダ行は後で配列添え字に使う。
  	$header = $this->current_array['header'];

  	// ２つの配列を比較して差分を抽出する。
  	$diff = array();

  	// 新規レコード
  	$new_record_array = array_diff_key($this->current_array, $this->latest_array);
  	foreach ($new_record_array as $key => $new_record) {
  		$after = array();
  		$diff[$key]['title'] = $new_record[$this->title_col];
  		foreach($new_record as $index => $value) {
  			// 差分抽出対象のカラムの場合は別の配列に結果を格納する。
  			if(in_array($index, $this->target_col)) {
  				$column = $header[$index];
  				$after[$column] = $new_record[$index];
  			}
  		}
  		$diff[$key]['after'] = $after;
  	}

  	// 削除レコード
  	$deleted_record_array = array_diff_key($this->latest_array, $this->current_array);
  	foreach ($deleted_record_array as $key => $deleted_record) {
  		$before = array();
  		$diff[$key]['title'] = $deleted_record[$this->title_col];
  		foreach($deleted_record as $index => $value) {
  			// 差分抽出対象のカラムの場合は別の配列に結果を格納する。
  			if(in_array($index, $this->target_col)) {
  				$column = $header[$index];
  				$before[$column] = $deleted_record[$index];
  			}
  		}
  		$diff[$key]['before'] = $before;
  	}

  	// 両方に存在するレコードの差分を抽出
  	// デヂエCSVレコード数分ループ
  	foreach($this->current_array as $current_data) {
  		if(empty($current_data[0])) continue;
  		$current_id = $current_data[0];
  		// 最終更新CSVファイルのレコード数分ループ。
  		foreach($this->latest_array as $latest_data) {
  			// ID列で突き合わせをする。
  			$latest_id = $latest_data[0];
  			if($latest_id === $current_id) {
  				$array_diff = array_diff_assoc($current_data, $latest_data);
  				if(!empty($array_diff)) {
  					$before = array();
  					$after = array();
  					// 差分要素数分ループ
  					foreach($array_diff as $index => $value) {
  						// 差分抽出対象のカラムの場合は別の配列に結果を格納する。
  						if(in_array($index, $this->target_col)) {
  							$column = $header[$index];
  							$before[$column] = $latest_data[$index];
  							$after[$column] = $current_data[$index];
  						}
  					}
  					// 差分があったらresultに格納する。
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
    			$mail_body .= '■' . $title . '【削除】' . PHP_EOL;
    			foreach ($before as $column => $value) {
    				$mail_body .= $column.': '.$before[$column].PHP_EOL;
    			}
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

  // 異常終了時のファイル削除
  public function remove() {
    if(file_exists($this->current_file)) {
      unlink($this->current_file);
    }
  }
}
