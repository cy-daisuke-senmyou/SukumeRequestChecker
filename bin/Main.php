<?php

require_once("Config.php");
require_once("Member.php");
require_once("Project.php");
require_once("Relation.php");
require_once("Report.php");

$main = new Main();
$main->run();

class Main {
  const BASE_DIR = '/home/cybird/SukumeRequestChecker/';
  // ���[���{��
  private $mail_body = '';

  public function run() {

    try {
      // ���ʐݒ�擾
      $config = new Config( self::BASE_DIR );
      // ���|�[�g�p�I�u�W�F�N�g
      $report = new Report( $config );

      // �����o�[�ꗗ�̃I�u�W�F�N�g
      $member = new Member( $config );
      // �S���҃}�X�^�[�̍������擾����B
    	$member->get_diff();
  		$this->mail_body .= $member->print_diff();

      // �v���W�F�N�g�ꗗ�̃I�u�W�F�N�g
      $project = new Project( $config );
      // �v���W�F�N�g�}�X�^�[�̍������擾����B
      $project->get_diff();
      $this->mail_body .= $project->print_diff();

      // �S���v���W�F�N�g�̃I�u�W�F�N�g
      $relation = new Relation( $config );
      // �S���v���W�F�N�g�̍������擾����B
      $relation->set_member($member);
      $relation->set_project($project);
      $relation->get_diff();
      $this->mail_body .= $relation->print_diff();

      // BackLogID�����͂���Ă���ɂ�������炸�S���Җ��������͂̃f�[�^�����o����B
      $this->mail_body .= $relation->validate();

      // ���|�[�g
      $report->out( $this->mail_body );

      // �Â��t�@�C�����폜
    	Util::remove_old_file( $config->get_param('data_dir'), $config->get_param('keep_file_days') );

    } catch(Exception $e) {
    	if(DEBUG_FLG) {
    		print($e->getMessage().PHP_EOL);
    	} else {
    		$report->out( $e->getMessage() );
    	}
    }
  }
}
