<?php
/*
 * @Name: Algo Stock
 * @Author: Max Base
 * @Date: 2021-01-04, 2021-01-05, 2021-01-06, 2021-01-07, 2021-01-08, 2021-01-09, 2021-01-10
 * @Repository: https://github.com/BaseMax/AlgoStock
 */

require "phpedb.php";
require "netphp.php";

date_default_timezone_set("Asia/Tehran");

define("TIME_START", "01:00:00am");
// define("TIME_START", "07:50:00am");
// define("TIME_END", "12:30:00pm");
// define("TIME_END", "12:30:00pm");
// define("TIME_END", "12:30:00pm");
define("TIME_END", "11:30:00pm");

// echo strtotime("now"), "\r\n";
// echo strtotime("+1 day"), "\r\n";
// 1607558815 - 1607472415 = 86400
// 1 day = 24 * 60 * 60 = 86400
define("DAY_IN_UNIX", 86400);

$debug = false;
$db = new database();
$db->db="algostock";
$db->connect("localhost", "root", "");

if($debug === true) {
  logs($argv);
}

function n2n($num) {
  return round($num, 2);
}

function logs($message) {
  global $debug;

  if($debug) {
    if(is_array($message)) {
      print_r($message);
    }
    else {
      print($message."\r\n");
    }
  }
}

