<?php
/*
 * @Name: Algo Stock
 * @Author: Max Base
 * @Date: 2021-01-05
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
  $url = 'https://rahavard365.com/api/chart/bars?ticker=exchange.asset%3A'. $symbol["rahavardID"] .'%3Areal_close%3Atype1&resolution=1&startDateTime='. $startTime .'&endDateTime='. $endTime .'&firstDataRequest=true';
  $url = 'https://rahavard365.com/api/chart/bars?ticker=exchange.asset%3A'. $symbol["rahavardID"] .'%3Areal_close&resolution=1&startDateTime='. $startTime .'&endDateTime='. $endTime .'&firstDataRequest=true';
  $url = 'https://rahavard365.com/api/chart/bars?ticker=exchange.asset%3A591%3Areal_close%3Atype1&resolution=1&startDateTime=1&endDateTime=1609747736&firstDataRequest=false';
  if($debug) {
    logs($url);
  }
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
  $result = request("get", $url, $headers);
  // print_r($result);
  if($result) {
    $json = json_decode($result, true);
    if(isset($json["noData"])) {
      logs("The empty JSON");
    }
    else {
      logs("JSON Length: ".strlen($result));
      if($json === null) {
        logs("JSON Response is not valid!");
      }
      else {
        logs("JSON Response is good!");

        $db->database->beginTransaction();
        foreach($json as $item) {
          if(is_array($item)) {
            print ".";
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
              $db->update("history", $clauses, $values);
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

  $symbols = $db->selectsRaw("SELECT * FROM $db->db.`symbol` WHERE `rahavardID` IS NOT NULL AND `tsetmcID` IS NOT NULL AND `tsetmcINS` IS NOT NULL;");
  return $symbols;
}

function arg_symbol_list($args=[]) {
  $symbols = get_symbol_list();
  print_symbols($symbols);
}

function arg_symbol_listall($args=[]) {
  global $db;

  $symbols = $db->selectsRaw("SELECT * FROM $db->db.`symbol`;");
  print_symbols($symbols);
}

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

  $length = count($args);
  $symbols = get_symbol_list();
  $symbols = [ $db->select("symbol", ["name"=>"شبندر"]) ];
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
    $endTime = strtotime( date("Y/m/d"). " " . TIME_END );
    $startTime = 0;

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

function arg_history_updatetoday($args=[]) {
  arg_history_update(getEpochDate(time()));
}

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

function arg_indicator_update($args=[]) {
  global $db;

  $symbols = get_symbol_list();
  $symbols = [ $db->select("symbol", ["name"=>"شبندر"]) ];
  foreach($symbols as $symbol) {
    $histories = $db->selects("history", ["symbolID"=>$symbol["id"]], "ORDER BY `epoch` ASC", "id,price");
    print count($histories);

    $prices = array_map(function($history) {
        return $history["price"];
    }, $histories);
    print count($prices);

    $rsi = trade_rsi($prices, 14);
    // $ao = trade_ao($prices, true);
    // print_r($rsi);

    $db->database->beginTransaction();
    foreach($histories as $i=>$history) {
      $values = [];

      if(isset($rsi[$i])) {
        $values["rsi"] = $rsi[$i];
      }
      else {
        $values["rsi"] = null;
      }

      // if(isset($ao[$i])) {
      //   $values["ao"] = $ao[$i];
      // }
      // else {
      //   $values["ao"] = null;
      // }

      $db->update("history", ["id"=>$history["id"]], $values);
    }
    $db->database->commit();
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
        case "updatetoday":
          array_shift($argv);
          array_shift($argv);
          arg_history_updatetoday($argv);
          break;
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
          arg_indicator_update($argv);
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
        case "listall":
          array_shift($argv);
          array_shift($argv);
          arg_symbol_listall($argv);
          break;
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
