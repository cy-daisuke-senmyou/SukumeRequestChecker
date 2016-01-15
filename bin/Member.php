<?php

require_once("Master.php");

class Member extends Master {
  protected $prefix = 'member_';
	protected $ext = 'csv';
  protected $target_col = array(0, 1, 2, 3, 4);
  protected $title_col = 1;
  protected $subject = "【スクメ担当者マスター】において以下のレコードが変更されました。\n\n";
}
