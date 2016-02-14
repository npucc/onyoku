<?php
include "header.inc";
?>
<div class = "contents">

<?php
require_once 'lib/db.php';
require_once('./searcher.php');

/* 入力の設定とチェック、validation */
class Input extends SearcherInput {
	function __construct() {
		if (strcasecmp($_SERVER['REQUEST_METHOD'],'post') == 0) {
			$this->name = $_POST['name'];
			$this->mode = $_POST['mode'];
			$this->pos = $_POST['pos'];
			$this->num = $_POST['num'];
			$this->artist_id = $_POST['artist_id'];
			$this->disc_id = $_POST['disc_id'];
		} else {
			$this->name = $_GET['name'];
			$this->mode = $_GET['mode'];
			$this->pos = $_GET['pos'];
			$this->num = $_GET['num'];
			$this->artist_id = $_GET['artist_id'];
			$this->disc_id = $_GET['disc_id'];
		}
		if (!$this->pos)
			$this->pos = 0;
		if (!$this->num && ($this->mode != 'result'))
			$this->num = 10;
	}
}
$output = null;
$input = new Input();
$searcher = new Searcher();/* Factoryに作らせたい */
try {
	$searcher->setInput($input);
	$db = OnyokuDB::getInstance();
	if ($count = $searcher->count($db)) { // ヒットした件数
		$searcher->search($db);
	}
	$output = $searcher->export();
} catch (Exception $e) {
	/* エラーメッセージはoutputに載せないとなあ */
	/* resultにfailureとか入れて */
	$error_message = $e->getMessage();
}
$artist_id = 0;
/* UI */
if ($output) {
	$result = json_decode($output, true);
	if ($result["result"] != "success")
		echo "検索に失敗しました";
	$count = $result["count"];
	if ($input->mode != 'result')
		echo "$count 件見つかりました<br>";
	if ($count > 0) {
		$pos = $result["pos"];
		$rnum = $result["num"];
		$begin = $pos + 1;
		$end = $pos + $rnum;
		if ($input->mode != 'result')
			echo "$begin 件目から $end 件目を表示<br>";
		$data = $result["data"];
		echo "<table border=1>";
		if ($input->mode == 'artist') {
			for ($i = 0; $i < $rnum; $i++) {
				echo "<tr>";
				echo
					"<td>{$data[$i][0]}</td>"
					."<td><a href=\"search.php?mode=music&artist_id={$data[$i][2]}&name=%\">"
					."曲検索へ"
					."</a></td>"
					."<td><a href=\"search.php?mode=disc&artist_id={$data[$i][2]}&name=%\">"
					."ディスク検索へ"
					."</a></td>";
				echo "</tr>";
			}
		} else if ($input->mode == 'disc') {
			echo '<tr><th>タイトル</th><th>副題</th></tr>';
			for ($i = 0; $i < $rnum; $i++) {
				echo "<tr>";
				echo "<td>{$data[$i][0]}</td>";
				echo "<td>";
				if ($data[$i][1])
					echo "{$data[$i][1]}";
				echo "</td>";
				echo "<td><a href=\"search.php?mode=result&disc_id={$data[$i][2]}\">"
					."詳細"
					."</a></td>";
				echo "</tr>";
			}
		} else if ($input->mode == 'music') {
			echo '<tr><th>曲名</th>';
			if ($data[$i][2])
				echo '<th>収録ディスク</th>';
			if ($data[$i][3])
				echo '<th>ディスク詳細</th>';
			echo '</tr>';
			for ($i = 0; $i < $rnum; $i++) {
				echo "<tr>";
				echo "<td>{$data[$i][0]}</td>";
				if ($data[$i][2])
					echo "<td>{$data[$i][2]}</td>";
				if ($data[$i][3])
					echo "<td><a href=\"search.php?mode=result&disc_id={$data[$i][3]}\">"
						."詳細"
						."</a></td>";
				echo "</tr>";
			}
		} else if ($input->mode == 'result') {
				echo "<tr>";
				echo "<th>タイトル</th>";
				echo "<th>副題</th>";
				echo "<th>レーベル1</th>";
				echo "<th>レーベル2</th>";
				echo "<th>ディスクタイプ</th>";
				echo "<th>メモ</th>";
				echo "</tr>";

				echo "<tr>";
				echo "<td>{$data[0][0]}</td>";
				echo "<td>{$data[0][1]}</td>";
				echo "<td>{$data[0][2]}（{$data[0][3]}）</td>";
				echo "<td>{$data[0][4]}（{$data[0][5]}）</td>";
				echo "<td>{$data[0][6]}</td>";
				echo "<td>{$data[0][7]}</td>";
				echo "</tr>";

				echo "<tr>";
				echo "<th>収録面</th>";
				echo "<th>トラック</th>";
				echo "<th>タイトル</th>";
				echo "<th>アーティスト名（カナ）</th>";
				echo "<th>参加区分</th>";
				echo "</tr>";

			$sideprev = 'numA';
			$rsprev_m = '';
			$numA = 0; $numB = 0;
			for ($i = 0; $i < $rnum; $i++) {
				if (($data[$i][8] != 1) && ($sideprev != 'numB')) {
					$sideprev = 'numB';
					$rsprev_m = '';
				}

				$$sideprev++;
				if ($rsprev_m != $data[$i][9]) {
					$rsprev_m = $data[$i][9];
					$numMs[] = $numM;
					$numM = 1;
				} else {
					$numM++;
				}
			}
			$numMs[] = $numM;
			$numMi = 1;
			$rsprev_s = '';
			for ($i = 0; $i < $rnum; $i++) {
				echo "<tr>";
				if ($rsprev_s != $data[$i][8]) {
					$sidetbl = array('', 'A', 'B');
					$rsprev_s = $data[$i][8];
					$rs = ($data[$i][8] == 1) ? $numA : $numB;
					echo "<td rowspan = $rs>{$sidetbl[$data[$i][8]]}</td>";
					$rsprev_m = '';
				}
				if ($rsprev_m != $data[$i][9]) {
					$rsprev_m = $data[$i][9];
					echo "<td rowspan={$numMs[$numMi]}>{$data[$i][9]}</td>";
					echo "<td rowspan={$numMs[$numMi]}>{$data[$i][10]}</td>";
					$numMi++;
				}
				echo "<td>{$data[$i][11]}（{$data[$i][12]}）</td>";
				echo "<td>{$data[$i][13]}</td>";
				echo "</tr>";
			}
		}
		
		echo "</table>";

		if (0 > ($prev = $pos - $input->num))
			$prev = 0;
		if ($pos > 0)
			show_prev($input->name, $input->mode, $prev, $input->artist_id);
		echo "<br>";
		if (($pos + $rnum) < $count)
			show_next($input->name, $input->mode, $pos + $rnum, $input->artist_id);
		echo "<br>";
	}
} else {
	echo "エラー：", $error_message ,"<br>";
}
show_form($input->name, $input->mode, $input->artist_id);

