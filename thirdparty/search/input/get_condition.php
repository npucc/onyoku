<?php
$result['results'] = 3;
$result['condition'] = array(
	array('name' => '特になし', 'id' => 0),
	array('name' => '傷あり',   'id' => 1),
	array('name' => '破損',     'id' => 9)
);
echo json_encode($result)
?>