function update_history_symbol($symbol, $startTime, $endTime) {
  global $db;
  // global $DAY_IN_BACK;
  global $debug;

  if($debug) {
    logs($symbol);
    logs($startTime);
    logs($endTime);
  }

  // https://rahavard365.com/api/chart/bars?ticker=exchange.asset%3A591%3Areal_close%3Atype1&resolution=1D&startDateTime=0&endDateTime=1609862968&firstDataRequest=true
  // https://rahavard365.com/api/chart/bars?ticker=exchange.asset%3A591%3Areal_close%3Atype1&resolution=1&startDateTime=1609773876&endDateTime=1609863398&firstDataRequest=true
  // $url = 'https://rahavard365.com/api/chart/bars?ticker=exchange.asset%3A'. $symbol["rahavardID"] .'%3Areal_close%3Atype1&resolution=1&startDateTime='. $startTime .'&endDateTime='. $endTime .'&firstDataRequest=true';
  // $url = 'https://rahavard365.com/api/chart/bars?ticker=exchange.asset%3A'. $symbol["rahavardID"] .'%3Areal_close&resolution=1&startDateTime='. $startTime .'&endDateTime='. $endTime .'&firstDataRequest=true';
  // $url = 'https://rahavard365.com/api/chart/bars?ticker=exchange.asset%3A591%3Areal_close%3Atype1&resolution=1&startDateTime='.$startTime.'&endDateTime='.$endTime.'&firstDataRequest=false';
  $url = 'https://rahavard365.com/api/chart/bars?ticker=exchange.asset%3A'.$symbol["rahavardID"].'%3Areal_close&resolution=1&startDateTime='.$startTime.'&endDateTime='.$endTime.'&firstDataRequest=false';
  if($debug) {
    logs($url);
  }
  print($url."\n");
  $headers = [
    'Connection: keep-alive',
    'Accept: */*',
    'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.111 Safari/537.36',
    'X-Requested-With: XMLHttpRequest',
    'Sec-Fetch-Site: same-origin',
    'Sec-Fetch-Mode: cors',
    'Sec-Fetch-Dest: empty',
    'Referer: '.$url,
    'Accept-Language: en,fa;q=0.9',
    'Cookie: __RequestVerificationToken=1KTHAtgKp3ExZCLUjruVbrN4Wni3dC5KtT7AZhIjvSEN1kkELE0Pw_FXKUCM0OsasZL6xm23r2ePw4-jG5N8qklX4Ac1; .rahavard365auth=6F3AFDA282A4E5C5BA0BF6DC4F694254E0339C17E770C247DF50142472E07C57C433BDA7770583BBC500A466B1BCF7127801C201D80169D7B24D50CC6A2F8C9621E990D9D087F161603EB3586BE9B3A1333BA20B424BC745A624946CD2EAA3E81523133F868D8477DE9A8AB41F09F1FC84D2AF97A7EF68CE219229EF75D4F5462383C00D2EAC97E06A2CF0DDA4CA1F17D343924A769E1739D18FCE4FC2B12F37CFF7CBFB534F547035332EF217E6073CECA47E5855905131E6F5D3F3DF113BA9E595ECD8BD8668EB035B399DE07E505039FED24CE297740B6471994CAA110FA404F76F5B; pro.package.state.905874=False; pro.package.905874=12/9/2020 3:14:53 AM',
  ];
  $json = request("get", $url, $headers);
  file_put_contents("temp.json", $json);
  // print_r($result);
  if($json) {
    $json = json_decode($json, true);
    if(isset($json["noData"])) {
      logs("The empty JSON");
    }
    else {
      // logs("JSON Length: ".strlen($json));
      // print("JSON Length: ".count($json)."\n");
      if($json === null || $json === []) {
        logs("JSON Response is not valid!");
      }
      else {
        logs("JSON Response is good!");

        $db->database->beginTransaction();
        foreach($json as $item) {
          if(is_array($item)) {
            // print_r($item);
            $clauses = [
              "symbolID"=>$symbol["id"],
              "epoch"=>$item["time"],
            ];

            $values = [
              "symbolID"=>$symbol["id"],
              "time"=>getEpochTime($item["time"]),
              "date"=>getEpochDate($item["time"]),
              "epoch"=>$item["time"],
              "low"=>$item["low"],
              "high"=>$item["high"],
              "open"=>$item["open"],
              "close"=>$item["close"],
              "volume"=>$item["volume"],
              "rsi"=>0,
              "ao"=>0,
            ];

            if($db->count("history", $clauses) === 0) {
              print ".";
              if($debug) {
                logs("Not found this stock report...");
              }

              if($db->insert("history", $values)) {
                if($debug) {
                  print "New stock record created successfully";
                }
              } else {
                if($debug) {
                  print "Insert stock error: " . $db->error;
                }
              }

            }
            else {
              // $db->update("history", $clauses, $values);
            }

          }
        }
        $db->database->commit();
        // logs("Update indicator...");
        // updateIndicator($symbol);
      }
    }
  }
  else {
    logs("Connection timed out");
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

function request($method, $url, $headers=[]) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
  curl_setopt($ch, CURLOPT_ENCODING, "gzip, deflate");
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  $result = curl_exec($ch);
  if(curl_errno($ch)) {
    logs("Curl error ". curl_error($ch));
    return null;
  }
  curl_close($ch);
  return $result;
}

function tehranRequest($link){
    $headers = [];
    $headers[] = "User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:79.0) Gecko/20100101 Firefox/79.0";
    $headers[] = "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8";
    $headers[] = "Accept-Language: en-US,en;q=0.5";
    $headers[] = "Connection: keep-alive";
    $headers[] = "Cookie: _ga=GA1.2.1547225046.1605700153; _gid=GA1.2.1909724480.1606295977; ASP.NET_SessionId=uoziejw0lyvcry0th4aygoll";
    $headers[] = "Upgrade-Insecure-Requests: 1";
    $headers[] = "Cache-Control: max-age=0";
    return request("get", $link, $headers);
}

function arg_help($args=[], $hasError=false) {
  if($hasError === true) {
    print "Error!\r\n\r\n";
  }
  print " algostock\r\n";
  print " Algorithm Tehran Stock Analyzer\r\n";
  print "\r\n";
  print "   symbol:\r\n";
  print "      algostock symbol list: List of symbols\r\n";
  print "      algostock symbol listAll: List of all symbols\r\n";
  print "      algostock symbol clear: Clear symbols\r\n";
  print "      algostock symbol update: Update list of symbols\r\n";
  print "\r\n";
  print "   history:\r\n";
  print "      algostock history list: List of symbols\r\n";
  print "      algostock history clear: Clear symbols\r\n";
  print "      algostock history update: Update list of symbols\r\n";
  print "      algostock history updateToday: Update list of today's symbols\r\n";
  print "\r\n";
}

function symbol_update_tehran1() {
  global $db;

  $db->database->beginTransaction();
  $res = tehranRequest("http://www.tsetmc.com/tsev2/data/MarketWatchInit.aspx?h=0&r=0");
  if($res !== "" and $res !== null) {
    $items = explode("@", $res);
    if(isset($items[2])) {
      $rows = explode(";", $items[2]);
      foreach($rows as $row) {
        $cols = explode(",", $row);
        $clauses = [
          "name"=>arabicToPersian($cols[2]),
        ];
        $values = [
          "name"=>arabicToPersian($cols[2]),
          "title"=>arabicToPersian($cols[3]),
          "tsetmcINS"=>$cols[0],
          "tsetmcID"=>$cols[1],
        ];
        if($db->count("symbol", $clauses) === 0) {
          $db->insert("symbol", $values);
        }
        else {
          $db->update("symbol", $clauses, $values);
        }
      }
    }
    else {
      logs("Cannot parse response of tsetmc market watch webservice to explode @[2]...");
    }
  }
  else {
    logs("Cannot get response from tsetmc market watch webservice...");
  }
  $db->database->commit();
}

function symbol_update_tehran2() {
  global $db;

  $db->database->beginTransaction();
  $res = tehranRequest("http://www.tsetmc.com/Loader.aspx?ParTree=111C1417");
  if($res !== "" and $res !== null) {
    $ids = [];
    $inds = [];
    $names = [];

    $regex = '/<tr>(\s*|)<td>(?<id>[^\<]+)<\/td>/i';
    preg_match_all($regex, $res, $matches);
    if(isset($matches["id"])) {
      $matches = $matches["id"];
      // print_r($matches);
      $ids = $matches;
    }

    // $regex = '/\&inscode=(?<ins>[^\\\'\"]+)\'([^\>]+|)>(?<name>[^\<]+)<\/a>(\s*|)<\/td>(\s*|)<td/i';
    $regex = '/\&inscode=(?<ins>[^\\\'\"]+)\'([^\>]+|)>(?<name>[^\<]+)<\/a>/i';
    preg_match_all($regex, $res, $matches);
    if(isset($matches["ins"], $matches["name"])) {
      // print_r($matches["ins"]);
      // print_r($matches["name"]);
      $ins = $matches["ins"];
      $names = $matches["name"];
    }

    if(count($ids) * 2 === count($ins) and count($ins) === count($names)) {
      $length = count($names);

      // foreach($ids as $i=>$id) {}
      for($i=0; $i<$length; $i+=2) {
        $clauses = [
          "name"=>arabicToPersian($names[$i]),
        ];
        $values = [
          "name"=>arabicToPersian($names[$i]),
          "title"=>arabicToPersian($names[$i+1]),
          "tsetmcINS"=>$ins[$i],
          "tsetmcID"=>$ids[(int)$i / 2],
        ];
        if($db->count("symbol", $clauses) === 0) {
          $db->insert("symbol", $values);
        }
        else {
          $db->update("symbol", $clauses, $values);
        }
      }
    }
    else {
      logs("Count of id, ins, name in second tsetmc market watch webservice is not same...");
    }
  }
  else {
    logs("Cannot get response from second tsetmc market watch webservice...");
  }
  $db->database->commit();
}

function symbol_update_rahavard1() {
  global $db;

  $db->database->beginTransaction();
  $res = tehranRequest("https://rahavard365.com/stock?last_trade=any");
  if($res !== "" and $res !== null) {
    $regex = '/var layoutModel = (?<object>[^\;]+)/i';
    preg_match($regex, $res, $match);
    if(isset($match["object"]) and $match["object"] !== "") {
      $object = $match["object"];
      $object = json_decode($object, true);
      if(is_array($object) and $object !== []) {
        if(isset($object["asset_data_list"])) {
          $object = $object["asset_data_list"];
          foreach($object as $i=>$item) {
            if(isset($item["asset"])) {
              if(!isset($item["asset"]["trade_symbol"]) || !isset($item["asset"]["entity"]["id"]) || !isset($item["asset"]["name"])) {
                // print_r($item["asset"]);
                continue;
              }
              $clauses = [
                "name"=>arabicToPersian($item["asset"]["trade_symbol"]),
              ];
              $values = [
                "name"=>arabicToPersian($item["asset"]["trade_symbol"]),
                "title"=>arabicToPersian($item["asset"]["name"]),
                "rahavardID"=>$item["asset"]["entity"]["id"],
              ];
              if($db->count("symbol", $clauses) === 0) {
                $db->insert("symbol", $values);
              }
              else {
                $db->update("symbol", $clauses, $values);
              }
            }
            else {
              logs("Cannot parse `asset` in asset_data_list object of rahavard market watch webservice...");
            }
          }
        }
        else {
          logs("Cannot parse `asset_data_list` from object of rahavard market watch webservice...");
        }
      }
      else {
        logs("Cannot parse object as JSON of rahavard market watch webservice...");
      }
    }
    else {
      logs("Cannot parse response of rahavard market watch webservice...");
    }
  }
  else {
    logs("Cannot get response from rahavard market watch webservice...");
  }
  $db->database->commit();
}

function arg_symbol_update($args=[]) {
  // Tehran
  symbol_update_tehran1();
  symbol_update_tehran2();

  // Rahavard
  symbol_update_rahavard1();
  print "Done updating.\r\n";
}

function get_symbol_list() {
  global $db;

  // $symbols = $db->selectsRaw("SELECT * FROM $db->db.`symbol` WHERE `rahavardID` IS NOT NULL AND `tsetmcID` IS NOT NULL AND `tsetmcINS` IS NOT NULL;");
  $symbols = [ $db->select("symbol", ["name"=>"شبندر"]) ];
  return $symbols;
}

function arg_symbol_list($args=[]) {
  global $db;

  if(isset($args[0]) and $args[0] === "all") {
    $symbols = $db->selectsRaw("SELECT * FROM $db->db.`symbol`;");
  }
  else {
    $symbols = get_symbol_list();
  }

  print_symbols($symbols);
}

// function arg_symbol_listall($args=[]) {
//   global $db;

//   $symbols = $db->selectsRaw("SELECT * FROM $db->db.`symbol`;");
//   print_symbols($symbols);
// }

function print_symbols($symbols) {
  print_r($symbols);
}

function print_histories($histories) {
  print_r($histories);
}

function arg_symbol_clear($args=[]) {
  global $db;

  $db->delete("symbol", []);
  print "Dlete all symbols.\r\n";
}

function startsWith($string, $startString) { 
  $len = strlen($startString); 
  return (substr($string, 0, $len) === $startString); 
} 
  
function endsWith($string, $endString) { 
  $len = strlen($endString); 
  if($len == 0) { 
    return true; 
  } 
  return(substr($string, -$len) === $endString); 
} 

function find_history_at_end_of_day($histories, $s) {
  $find = [];
  $length = count($histories);
  $i=0;
  foreach($histories as $history) {
    if(!startsWith($history["time"],"12:")) {
      continue;
    }

    $time_min = $history["time"];
    $time_min = substr($time_min, -5, 2);
    // print $time_min."\n";
    $time_min = (int)$time_min;

    if($time_min >= $s) {
      $find = $history;
      break;
    }
    $i++;
  }

  if($find === []) {
    $i = $length-1;
    $find = $histories[$length-1];
  }

  return [$i, $find];
}

function arg_analyze_list($args=[]) {
  global $db;

  $analyze_per_day = false;
  $symbols = get_symbol_list();
  $benfitTotal = 0;
  foreach($symbols as $symbol) {
    // $histories = $db->selects("history", [], "", "date, time, rsi, ao");
    $days = $db->selectsRaw("SELECT date FROM $db->db.`history` WHERE `rsi` IS NOT NULL AND `ao` IS NOT NULL GROUP BY `date` ORDER BY `epoch` ASC;");
    $benfitAll = 0;
    foreach($days as $day) {
      $benfitDay = 0;
      $day = $day["date"];
      if($analyze_per_day) {
        $todayHistories = $db->selectsRaw("SELECT time, volume, rsi, ao, close FROM $db->db.`history` WHERE `date` = '$day' AND `rsi` IS NOT NULL AND `ao` IS NOT NULL ORDER BY `epoch` ASC;");
      }
      else {
        $todayHistories = $db->selectsRaw("SELECT time, volume, rsi, ao, close FROM $db->db.`history` WHERE `rsi` IS NOT NULL AND `ao` IS NOT NULL ORDER BY `epoch` ASC;");
      }
      if($todayHistories === [] || $todayHistories === null) {
        continue;
      }

      // print_r($day);
      // print_r($histories);

      $trades = [];
      $x = 0;
      $length = count($todayHistories);
      for($i=0;$i<$length;$i++) {
        if($i <= 16) {
          continue;
        }
        $aoDiff = $todayHistories[$i]["ao"] - $todayHistories[$i-1]["ao"];
        $todayHistories[$i]["trade_buy"] = ($aoDiff > 0 && $todayHistories[$i]["rsi"]<40) ? true : false;
        $todayHistories[$i]["trade_sale"] = ($aoDiff < 0 && $todayHistories[$i]["rsi"]>60) ? true : false;
        if($todayHistories[$i]["trade_buy"] === true) {
          $type = "buy";
        }
        else if($todayHistories[$i]["trade_sale"] === true) {
          $type = "sale";
        }

        if(isset($type)) {
          if(($trades === [] and $type === "buy") || ($trades !== [] and (
              ($trades[$x-1]["type"] === "sale" and $type !== "sale")
              ||
              ($trades[$x-1]["type"] === "buy" and $type !== "buy")
            )
          )) {
            $trades[$x++]=[
              "id"=>$i,
              // "date"=>$todayHistories[$i]["date"],
              "date"=>$day,
              "time"=>$todayHistories[$i]["time"],
              "type"=>$type,
              "close"=>$todayHistories[$i]["close"],
              "volume"=>$todayHistories[$i]["volume"],
              "rsi"=>$todayHistories[$i]["rsi"],
              "ao"=>$todayHistories[$i]["ao"],
            ];
          }
        }
        unset($type);
      }

      if($trades === [] || $trades === null) {
        continue;
      }

      // Auto sale at last time of day
      /*
      if($trades[$x-1]["type"] === "buy") {
        $findHistory = find_history_at_end_of_day($todayHistories, 15); // 15 is mins at 12th hours of day/morning

        // TODO: check $findHistory[1] is [] or null!
        $i = $findHistory[0];
        $findHistory = $findHistory[1];

        $trades[] = [
          "id"=>$i,
          "date"=>$day,
          "time"=>$findHistory["time"],
          "type"=>"sale",
          "close"=>$findHistory["close"],
          "volume"=>$findHistory["volume"],
          "rsi"=>$findHistory["rsi"],
          "ao"=>$findHistory["ao"],
          "auto"=>true,
        ];
      }
      */

      // print_r($trades);
      // exit();

      $length = count($trades);
      for($i=0;$i<$length;$i++) {
        if($trades[$i]["type"] === "sale") {
          $priceDiff = $trades[$i]["close"] - $trades[$i-1]["close"];
          $percent = $priceDiff * 100 / $trades[$i-1]["close"];
          // print $priceDiff."\t".n2n($percent)."%\n";
          print $trades[$i-1]["date"]." ".$trades[$i-1]["time"] . "\t";
          print $trades[$i-1]["close"]."\t";
          print $trades[$i-1]["rsi"]."\t";
          print $trades[$i-1]["ao"]."\t";
          print "<->\t";
          print $trades[$i]["date"]." ".$trades[$i]["time"] . "\t";
          print $trades[$i]["close"]."\t";
          print $trades[$i]["rsi"]."\t";
          print $trades[$i]["ao"]."\t";
          print "=\t";
          print n2n($percent)."%";
          print "\t";
          $benfit = n2n($percent) - 1.2;
          print $benfit."%";
          $benfitDay += $benfit;
          if(isset($trades[$i]["auto"])) {
            print "\t*";
          }
          print "\n";
        }
      }

      $benfitAll += $benfitDay;

      if($analyze_per_day) {
        print "All Benfit of this symbol: " . $benfitAll."\n";
        print "-------------------------------------------------------\n";
      }
      else {
        break;
      }
      // exit();
    }
    // print_r($days);
    // exit();

    // print count($histories)."\n";
    // $histories = array_slice($histories, 14);
    // print count($histories)."\n";
    // print_r($histories);

    // if($histories === [] || $histories === null) {
    //   continue;
    // }

    // $currentDate = $histories[0]["date"];
    // $todayHistories = [];
    // $todayHistories[] = $histories[0];

    // $length = count($histories);
    // $lengthHistories = $length;

    // $firstDate = $currentDate;
    // $lastDate = $histories[$length-1]["date"];
    // $lastIndex = 1;

    // while($lastIndex < $lengthHistories) {
    //   for($i=$lastIndex;$i<$length;$i++) {
    //     if($histories[$i]["date"] === $currentDate) {
    //       $todayHistories[] = $histories[$i];
    //     }
    //     else {
    //       // $lastIndex = $i;
    //       break;
    //       // $currentDate = $histories[$i]["date"];
    //       // $i--;
    //     }
    //   }
    //   $lastIndex = $i;

      // print_r($todayHistories);

      // $trades = [];
      // $x = 0;

      // // $length = count($histories);
      // $length = count($todayHistories);
      // // foreach($todayHistories as $i=>$history) {
      // for($i=0;$i<$length;$i++) {
      //   if($i <= 16) {
      //     continue;
      //   }
      //   // print ".";
      //   $aoDiff = $todayHistories[$i]["ao"] - $todayHistories[$i-1]["ao"];
      //   $todayHistories[$i]["trade_buy"] = ($aoDiff > 0 && $todayHistories[$i]["rsi"]<40) ? true : false;
      //   $todayHistories[$i]["trade_sale"] = ($aoDiff < 0 && $todayHistories[$i]["rsi"]>60) ? true : false;
      //   // print $todayHistories[$i]["trade_buy"]."\t";
      //   // print $todayHistories[$i]["trade_sale"]."\n";
      //   if($todayHistories[$i]["trade_buy"] === true) {
      //     $type = "buy";
      //   }
      //   else if($todayHistories[$i]["trade_sale"] === true) {
      //     $type = "sale";
      //   }
      //   // print_r($todayHistories[$i]);

      //   if(isset($type)) {
      //     if(($trades === [] and $type === "buy") || ($trades !== [] and (
      //         ($trades[$x-1]["type"] === "sale" and $type !== "sale")
      //         ||
      //         ($trades[$x-1]["type"] === "buy" and $type !== "buy")
      //       )
      //     )) {
      //       $trades[$x++]=[
      //         "id"=>$i,
      //         "date"=>$todayHistories[$i]["date"],
      //         "time"=>$todayHistories[$i]["time"],
      //         "type"=>$type,
      //         "close"=>$todayHistories[$i]["close"],
      //         "volume"=>$todayHistories[$i]["volume"],
      //         "rsi"=>$todayHistories[$i]["rsi"],
      //         "ao"=>$todayHistories[$i]["ao"],
      //       ];
      //     }
      //   }
      //   unset($type);
      // }

      // if($trades === [] || $trades === null) {
      //   continue;
      // }
      // print_r($trades);
      // exit();

      // $length = count($trades);
      // // foreach($trades as $i=>$trade) {
      // for($i=0;$i<$length;$i++) {
      //   if($trades[$i]["type"] === "sale") {
      //     $priceDiff = $trades[$i]["close"] - $trades[$i-1]["close"];
      //     $percent = $priceDiff * 100 / $trades[$i-1]["close"];
      //     // print $priceDiff."\t".n2n($percent)."%\n";
      //     print $trades[$i-1]["date"]." ".$trades[$i-1]["time"] . "\t";
      //     print $trades[$i-1]["close"]."\t";
      //     print $trades[$i-1]["rsi"]."\t";
      //     print $trades[$i-1]["ao"]."\t";
      //     print "<->\t";
      //     print $trades[$i]["date"]." ".$trades[$i]["time"] . "\t";
      //     print $trades[$i]["close"]."\t";
      //     print $trades[$i]["rsi"]."\t";
      //     print $trades[$i]["ao"]."\t";
      //     print "=\t";
      //     print n2n($percent)."%\n";
      //   }
      // }
      // print_r($trades);
    // }
    print "Total Benfit of this symbol: " . $benfitAll."\n";
    $benfitTotal += $benfitAll;
  }
  print "Total Benfit of all symbol: " . $benfitTotal."\n";
}

function arg_history_list($args=[]) {
  global $db;

  $length = count($args);
  if($length === 1) {
    $regex = '/([0-9]+)-([0-9]+)-([0-9]+)/i';
    if(preg_match($regex, $args[0])) {
      $histories = $db->selects("history", ["date"=>$args[0]], "ORDER BY `epoch` DESC");
      print_histories($histories);
    }
    else {
      $histories = $db->selects("history", [], "ORDER BY `epoch` DESC");
      print_histories($histories);
    }
  }
  else {
    $histories = $db->selects("history", [], "ORDER BY `epoch` DESC");
    print_histories($histories);
  }
}

function arg_history_clear($args=[]) {
  global $db;

  $db->delete("history", []);
  print "Delete all histories.\r\n";
}

function get_last_time_of_symbol($symbolID) {
  global $db;

  // $sql = "SELECT epoch FROM $db->db.`stock` WHERE `symbolID` = '". $symbolID ."' ORDER BY epoch DESC LIMIT 1";
  // $last_time = $db->selectRaw($sql);
  $last_time = $db->select("history", ["symbolID"=>$symbolID], "ORDER BY `epoch` DESC LIMIT 1", "epoch");
  if($last_time === null || $last_time === []) {
    $startTime = strtotime( date("Y/m/d"). " " . TIME_START );
    $startTime -= $DAY_IN_BACK * DAY_IN_UNIX;
    $startTime = 0;
  }
  else {
    // print_r($last_time);
    $startTime = ((int) substr($last_time["epoch"], 0, -3)) + 1;
    // $startTime = 0; // just for testing
  }
  return $startTime;
}

function arg_history_update($args=[]) {
  global $db;
  global $debug;

  if(isset($args[0]) and $args[0] === "last") {
    // $args[0] = getEpochDate(time());
    $args[0] = strtotime( date("Y/m/d"). " " . TIME_START );
  }

  $length = count($args);
  $symbols = get_symbol_list();
  foreach($symbols as $symbol) {
    if($debug) {
      logs($symbol);
    }
    
    if($length === 1) {
        $startTime = $args[0];
    }
    else {
        $startTime = get_last_time_of_symbol($symbol["id"]);
    }
    // $startTime = 1483894017;// 947350017;
    // $startTime = 1605835337-1;
    // $endTime = 1605835337;
    $endTime = strtotime( date("Y/m/d"). " " . TIME_END );

    // https://rahavard365.com/api/chart/bars?ticker=exchange.asset%3A772%3Areal_close&resolution=1&startDateTime=1&endDateTime=1605835337&firstDataRequest=false
    update_history_symbol($symbol, $startTime, $endTime);
    sleep(3);
  }
}

function getEpochDate($epoch) {
  return date("Y-m-d", substr($epoch, 0, 10));
}

function getEpochTime($epoch) {
  return date("H:i:s", substr($epoch, 0, 10));
}

// function arg_history_updatetoday($args=[]) {
//   arg_history_update(getEpochDate(time()));
// }

/**
 Moving Average Convergence/Divergence
 */
function trade_macd($data=null, $fastPeriod=10, $slowPeriod = 5, $signalPeriod=7) {
  return trader_macd($data, $fastPeriod, $slowPeriod, $signalPeriod);
}

/**
 Rate of change ratio: (price/prevPrice)
 */
function trade_rocr($data=null, $timePeriod = 10) {
  return trader_rocr($data, $timePeriod);
}

/**
 Relative Strength Index
 **/
function trade_rsi($data=null, $timePeriod=14) {
  return trader_rsi($data, $timePeriod);
}

/**
 Awesome Oscillator
 https://github.com/joeldg/bowhead/blob/master/app/Util/Indicators.php#L431
 https://www.tradingview.com/wiki/Awesome_Oscillator_(AO)
 AO = SMA(High+Low)/2, 5 Periods) - SMA(High+Low/2, 34 Periods)
 **/
function trade_ao($data=null, $return_raw=false) {
  $data["mid"] = [];
  foreach($data["high"] as $high_key => $high_alue) {
    $data["mid"][$high_key] = (($data["high"][$high_key] + $data["low"][$high_key])/2);
  }
  $ao_sma_1 = trader_sma($data["mid"], 5);
  $ao_sma_2 = trader_sma($data["mid"], 34);
  array_pop($data["mid"]);
  $ao_sma_3 = trader_sma($data["mid"], 5);
  $ao_sma_4 = trader_sma($data["mid"], 34);
  if ($return_raw) {
    // print_r($ao_sma_1);
    // print_r($ao_sma_2);
    $r = [];
    foreach($ao_sma_1 as $i=>$v1) {
      $v2 = 0;
      if(isset($ao_sma_2[$i])) {
        $v2 = $ao_sma_2[$i];
      }
      $r [] = $v1 - $v2;
    }
    return $r;
    // return ($ao_sma_1 - $ao_sma_2); // return the actual values of the oscillator
  } else {
    $ao_prior = (array_pop($ao_sma_3) - array_pop($ao_sma_4)); // last "tick"
    $ao_now   = (array_pop($ao_sma_1) - array_pop($ao_sma_2)); // current "tick"
    /** Bullish cross */
    if ($ao_prior <= 0 && $ao_now > 0) {
      return 100;
    /** Bearish cross */
    } elseif($ao_prior >= 0 && $ao_now < 0){
      return -100;
    } else {
      return 0;
    }
  }
}

function arg_indicator_clear($args=[]) {
  global $db;

  $db->update("history", [], ["rsi"=>null, "ao"=>null]);
}

// function arg_indicator_updateLast($args=[]) {
//   arg_indicator_update("last");
// }

function arg_indicator_update($args=[]) {
  global $db;

  $paging = 10000;
  $symbols = get_symbol_list();
  foreach($symbols as $symbol) {
    $clauses = ["symbolID"=>$symbol["id"]];
    $count = $db->count("history", $clauses);
    $pageAll = ceil($count / $paging);

    if(isset($args[0]) and $args[0] === "last") {
      $page = $pageAll;
    } 
    else {
      $page = 1;
    }

    // $histories = $db->selects("history", $clauses, "ORDER BY `epoch` DESC LIMIT 100", "id,low,high,open,close");
    // print_r($histories);
    // $columns = [];
    // $columns["low"] = array_map(function($history) {
    //     return $history["low"];
    // }, $histories);

    // $columns["high"] = array_map(function($history) {
    //     return $history["high"];
    // }, $histories);
    // $columns = trade_ao($columns, true);
    // print_r($columns);

    // exit();

    // $sql = "ORDER BY `epoch` ASC";
    for(; $page <= $pageAll; $page++) {
      $offset = (($page - 1) * $paging)-15;
      if($offset < 0) {
        $offset = 0;
      }
      $sql = "ORDER BY `epoch` ASC LIMIT ".($paging+15)." OFFSET ". $offset;
      $histories = $db->selects("history", $clauses, $sql, "id,low,high,open,close");
      // print count($histories);
      // print "\t";
      // file_put_contents("temp.txt", print_r($histories, true)."\n-----\n");

      $closes = array_map(function($history) {
          return $history["close"];
      }, $histories);
      // print count($closes);
      // print "\t";
      $rsi = trade_rsi($closes, 14);
      // print count($rsi);
      // print "\t";
      // file_put_contents("temp.txt", print_r($closes, true)."\n-----\n", FILE_APPEND);
      unset($closes);

      $columns = [];
      $columns["low"] = array_map(function($history) {
          return $history["low"];
      }, $histories);

      $columns["high"] = array_map(function($history) {
          return $history["high"];
      }, $histories);
      $columns = trade_ao($columns, true);
      // print_r($columns);
      // print count($columns);
      // print "\n";
      // file_put_contents("temp.txt", print_r($columns, true)."\n-----\n", FILE_APPEND);

      $db->database->beginTransaction();
      foreach($histories as $i=>$history) {
        $values = [];
        if(isset($rsi[$i])) {
          $values["rsi"] = $rsi[$i];
        }
        // else {
        //   $values["rsi"] = null;
        // }

        if(isset($columns[$i-4])) {
          $values["ao"] = $columns[$i-4];
        }
        // else {
        //   $values["ao"] = null;
        // }
        if($values !== []) {
          $db->update("history", ["id"=>$history["id"]], $values);
        }
      }
      $db->database->commit();
      unset($rsi);
      unset($ao);
      unset($histories);
    }
  }
}

function main() {
  global $argv;

  $length = count($argv);
  if($length > 1) {
    $argv[1] = strtolower($argv[1]);
  }

  if($length === 1) {
    arg_help($argv);
  }
  else if($length > 1 and $argv[1] === "help") {
    arg_help($argv);
  }
  else if($length > 1) {
    switch($argv[1]) {
    case "analyze":
      if(!isset($argv[2])) {
        $argv[2]=null;
      }
      switch(strtolower($argv[2])) {
        case "list":
          array_shift($argv);
          array_shift($argv);
          arg_analyze_list($argv);
          break;
        default:
          arg_help($argv, true);
      }
      break;
    case "history":
      if(!isset($argv[2])) {
        $argv[2]=null;
      }
      switch(strtolower($argv[2])) {
        case "list":
          array_shift($argv);
          array_shift($argv);
          arg_history_list($argv);
          break;
        case "clear":
          array_shift($argv);
          array_shift($argv);
          arg_history_clear($argv);
          break;
        case "update":
          array_shift($argv);
          array_shift($argv);
          arg_history_update($argv);
          break;
        // case "updatetoday":
        //   array_shift($argv);
        //   array_shift($argv);
        //   arg_history_updatetoday($argv);
        //   break;
        default:
          arg_help($argv, true);
      }
      break;
    case "indicator":
      if(!isset($argv[2])) {
        $argv[2]=null;
      }
      switch(strtolower($argv[2])) {
        case "update":
          array_shift($argv);
          array_shift($argv);
          array_shift($argv);
          arg_indicator_update($argv);
          break;
        case "clear":
          array_shift($argv);
          array_shift($argv);
          array_shift($argv);
          arg_indicator_clear($argv);
          break;
        default:
          arg_help($argv, true);
      }
      break;
    case "symbol":
      if(!isset($argv[2])) {
        $argv[2]=null;
      }
      switch(strtolower($argv[2])) {
        case "list":
          array_shift($argv);
          array_shift($argv);
          arg_symbol_list($argv);
          break;
        // case "listall":
        //   array_shift($argv);
        //   array_shift($argv);
        //   arg_symbol_listall($argv);
        //   break;
        case "clear":
          array_shift($argv);
          array_shift($argv);
          arg_symbol_clear($argv);
          break;
        case "update":
          array_shift($argv);
          array_shift($argv);
          arg_symbol_update($argv);
          break;
        default:
          arg_help($argv, true);
      }
      break;
    default:
      logs("Algostock not support this argument method, you can check help section.");
      break;
    }
  }
}

main();
