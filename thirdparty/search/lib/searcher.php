<?php
require_once 'db.php';

class Searcher {
	private $input;
	private $table;
	private $key_column;
	private $count;
	private $num_rows = 0;
	private $data;
	private $common_string;
	private $error_flag = false;
	private $last_error;
	private $result = "success";

	/* tableとcolumnをここで指定、ユーザからの入力そのまま渡したりしない */
	private function setMode($mode) {
		$whitelist = array(
			'artist'    => array('artist'  , array('name', 'kana', 'id')),
			'disc'      => array('disc'    , array('title')),
			'music'     => array('music'   , array('title', 'no')),
			'disc_type' => array('disctype', array('caption', 'id')),
			'stocker'   => array('stocker' , array('id', 'caption')),
			'label'     => array('record_label', array('name', 'kana', 'id')),
			'category'  => array('category', array('caption', 'id')),
		);
		if ($table_column = $whitelist[$mode]) {
			$this->table  = $table_column[0];
			$this->key_column = $table_column[1][0];
			$this->columns = $table_column[1];
		} else {
			$this->error_flag = true;
			return false;
		}
		return true;
	}
	/* SQL文はDBに依存するため、OnyokuDB側のメソッドを使うべき */
	private function makeCommonSQLString() {
		$this->common_string = " FROM $this->table";
		$this->common_string .= " WHERE $this->key_column";
	}
	public function __construct() {
		return;
	}
	public function setInput($input) {
		if (!$input->name || !$input->mode) {
			$this->error_flag = true;
			throw new Exception("検索条件の不足");
		}

		$this->input = $input;
		if (!$this->setMode($input->mode))
			throw new Exception("検索モードの指定が不適切");
		$this->makeCommonSQLString();
		return;
	}
	public function count($db) {
		$sql = 'SELECT COUNT(*)'.$this->common_string.' ILIKE $1';
		$result = $db->query($sql,
			array(
				"{$this->input->name}%"
			));
		$row = $result->fetchArray(0);	
		return $this->count = $row[0];
	}
	public function search($db) {
		$params = array(
			"{$this->input->name}%",
			$this->input->pos,
		);
//		if ($this->input->mode == 'artist2music') {
//			"SELECT * FROM "
//		}
		$sql = 'SELECT *'.$this->common_string;
		$sql .=' ILIKE $1';
		$sql .= ' OFFSET $2';
		if ($this->input->num) {
			$sql .= ' LIMIT $3';
			$params[] = $this->input->num;
		}
		$result = $db->query($sql, $params);

		$this->num_rows = $result->numRows();

		for ($i = 0; $i < $this->num_rows; $i++) {
			$item = $result->fetchArray($i);
			foreach ($this->columns as $column)
				$this->data[$i][] = $item["$column"];
		}
	}
	public function export() { /* JSON形式にエンコード */
		$var = array (
			"result" => $this->result,
			"count"  => $this->count,
			"pos"    => $this->input->pos,
			"num"    => $this->num_rows,
			"data"   => $this->data
		);
		return json_encode($var);
	}
	public function checkError() {
		return $error_flag;
	}
	public function clearError() {
		$error_flag = false;
	}
}
class SearcherInput {
	public $name;
	public $mode;
	public $pos;
	public $num;
}
?>