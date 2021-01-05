<?php
require_once "base.php";

$symbols = $db->selectsRaw("SELECT * FROM $db->db.`symbol` WHERE `rahavardID` IS NOT NULL AND `tsetmcID` IS NOT NULL AND `tsetmcINS` IS NOT NULL;");
print_r($symbols);

