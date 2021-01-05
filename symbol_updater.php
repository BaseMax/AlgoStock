<?php
require_once "base.php";

function tehran1() {
	global $db;
	$db->database->beginTransaction();
	$res = getData("http://www.tsetmc.com/tsev2/data/MarketWatchInit.aspx?h=0&r=0");
	if($res !== "") {
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

function tehran2() {
	global $db;
	$db->database->beginTransaction();
	$res = getData("http://www.tsetmc.com/Loader.aspx?ParTree=111C1417");
	if($res !== "") {
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

function rahavard1() {
	global $db;
	$db->database->beginTransaction();
	$res = getData("https://rahavard365.com/stock?last_trade=any");
	if($res !== "") {
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

// Tehran
tehran1();
tehran2();

// Rahavard
rahavard1();

print "Done updating.\n";
