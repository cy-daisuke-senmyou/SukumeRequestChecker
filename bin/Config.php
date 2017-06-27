<?php

class Config {
  private $base_dir = '';
  private $conf_path = 'conf/config.ini';
  private $param = array();

  public function __construct( $base_dir ) {
    $this->base_dir = $base_dir;
    $this->get_config();
    $this->param['base_dir'] = $base_dir;
    return true;
  }

  private function get_config() {
    $file = $this->base_dir . $this->conf_path;
    if(is_file( $file ) && preg_match("/.*\.ini$/", $file) == true) {
      $ini_data = parse_ini_file( $file );
    } else {
      throw new Exception("設定ファイルのオープンに失敗しました。");
      return false;
    }

    foreach ($ini_data as $key => $value) {
      $this->param[$key] = $value;
    }

    return true;
  }

  public function get_param( $key ) {
    return $this->param[$key];
  }
}
