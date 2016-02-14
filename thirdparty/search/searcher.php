<?php
require_once 'lib/db.php';
class SearcherInput {
	public $name;
	public $mode;
	public $pos;
	public $num;
	public $artist_id;
	public $music_id;
	public $disc_id;
}

// modeは多態で表現したいところ？
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

	private function setMode($mode) {
		$whitelist = array(
			'artist' => array(
				'artist',
				array('name', 'kana', 'id'),
				'sql' => "SELECT name, kana, id"
					." FROM artist"
					." WHERE name ILIKE $1 OR kana ILIKE $1",
				'params' => array("{$this->input->name}%")
			),
			'disc' => array(
				'disc',
				array('title', 'subtitle', 'no'),
				'sql' => "SELECT title, subtitle, no"
					." FROM disc"
					." WHERE title ILIKE $1 OR subtitle ILIKE $1",
				'params' => array("{$this->input->name}%")
			),
			'music' => array(
				'music',
				array('title', 'no'),
				'sql' => "SELECT title, no"
					." FROM music"
					." WHERE title ILIKE $1",
				'params' => array("{$this->input->name}%")
			),
			'disc_type' => array(
				'disctype',
				array('caption', 'id')
			),
			'stocker' => array(
				'stocker',
				array('id', 'caption')
			),
			'label' => array(
				'record_label',
				array('name', 'kana', 'id')
			),
			'category' => array(
				'category',
				array('caption', 'id')
			),
			'result' => array(
				'result',
				array(
					'title', 'subtitle',
					'label1_name', 'label1_kana',
					'label2_name', 'label2_kana',
					'disctype', 'description',
					'side', 'track', 'music',
					'artist_name', 'artist_kana', 'category'
				),
				'sql' =>
					"SELECT disc.title AS title, disc.subtitle AS subtitle,"
					. " label1.name AS label1_name,"
					. " label1.kana AS label1_kana,"
					. " label2.name AS label2_name,"
					. " label2.kana AS label2_kana,"
					. " disctype.caption AS disctype,"
					. " disc.description AS description,"
					. " discography.side AS side,"
					. " discography.track AS track, music.title AS music,"
					. " artist.name AS artist_name, artist.kana AS artist_kana,"
					. " category.caption AS category"
					." FROM disc"
					. " LEFT OUTER JOIN record_label AS label1 ON disc.label1_id = label1.id"
					. " LEFT OUTER JOIN record_label AS label2 ON disc.label2_id = label2.id"
					. ", disctype, discography, artist, music, category"
					." WHERE disc.disctype_id = disctype.id"
					. " AND discography.artist_id = artist.id"
					. " AND discography.music_no = music.no"
					. " AND discography.disc_no = disc.no"
					. " AND discography.category_id = category.id"
					. " AND disc.no = $1"
					." ORDER BY side, track",
				'params' => array("{$this->input->disc_id}")
			)
		);
		if ($this->input->artist_id > 0) {
			$whitelist['music']['sql'] =
				'SELECT music.title AS title, music.no AS no'
				.', disc.title AS disc_title, disc.no AS disc_no'
				.' FROM music, discography, disc'
				.' WHERE music.title ILIKE $1'
				.' AND discography.artist_id = $2'
				.' AND discography.music_no = music.no'
				.' AND discography.disc_no = disc.no';
			$whitelist['music']['params'] = array(
				"{$this->input->name}%",
				"{$this->input->artist_id}"
			);
			$whitelist['music'][1][] = 'disc_title';
			$whitelist['music'][1][] = 'disc_no';

			$whitelist['disc']['sql'] =
				'SELECT DISTINCT disc.title AS title'
				.', disc.subtitle AS subtitle, disc.no AS no'
				.' FROM disc, discography'
				.' WHERE disc.no = discography.disc_no'
				.' AND (disc.title ILIKE $1 OR disc.subtitle ILIKE $1)'
				.' AND discography.artist_id = $2';
			$whitelist['disc']['params'] = array(
				"{$this->input->name}%",
				"{$this->input->artist_id}"
			);
		}
		if ($table_column = $whitelist[$mode]) {
			$this->table  = $table_column[0];
			$this->key_column = $table_column[1][0];
			$this->columns = $table_column[1];
			$this->sql = $table_column['sql'];
			$this->params = $table_column['params'];
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
		if (!$input->disc_id && (!$input->name || !$input->mode)) {
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
		$alternative = array(
			'artist' => true,
			'disc'   => true,
			'music'  => true,
			'result' => true
		);
		$sql = 'SELECT COUNT(*)'.$this->common_string.' ILIKE $1';
		$params = array("{$this->input->name}%");

		if ($alternative[$this->input->mode]) {
			$sql = 'SELECT COUNT(*) FROM ('.$this->sql.') AS tmp';
			$params = $this->params;
		}
		$result = $db->query($sql, $params);

		$row = $result->fetchArray(0);
		return $this->count = $row[0];
	}
	public function search($db) {
		$alternative = array(
			'artist' => true,
			'disc'   => true,
			'music'  => true,
			'result' => true
		);
		$params = array(
			"{$this->input->name}%",
			$this->input->pos,
		);
		$sql = 'SELECT *'.$this->common_string;
		$sql .=' ILIKE $1';
		$sql .= ' OFFSET $2';
		if ($this->input->num) {
			$sql .= ' LIMIT $3';
			$params[] = $this->input->num;
		}
		if ($alternative[$this->input->mode]) {
			$params_count = count($this->params);
			$params_count++;
			$sql = $this->sql;
			$sql .= " OFFSET \$$params_count";
			$params = $this->params;
			$params[] = $this->input->pos;
			if ($this->input->num) {
				$params_count++;
				$sql .= " LIMIT \$$params_count";
				$params[] = $this->input->num;
			}
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
?>