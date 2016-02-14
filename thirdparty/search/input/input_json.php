<?php
require_once '../lib/db.php';

$file = file_get_contents('2009Jan29_214404_alt');
$input = json_decode($file);
print_r($input);

if (check_input($input)) {
	echo 'check_ok<br>';

	$music_id = register_music($input);
	$label_id = register_label($input);
	$stocker_id = register_stocker($input);

	$disc_id = register_disc($input, $label_id);

	register_discography($input, $music_id, $disc_id);
	register_asset($stocker_id, $disc_id, $input->condition);
}

function register_asset($stocker_id, $disc_id, $condition) {
	$db = OnyokuDB::getInstance();
	$sql = 'INSERT INTO asset'
	.' (stocker_id, disc_id, status)'
	.' VALUES($1, $2, $3)';

	$db->query($sql, array($stocker_id, $disc_id, $condition));
}

function register_discography($input, $music_id, $disc_id) {
	$db = OnyokuDB::getInstance();
	$sql = 'INSERT INTO discography'
	.' (artist_id, disc_no, music_no, category_id,'
	.' side, track, year, description)'
	.' VALUES($1, $2, $3, $4, $5, $6, $7, $8)';

	$i=0;
	foreach ($input->music as $music) {
		foreach ($music->artist as $artist) {
			$side_tbl = array(
				'A' => 1,
				'B' => 2
			);
			$db->query($sql, array(
				$artist->id,
				$disc_id,
				$music_id[$i],
				$artist->category_id,
				$side_tbl[$music->side],
				$music->track,
				$music->year,
				isset($music->description) ? $music->description : ''
			));
		}
		$i++;
	}
}

function register_disc($input, $label_id) {
	$db = OnyokuDB::getInstance();
	$sql = 'INSERT INTO disc'
	.' (title, subtitle,'
	.' label1_id, label2_id,'
	.' disctype_id, description,'
	.' year, product_no)'
	.' VALUES($1, $2, $3, $4, $5, $6, $7, $8)'
	.' RETURNING no';

	$year = $input->year ? $input->year : 0;
	$res = $db->query($sql, array(
		$input->title, $input->subtitle,
		$label_id[0], $label_id[1],
		$input->type, $input->description,
		$year, $input->product_no
	));
	$row = $res->fetchArray();
	return $row['no'];
}

/*
 DBへのストッカーの登録
 @return	String	ストッカーID
*/
function register_stocker($input) {
	$id = $input->stocker[0];
	$db = OnyokuDB::getInstance();
	$sql = 'SELECT id'
	.' FROM stocker'
	.' WHERE id = $1';
	
	$res = $db->query($sql, array($id));
	if (!$res->numRows()) {
		$sql = 'INSERT INTO stocker'
		.' (id, caption)'
		.' VALUES($1, $2)';
		$db->query($sql, array($id, $input->stocker[1]));
	}
	return $id;
}

/*
 DBへのレーベルの登録
 DBに既に同じレーベルがあっても構わずinsert
 @return	array	record_label.idの配列
*/
function register_label($input) {
	$db = OnyokuDB::getInstance();
	$sql = 'INSERT INTO record_label'
	.' (name, kana)'
	.' VALUES($1, $2)'
	.' RETURNING id';

	$labels[] = $input->label1;
	$labels[] = $input->label2;

	foreach ($labels as $label) {
		if ($label[2]) { /* id */
			$id_list[] = $label[2];
		} else if($label[0] && $label[1]){ /* name & kana */
			$res = $db->query($sql, array($label[0], $label[1]));
			$row = $res->fetchArray();
			$id_list[] = $row['id'];
		}
	}
	if (!isset($id_list[0]))
		$id_list[0] = 0;
	if (!isset($id_list[1]))
		$id_list[1] = 0;
	return $id_list;
}
/*
 DBへの曲名の登録
 DBに既に同じ曲名があっても構わずinsert
 @return	array	music.noの配列
*/
function register_music($input) {
	$db = OnyokuDB::getInstance();
	$sql = 'INSERT INTO music'
	.' (title)'
	.' VALUES($1)'
	.' RETURNING no';

	foreach($input->music as $music) {
		if ($music->id) {
			$id_list[] = $music->id;
		} else {
			$res = $db->query($sql, array($music->title));
			$row = $res->fetchArray();
			$id_list[] = $row['no'];
		}
	}
	
	return $id_list;
}
function save_input($input) {
	// 同時に書き込みあると困る？
	// まあ1ファイルに2件入る場合があってもいいか
	// 書き込みの際、末尾にセパレータでも入れるかあ？
	$filename = date('YMd_His');
	echo $filename."<br>";
	$fp = fopen($filename, 'a');
	if (flock($fp, LOCK_EX)) {
		fwrite($fp, $input);
		flock($fp, LOCK_UN);
	}
	fclose($fp);
}
function check_input($input) {
	if (!$input) {
		echo 'no input';
		return false;
	}
	if (!isset($input->title)  || !$input->title ||
	    !isset($input->type)   || !$input->type ||
	    !isset($input->stocker)|| !$input->stocker ||
	    !isset($input->condition) ||
		!isset($input->music) || !$input->music) {
		echo 'not enough disc_info';
		return false;
	}
	foreach ($input->music as $music) {
		if (!isset($music->side)  || !$music->side ||
			!isset($music->track) || !$music->track ||
			!isset($music->title) || !$music->title ||
		    !isset($music->year)  ||
		    !isset($music->artist) || !$music->artist) {
			echo 'not enough music_info';
			return false;
		}
		foreach ($music->artist as $artist) {
			if (!isset($artist->name) || !$artist->name ||
			    !isset($artist->category) || !$artist->category) {
				echo "not enough artist_info";
				return false;
			}
		}
	}
	return true;
}
?>
