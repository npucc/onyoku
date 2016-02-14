<?php
//$dir = $_SERVER['DOCUMENT_ROOT'];
//if ($dir == "") $dir = ".";

$dir.= "./data";
require_once("./modules/utils.php");

$date = isset($_POST['date']) ? $_POST['date']:Date("Y/m/d");
$name = isset($_POST['name']) ? $_POST['name']:"";
$message = isset($_POST['message']) ? $_POST['message']:"";
$ticket = isset($_POST['ticket']) ? $_POST['ticket']:"";
$mode = isset($_POST['mode']) ? $_POST['mode']:"lookup";

switch ($mode) {
	case "lookup":
		lookup($date);
		break;
	case "add":
		add($date,$name,$message,$ticket);
		break;
	case "dirlist":
		dirlist($dir);
		break;
}

//---- end of main ----//

function lookup($date) {
	$temp = split("/",$date);
	$monthname = $temp[0].$temp[1];

	$folder = "./data/$monthname";
	$drc=dir($folder);
	$datas = array();
	$count = 0;
	if ($drc!= null) {
		while($fl=$drc->read()) {
  			$lfl = $folder."/".$fl;
		  	$din = pathinfo($lfl);
  			if ($fl!=".." && $fl!=".") {
				$temp = getData($folder."/".$fl);
				$rec = array();
				foreach ($temp as $data) {
					list($key,$value) = split('":"',$data);	
					$key = stlipQuot($key);
					$value = stlipQuot($value);
					$rec[$key] = $value;
				}
				$datas[] = $rec;
				$count++;
  			}
		}
		$drc->close();
	}		
	if ($count > 0) {
		$datas = JEncode($datas);
		echo '({"total":"'.$count.'","results":'.$datas. '})';
	} else {
		echo '({"total":"0","results":""})';
	}		
}

function add($date,$name,$message,$ticket) {
    $temp = split("/",$date);
    $monthname = $temp[0].$temp[1];

    $folder = "./data/$monthname";

	if (!file_exists($folder)) {
		umask(0);
		$di=mkdir($folder, 0777);
	}
	$tmp = '"date":"' .$date .'"';
	$tmp .= ',"name":"' .$name .'"';
	$tmp .= ',"ticket":"' . $ticket . '"';
	$tmp .= ',"message":"' . $message . '"'; 

	$fp = fopen($folder . "/" . $ticket . ".txt", "w+");
	if ($fp == false) {
  		echo 0;
		return;
	}
  	fwrite($fp, $tmp);
  	fwrite($fp, "\n");
	
	fclose($fp);
	$n = json_decode($tmp,true);
	echo 1;	
}

function dirlist($dir) {
	getDirList($dir);
}

function getData($pathname) {
	$data = trim(file_get_contents($pathname));
	$temp = split(",",$data);
	return $temp;
}
function stlipQuot($data) {
	$len = strlen($data); 
	if ($len > 1) {
		if ($data[0] == '"') {
			$data = substr($data,1);
			$len--;
		}
		if ($data[$len-1]== '"') {
			$data = substr($data,0,$len-1);
		}
	}
	return $data;
}


function getDirList($dir) {
	
	$ary = array();

	if ($current = opendir($dir)) {
		while ($file = readdir($current)) {
        	if (is_dir("$dir/$file") && !($file == "." || $file == "..") ) {
            	$ary[] = array("date" => $file );
                }
            }
            closedir($current);
	}
    if (count($ary) > 0) {
		 sort($ary);
		 $dates = JEncode($ary);
		 echo '({"total":"'.count($ary).'","results":'.$dates. '})';
	} else {
	     echo '({"total":"0","results":"$dir"})';
	}
}

?>
