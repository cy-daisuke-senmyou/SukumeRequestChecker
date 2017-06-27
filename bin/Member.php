<?php

require_once("Master.php");

class Member extends Master {
  protected $prefix = 'member_';
  protected $ext = 'csv';
  protected $target_col = array(5, 6, 7, 8, 9);
  protected $title_col = 6;
  protected $subject = "【スクメ担当者マスター】において以下のレコードが変更されました。\n\n";
}
