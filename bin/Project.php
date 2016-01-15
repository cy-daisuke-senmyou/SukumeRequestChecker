<?php

require_once("Master.php");

class Project extends Master {
  protected $prefix = 'pj_';
	protected $ext = 'csv';
  protected $target_col = array(3, 4, 5);
  protected $title_col = 1;
  protected $subject = "【スクメPJマスター】において以下のレコードが変更されました。\n\n";
}
