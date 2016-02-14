<?php
// JSONデータ形式への変換
function JEncode($arr){
    if (version_compare(PHP_VERSION,"5.2","<"))
    {
        require_once("JSON.php"); //if php<5.2 need JSON class
        $json = new Services_JSON();//instantiate new json object
        $data=$json->encode($arr);  //encode the data in json format
    } else
    {
        $data = json_encode($arr);  //encode the data in json format
    }
    return $data;
}
?>

