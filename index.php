<?php
Header('Content-Type: text/html; charset=utf-8');

require_once("lib/curl_query.php");
require_once("lib/simple_html_dom.php");

	$servername = "localhost";
	$username = "root";
	$password = "";
	$dbname = "parsegismeteo";

	$list = [];
// Create connection

$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
}


function debug($var)
{
	echo "<pre>";
	var_dump($var);
	echo "</pre>";
	die();
}

/*
* Notification (text/back)
0-white
1-gray
2-blue
3-green
4-yellow
5-red
*/
function notis($message, $type = 0)
{
	$text = '#000';
	$back = '#fff';
	
	switch($type){
		case 0: 
			$text = '#000';
			$back = '#fff';
			break;
		case 1: 
			$text = '#000';
			$back = '#CCCCCC';
			break;
		case 2: 
			$text = '#000';
			$back = '#3399FF';
			break;
		case 3: 
			$text = '#000';
			$back = '#99FF66';
			break;
		case 4: 
			$text = '#000';
			$back = '#FFFF99';
			break;
		case 5: 
			$text = '#000';
			$back = '#FF9966';
			break;
	}
	echo "<div style='padding:10px; color:$text; background:$back;'>$message</div>";
}

function saveToFile($url, $file)
{
	$html = file_get_contents($url, false, $context);
			
			if (file_put_contents($file, $html)) {
				return true;
			} else {
				return false;
			}
}

// Сохранить запись о файле в БД
function saveFileToDb($city, $year, $month, $filename)
{
	global $conn;
	$sql = "INSERT INTO files (city_id, year, month, file_name)
		VALUES ('$city', '$year', '$month', '$filename')";

		if ($conn->query($sql) === TRUE) {
			echo "New record created successfully";
		} else {
			echo "Error: " . $sql . "<br>" . $conn->error;
		}
}

// Сохранить погоду за день
function saveWeatherToDb($fname, $params)
{
	global $conn;
	$count = count($params);
	$city = $fname['city'];
	$lm = $fname['month']; 
	if ($lm < 10) {$lm = "0".$lm;}
	
	for ($i=0; $i < $count; $i++)
	{
		$ld = $params[$i]['date']; 
		if ($ld < 10) {$ld = "0".$ld;}
		
		$date = $fname['year']."-".$lm."-".$ld;
		
		$d_temp			=	$params[$i]['d_temp'];
		$d_press		=	$params[$i]['d_press'];
		$d_cloud		=	$params[$i]['d_cloud'];
		$d_phenomen		=	$params[$i]['d_phenomen'];
		$d_wind_vector	=	$params[$i]['d_wind_vector'];
		$d_wind_power	=	$params[$i]['d_wind_power'];
		$n_temp			=	$params[$i]['n_temp'];
		$n_press		=	$params[$i]['n_press'];
		$n_cloud		=	$params[$i]['n_cloud'];
		$n_phenomen		=	$params[$i]['n_phenomen'];
		$n_wind_vector	=	$params[$i]['n_wind_vector'];
		$n_wind_power	=	$params[$i]['n_wind_power'];
		
		$sql = "INSERT INTO weather (city_id, date, d_temp, d_press, d_cloud, d_phenomen, d_wind_vector, d_wind_power, n_temp, n_press, n_cloud, n_phenomen, n_wind_vector, n_wind_power)
			VALUES ('$city', '$date', '$d_temp', '$d_press', '$d_cloud', '$d_phenomen', '$d_wind_vector', '$d_wind_power', '$n_temp', '$n_press', '$n_cloud', '$n_phenomen', '$n_wind_vector', '$n_wind_power')";
		$result = $conn->query($sql);
	}
	
		if ($result === TRUE) {
			echo "New record created successfully";
		} else {
			echo "Error: " . $sql . "<br>" . $conn->error;
		}
}

// Получить список файлов из базы
function getFileList($city)
{
	global $conn, $list;
	$sql = "SELECT * FROM files WHERE city_id = ".$city;
	$result = $conn->query($sql);
		if ($result) {
			while($row = $result->fetch_array()){
				$list[] = $row['file_name'];
			}
			return $list;
		} else {
			echo "Error: " . $sql . "<br>" . $conn->error;
			return false;
		}
}

