<?php
/*
	YOU WANT TO RUN THIS SCRIPT FIRST AFTER SETTING UP SETTINGS.PHP
*/


/*
	Initialize required files
*/
require_once("settings.php");
require_once("Database.php");
require_once("anonyPM.php");

/*
	Start up Database and anonyPM engine as well as the page mode select
*/

//Open up the database connection
Database::openDatabase('rw', $config['database']['dsn'], $config['database']['username'], $config['database']['password']);

//Create our anonyPM object
$board = new anonyPM();

if (!$__initEnable) {echo 'Permission Denied. init command disabled';exit;}
echo "Installing table definition... ";
$board->initDatabase();

echo "done";