function show_prev($name, $mode, $pos, $artist_id) {
	$safe_name = htmlentities($name, ENT_QUOTES, "UTF-8");
	$safe_mode = htmlentities($mode, ENT_QUOTES, "UTF-8");
	$safe_pos = htmlentities($pos, ENT_QUOTES, "UTF-8");
	echo "<a href = \"search.php?name=$safe_name&mode=$safe_mode&pos=$safe_pos&artist_id=$artist_id\">prev</a>";
	return;
}
function show_next($name, $mode, $pos, $artist_id) {
	$safe_name = htmlentities($name, ENT_QUOTES, "UTF-8");
	$safe_mode = htmlentities($mode, ENT_QUOTES, "UTF-8");
	$safe_pos = htmlentities($pos, ENT_QUOTES, "UTF-8");
	echo "<a href = \"search.php?name=$name&mode=$mode&pos=$pos&artist_id=$artist_id\">next</a>";
	return;
}
function show_form($name, $mode, $artist_id) {
	$safe_name = htmlentities($name, ENT_QUOTES, "UTF-8");
	$safe_mode = htmlentities($mode, ENT_QUOTES, "UTF-8");
	echo "<form method = \"POST\" action=\"search.php\">";
	echo "<input type = \"text\" name = \"name\" value = \"$safe_name\">";
	echo "<input type = \"hidden\" name = \"mode\" value = \"$safe_mode\">";
	echo "<input type = \"hidden\" name = \"pos\" value = 0>";
	echo "<input type = \"hidden\" name = \"num\" value = 10>";
	echo "<input type = \"hidden\" name = \"artist_id\" value = $artist_id>";
	echo "<br>";
	echo "<input type = \"submit\" value = \"検索\">";
	echo "</form>";
	return;
}
?>

</div>
<?php
include "footer.inc";
?>