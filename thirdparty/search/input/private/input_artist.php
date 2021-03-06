<?php
require_once('../../lib/db.php');

$name = $_POST['artist_name'];
$kana = $_POST['artist_kana'];
$description = $_POST['artist_description'];

try {
	$db = OnyokuDB::getInstance();
	$sql = 'INSERT INTO artist'
	.' (name, kana, description)'
	.' VALUES ( $1, $2, $3)';

	$result = $db->query($sql, array($name, $kana, $description));
} catch (Exception $e) {
	$error_message = $e->getMessage();
}

$ret['success'] = isset($error_message) ? false : true;
$ret['input'] = array($name, $kana, $description);
$ret['error'] = $error_message;
echo json_encode($ret);
?>