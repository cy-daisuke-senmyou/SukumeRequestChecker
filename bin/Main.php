<?php

require_once("Config.php");
require_once("Member.php");
require_once("Project.php");
require_once("Relation.php");
require_once("Report.php");
require_once("MultiAlert.php");

$main = new Main();
$main->run();

class Main {
  // メール本文
  private $mail_body = '';

  public function run() {
    try {
      // 共通設定取得
      $base_dir = dirname(__FILE__) . '/../';
      $config = new Config( $base_dir );
      $debug = $config->get_param('debug');

      // レポート用オブジェクト
      $report = new Report( $config );

      // メンバー一覧のオブジェクト
      $member = new Member( $config );
      // 担当者マスターの差分を取得する。
      $member->get_diff();
  	  $this->mail_body .= $member->print_diff();

      // プロジェクト一覧のオブジェクト
      $project = new Project( $config );
      // プロジェクトマスターの差分を取得する。
      $project->get_diff();
      $this->mail_body .= $project->print_diff();

      // 担当プロジェクトのオブジェクト
      $relation = new Relation( $config );
      // 担当プロジェクトの差分を取得する。
      $relation->set_member($member);
      $relation->set_project($project);
      $relation->get_diff();
      $this->mail_body .= $relation->print_diff();

      // BackLogIDが入力されているにもかかわらず担当者名が未入力のデータを検出する。
      $this->mail_body .= $relation->validate();


      // 複数コンテンツ障害の配信先
      // 全社向け
      $multi_alert = new MultiAlert( $config );
      $multi_alert->set_mode('all');
      $multi_alert->set_project($project);
      $multi_alert->set_member($member);
      $multi_alert->get_diff();
      $this->mail_body .= $multi_alert->print_diff();
      // コンテンツ事業部向け
      $multi_alert = new MultiAlert( $config );
      $multi_alert->set_mode('contents');
      $multi_alert->set_project($project);
      $multi_alert->set_member($member);
      $multi_alert->get_diff();
      $this->mail_body .= $multi_alert->print_diff();

      // レポート
      $report->out( $this->mail_body );


      // 古いファイルを削除
    	Util::remove_old_file( $config->get_param('data_dir'), $config->get_param('keep_file_days') );

    } catch(Exception $e) {
      $member->remove();
      $project->remove();
      $relation->remove();
      $multi_alert->remove();
    	if($debug) {
    		print($e->getMessage().PHP_EOL);
    	} else {
    		$report->out( $e->getMessage() );
    	}
    }
  }
}
