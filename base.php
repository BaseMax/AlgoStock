<?php
date_default_timezone_set("Asia/Tehran");
require "phpedb.php";
require "netphp.php";

$debug = true;
$db = new database();
$db->db="algostock";
$db->connect("localhost", "root", "");

function n2n($num) {
  return round($num, 2);
}

function logs($message) {
	global $debug;
	if($debug) {
		print($message."\n");
	}
}

function arabicToPersian($string){
  $characters = [
    'ك' => 'ک',
    'دِ' => 'د',
    'بِ' => 'ب',
    'زِ' => 'ز',
    'ذِ' => 'ذ',
    'شِ' => 'ش',
    'سِ' => 'س',
    'ى' => 'ی',
    'ي' => 'ی',
    '١' => '۱',
    '٢' => '۲',
    '٣' => '۳',
    '٤' => '۴',
    '٥' => '۵',
    '٦' => '۶',
    '٧' => '۷',
    '٨' => '۸',
    '٩' => '۹',
    '٠' => '۰',
  ];
  return str_replace(array_keys($characters), array_values($characters),$string);
}

function getData($url){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $headers = [];
    $headers[] = 'User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:79.0) Gecko/20100101 Firefox/79.0';
    $headers[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8';
    $headers[] = 'Accept-Language: en-US,en;q=0.5';
    $headers[] = 'Connection: keep-alive';
    $headers[] = 'Cookie: _ga=GA1.2.1547225046.1605700153; _gid=GA1.2.1909724480.1606295977; ASP.NET_SessionId=uoziejw0lyvcry0th4aygoll';
    $headers[] = 'Upgrade-Insecure-Requests: 1';
    $headers[] = 'Cache-Control: max-age=0';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        $result= 'Error:' . curl_error($ch);
    }
    curl_close($ch);
    return $result;
}
