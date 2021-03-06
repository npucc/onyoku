<?php

require_once '../lib/db.php';
require_once '../lib/searcher.php';

class Input extends SearcherInput{
	public function __construct() {
		$match = false;
		$modes = array(
			'disc_type',
			'stocker',
			'label',
			'artist',
			'category',
			'music',
		);
		foreach ($modes as $mode) {
			if (array_key_exists($mode, $_POST)) {
				$this->name = $_POST[$mode];
				$this->mode = $mode;
				$match = true;
				break;
			}
		}
		if (!$match)
			die;

		if ($this->name == '') {
			$this->name = '%';
		}
		$this->pos = 0;
		$this->num = 0;
	}
}
// main
$searcher = new Searcher();
$input = new Input();
$output = null;
$count = 0;
try {
		$searcher->setInput($input);
		$db = OnyokuDB::getInstance();
		if ($count = $searcher->count($db)) { // �ҥåȤ������
			$searcher->search($db);
		}
		$output = $searcher->export();
} catch (Exception $e) {
	$error_message = $e->getMessage();
}

$result['results'] = $count;
if ($output) {
	$tmp = json_decode($output, true);
	for ($i=0; $i<$count; $i++) {
		$a['name'] = $tmp['data'][$i][0];
		if (($input->mode == 'artist') ||
		    ($input->mode == 'label')) {
			$a['kana'] = $tmp['data'][$i][1];
			$a['id']   = $tmp['data'][$i][2];
		} else if (($input->mode == 'category')||
		           ($input->mode == 'music') ||
		           ($input->mode == 'disc_type')) {
			$a['id'] = $tmp['data'][$i][1];
		} else if ($input->mode == 'stocker') {
			$a['caption'] = $tmp['data'][$i][1];
		}
		$result['root'][] = $a;
	}
}
echo json_encode($result);
?>