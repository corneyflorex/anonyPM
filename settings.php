<?php	//Import settings via require("settings.php");
	// Settings
	
	// General configuration
	$__initEnable = true; // Disable After Install (set to 'false' rather than 'true')
	$__debug = false; // Dev mode
	$__hiddenServer = false; // Hidden server should not use IP tracking as users all appear local (e.g. in tor)
	
	
	// Unique Salt
	// Please set your own salt
	$__salt = NULL;	// $__salt = "PUT RANDOM LETTERS HERE";
	if(!isset($__salt)){$__salt = hash("sha512",$_SERVER['DOCUMENT_ROOT'].$_SERVER['SERVER_SOFTWARE']);}
	
	// DATABASE CONFIG
	// CHOOSE YOUR MODE
	$settingMode = "sqlite";
	// FILL YOUR DB SETTINGS HERE
	switch($settingMode){
		case "mysql":
			$dbType		= "mysql";
			$dbHost		= "localhost";
			$dbName		= "taskboard";
			$dbuser     = "root";
			$dbpass     = "";
			$dbConnection = "host=".$dbHost.";dbname=".$dbName;
			break;
			
		case "sqlite":
			$dbType		= "sqlite";
			$dbuser     = "";
			$dbpass     = "";
			$dbConnection = "tasks.sq3";
			break;
			
	}
	
	$config = array(
					"homepage"	=>array(
										"tasks_to_show" => 10
										)
					,
					"tasks"		=>array(
										"lifespan" => 1
										)
					,
					"database"	=>array(
										"dsn" => $dbType.":".$dbConnection
										,
										"username" =>$dbuser 
										,
										"password" =>$dbpass
										)
					);
					