// Сохраняем контент в файлы по месяцам
function getFile($city = null, $year = null, $month = null)
{
	
	$opts = array(
		'http'=>array(
		'method'=>"GET",
		'header'=>"Accept-language: en\r\n" .
				  "Cookie: foo=bar\r\n"
		  )
	);

	$context = stream_context_create($opts);

	if (!is_null($city))
	{
		if (is_null($year) && is_null($month))
		{
			// парсим все
			for ($y=1997; $y<2021; $y++)
			{
				for ($m=1; $m<=12; $m++)
				{
					$url = "https://www.gismeteo.ru/diary/".$city."/".$y."/".$m."/";
					$lm = $m;
					if ($m<10){$lm = "0".$m;}
					$route = "data/".$city."/";
					$filename = $y."_".$lm.".txt";
			
					if (saveToFile($url, $route.''.$filename)) 
					{
						saveFileToDb($city, $y, $m, $filename);
					}
				}
			}
		} else {
			// парсим нужный месяц
			$url = "https://www.gismeteo.ru/diary/".$city."/".$year."/".$month."/";
			$lm = $month;
			if ($m<10){$lm = "0".$month;}
			$route = "data/".$city."/";
			$filename = $year."_".$lm.".txt";
			if (saveToFile($url, $route.''.$filename)) 
					{
						saveFileToDb($city, $year, $month, $filename);		
					}
		}
	}else{
		notis("Город не задан!", 5);
	}
}

// Парсинг направления и скорости ветра
function parseWind($str)
{
	if (($str <> "Ш") & (strlen($str) > 0)){
		$wind = explode(" ", $str);
	} else {$wind = Array(0,0);}
	return $wind;
}

// Парсинг имени файла
function parseFileName($str)
{
	$farr = [];
	if (isset($str)){
		$t = explode("/", $str);
		$farr['city'] = (int)$t[1];
		$w = explode("_", substr($t[2], 0, -4));
		$farr['year'] = (int)$w[0];
		$farr['month'] = (int)$w[1];
		
		return $farr;
	}
	
	return false;
	

}

// Парсинг сохраненных файлов
function parseFile($file)
{
	$html = file_get_contents($file);
	$arr = [];
	$dom = str_get_html($html);

	
	$dtemps = $dom->find('td');
	$i = 0;
	$d = -1;
	foreach($dtemps as $dtemp){
		switch($i % 11) {
			
			case 10:		$w = parseWind(trim($dtemp->plaintext));
						$arr[$d]['n_wind_vector'] 	= 	$w[0];
						$arr[$d]['n_wind_power'] 	= 	(int)$w[1];
						break;
			case 9:		$arr[$d]['n_phenomen'] 		= 	$dtemp->children(0)->src ? $dtemp->children(0)->src : null; break;
			case 8:		$arr[$d]['n_cloud'] 		= 	$dtemp->children(0)->src ? $dtemp->children(0)->src : null; break;
			case 7:		$arr[$d]['n_press'] 		= 	isset($dtemp->plaintext) ? (int)$dtemp->plaintext : 0; break;
			case 6:		$arr[$d]['n_temp'] 			= 	isset($dtemp->plaintext) ? (int)$dtemp->plaintext : 0; break;
			case 5:			$w = parseWind(trim($dtemp->plaintext));
						$arr[$d]['d_wind_vector'] 	= 	$w[0];
						$arr[$d]['d_wind_power'] 	= 	(int)$w[1];
						break;
			case 4:		$arr[$d]['d_phenomen'] 		= 	$dtemp->children(0)->src ? $dtemp->children(0)->src : null; break;
			case 3:		$arr[$d]['d_cloud'] 		= 	$dtemp->children(0)->src ? $dtemp->children(0)->src : null; break;
			case 2:		$arr[$d]['d_press'] 		= 	isset($dtemp->plaintext) ? (int)$dtemp->plaintext : 0; break;
			case 1:		$arr[$d]['d_temp'] 			= 	isset($dtemp->plaintext) ? (int)$dtemp->plaintext : 0; break;
			case 0:		$arr[++$d]['date']			=	(int)$dtemp->plaintext;break;
		}	
		
		 $i++;
	}
	
	$fname = parseFileName($file);
	if ($fname) {
		saveWeatherToDb($fname, $arr);
	}
	
	
}

//getFile(5039, null, null);
$city = 5039;
$list = getFileList($city);
$count = count($list);

for ($i=0; $i < $count; $i++)
{
	$path = 'data/'.$city.'/'.$list[$i];
	
	if (file_exists($path))
	{
		//echo $list[$i]."-ok</br>";
		parseFile($path);
	}else{
		echo 'false</br>';
	}
}




?>
