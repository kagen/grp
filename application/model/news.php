<?php
/*
 * kagen.yu
  * 文字コード UTF-8
 */

class News extends ApplicationModel {

	function News() {
		$this->schema = array(
		'news_title'=>array(),
		'news_body'=>array(),
		'news_begin'=>array(),
		'news_end'=>array(),
		'recorder_disp'=>array(),
		'news_hide'=>array());
	}

	function index() {
		$hash = $this->simpleList('id',1,"INNER JOIN grp_user owner ON owner.userid = grp_news.owner LEFT JOIN grp_user editor ON editor.userid = grp_news.editor", array("grp_news.*", "owner.realname AS owner", "editor.realname AS editor"));
		return $hash;
	}

	function insert() {
		$this->validateSchema('insert');
		if($this->insertPost()){
			$hash = $this->findView($this->mysql_insert_id);
		}
		return $hash;
	}

	function update() {
		$this->validateSchema('update');
		if($this->updatePost()){
			$hash = $this->findView(intval($_POST['id']));
		}
		return $hash;
	}

	function remove() {
		return $this->deletePost();
	}

	function get() {
		if($_POST['news_hide'] == 0){
			$this->where[] = "(news_hide = 0)";
		}
		if($_POST['recorder_disp'] == 1){
			$this->where[] = "(recorder_disp = 1)";
		}
		$this->where[] = "((news_begin IS NULL OR news_begin <= CURRENT_TIMESTAMP) AND (news_end IS NULL OR news_end >= CURRENT_TIMESTAMP))";
		$hash = $this->simpleList('id',1,"INNER JOIN grp_user owner ON owner.userid = grp_news.owner LEFT JOIN grp_user editor ON editor.userid = grp_news.editor", array("grp_news.*", "owner.realname AS owner", "editor.realname AS editor"));
		return $hash;
	}

	function detail() {
		$this->insertAccess();
		$hash = $this->findView(intval($_POST['id']),"INNER JOIN grp_user owner ON owner.userid = grp_news.owner LEFT JOIN grp_user editor ON editor.userid = grp_news.editor", array("grp_news.*", "owner.realname AS owner", "editor.realname AS editor"));
		return $hash;
	}

}
?>