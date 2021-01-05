<?php
// Max Base
require_once "base.php";

// print_r($argv);

function arg_help($args=[]) {

}

function arg_symbols($args=[]) {

}

function main() {
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
			switch(strtolower($argv[2])) {
				case "list":
					array_shift($argv);
					array_shift($argv);
					arg_symbol_list($argv);
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
				case "updatetoday":
					array_shift($argv);
					array_shift($argv);
					arg_symbol_update($argv);
					break;
				default:
					arg_help($argv);
			}
			break;
		case "symbol":
			switch($argv[2]) {
				case "list":
					array_shift($argv);
					array_shift($argv);
					arg_symbol_list($argv);
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
					arg_help($argv);
			}
			break;
		default:
			logs("Algostock not support this argument method, you can check help section.");
			break;
		}
	}
}

main();
