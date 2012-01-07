<?php

/**
 * Our main Taskboard class
 */
class anonyPM {

    /**
     * PHP5 Constructor that serves no purpose
     */
    public function __construct(){
        //asdf
    }
	
	/*
	* Check who is online in a particular page.
	*/
	
	public function isOnlineUpdate( $userID_perm = array() ){
	
		foreach($userID_perm as $key => $hashID){
			// Update online statuscode
			$sql = "INSERT OR REPLACE INTO usersonline (userA, timestamp)
						VALUES (?,?)
						";
			//Create the array we will store in the database
			$sql_data = array(
				'userA' => $hashID,
				'timestamp' => time(),
			);
			//Data types to put into the database
			$sql_dataType = array(
				'userA' => 'STR',
				'timestamp' => 'INT',
			);
					
			Database::query($sql,$sql_data,$sql_dataType);
		}
		
		// DELETE any old entries
		$sql = "DELETE FROM usersonline WHERE timestamp < ".strtotime( "-10 minutes" , time() );
		Database::query($sql,array(),array());
	}

    public function openedWindowIM_Update($targetuser, $instigator){
		/*
			remove duplicate records or expired records
		*/
		$sql = "DELETE FROM openedimwindow WHERE userA = ? and userB = ? or timestamp < ".strtotime( "-30 minutes" , time() );
		//Create the array we will store in the database
		$sql_data = array(
			'userA' => $targetuser,
			'userB' => $instigator,
		);
		//Data types to put into the database
		$sql_dataType = array(
			'userA' => 'STR',
			'userB' => 'STR',
		);
		Database::query($sql,$sql_data,$sql_dataType);
	
		/*
			Update open IM window statuscode
		*/
		$sql = "INSERT OR REPLACE INTO openedimwindow (userA, userB, timestamp)
					VALUES (?,?,?)";
		//Create the array we will store in the database
		$sql_data = array(
			'userA' => $targetuser,
			'userB' => $instigator,
			'timestamp' => time()
		);
		//Data types to put into the database
		$sql_dataType = array(
			'userA' => 'STR',
			'userB' => 'STR',
			'timestamp' => 'INT'
		);
		Database::query($sql,$sql_data,$sql_dataType);
		

		
        return 1;
    }
	
	/*
		Find Who Open your IM window, and if a person is online.
	*/
    public function getstatus($userA_array=array(), $mode="isonline", $limit=10){
	
		/*
			Prepare toID
		*/
		
		// string should be placed into an array instead.
		if (!is_array($userA_array)) {$userA_array = array($userA_array);}//better use !is_array here? - yea
		// if not array, then make it an array
        if(!is_array($userA_array)) {$userA_array = array();}

        if(!empty($userA_array)){
            $sql_where_hashid = "userA IN ('".implode("','", $userA_array)."')"; // Make sure to check that its alphanumeric(plus '!') before inserting ID in.
        } else {
            $sql_where_hashid = '0=0';
        }
		
		
		/*
			construct the query
		*/
		if($mode == "isonline"){
			// filter out old entries
			$timefilter = "timestamp > ".strtotime( "-5 minutes" , time() );
			// Update open IM window statuscode
			$sql = "SELECT DISTINCT userA, timestamp 
					FROM usersonline
					WHERE
					$sql_where_hashid
					AND
					$timefilter
					ORDER BY timestamp DESC
					LIMIT ?
					";	
		} else if ($mode == "windowopen"){
			// filter out old entries
			$timefilter = "timestamp > ".strtotime( "-1 minutes" , time() );
			// Update open IM window statuscode
			$sql = "SELECT * 
					FROM openedimwindow
					WHERE
					$sql_where_hashid
					AND
					$timefilter
					ORDER BY timestamp DESC
					LIMIT ?
					";					
		}
		
		/*
			Run Query
		*/
		$rs = Database::query($sql,array("limit"=>$limit),array("limit"=>"INT"));
		
        // If something failed.. return no messages
        //if(!$rs) return array(); //Exception usually indicates some serious problem which should not be ignored!

        // TODO: Get the tags for each task!
        return $rs;
    }
	
    /**
     * Creates a new task and adds it to the database.
     * 
     * @param type $tripcode The tripcode (idk what this is)
     * @param type $title The title of the task
     * @param type $message The message the task contains
     * @param type $tags The tags this task contains
     * @return type The task id
     */
    public function sendmsg($fromID, $toID, $message, $messagetype = NULL, $expiryset = NULL, $imageBinary = NULL, $fileBinary = NULL){
        
		// Setup and create thumbnail version of imagebinary as well as the normal image
			$imagemimetype = __image_file_type_from_binary($imageBinary);
			if( ($imageBinary != NULL) && ($imagemimetype != NULL) ){
				
				// Get new sizes
				$desired_width = 50;
				$desired_height = 50;
				 
				$im = imagecreatefromstring($imageBinary);
				$new = imagecreatetruecolor($desired_width, $desired_height);

				$x = imagesx($im);
				$y = imagesy($im);

				imagecopyresampled($new, $im, 0, 0, 0, 0, $desired_width, $desired_height, $x, $y);
				imagedestroy($im);
				
				ob_start(); // Start capturing stdout. 
					imagejpeg($new, null, 75);
					$sBinaryThumbnail = ob_get_contents(); // the raw jpeg image data. 
				ob_end_clean(); // Dump the stdout so it does not screw other output.
				
				imagedestroy($new); //?? do we really need to? - Probably not, but it might be good to do it anyway (may release some memory)
			}else{$sBinaryThumbnail = NULL;}
			
		// default expiry time
		if ( isset($expiryset) ){
			$expiryset = PREG_REPLACE("/[^0-9a-zA-Z@\.+ ]/i", '', $expiryset);
			$expirytime = strtotime( $expiryset , time() );
		} else {
			$expirytime = strtotime( "+2 week" , time() );
		}
		
		//Create the array we will store in the database
        $data = array(
			'fromID' => $fromID,
			'toID' => $toID,
            'created' => time(),
            'expires' => $expirytime, // should be adjustable in the future
            'message' => $message,
			'messagetype' => strtolower($messagetype), // 'pm', 'im'
            'md5msg' => md5($message), // useful for quick searchup of messages
            'md5id' => substr(md5($message.$fromID.$toID.time()) , 0, 10), // semi-unique signiture of this post
            'image' => $imageBinary,
            'thumbnail' => $sBinaryThumbnail,
			'imagetype' => $imagemimetype,
            'file' => $fileBinary
        );

        //Data types to put into the database
        $dataType = array(
			'fromID' => 'STR',
			'toID' => 'STR',
            'created' => 'INT',
            'bumped' => 'INT',
            'title' => 'STR',
            'message' => 'STR',
			'messagetype' => 'STR', 
            'md5msg' => 'STR',
            'md5id' => 'STR',
			'image' => 'LARGEOBJECT',
			'thumbnail' => 'LARGEOBJECT',
			'imagetype' => 'STR',
			'file' => 'LARGEOBJECT'
        );

        //Insert the data
        $task_id = Database::insert('messages', $data, $dataType);
        if(!$task_id) {echo " PROBLEM SENDING MESSAGE! WHAT ARE YOU GOING TO DO ABOUT IT? D: <br/>";return false;}

        return $task_id;
    }
	
    /**
     * Either display an image or show a file from a task id.
     * 
     * @param type $id
     * @return type 
     */
    public function getFileByID($id='',$mode='image'){
		switch($mode){
			case "image":
				$sql = "SELECT DISTINCT messages.image, messages.imagetype FROM messages WHERE messages.id = $id LIMIT 1";//Bad! Don't inline variables in your query, use placeholders!
				break;
			case "thumbnail":
				$sql = "SELECT DISTINCT messages.thumbnail, messages.imagetype FROM messages WHERE messages.id = $id LIMIT 1";//Bad! Don't inline variables in your query, use placeholders!
				break;
				
			case "file":
				$sql = "SELECT DISTINCT messages.image FROM messages WHERE messages.id = $id LIMIT 1";//Bad! Don't inline variables in your query, use placeholders!
				break;
			//What happens in other cases? Should throw exception or return and not execure the query!
		}

		//Input value
		$data = array(
			'id' => $id,
		);
		//Data types of query input
		$dataType = array(
			'id' => 'INT',
		);
		
        try {
            $rs = Database::query($sql);
        } catch (Eception $e){
            echo "SQL ERROR! Something in the database has borked up..."; exit;
        }

        // If something failed.. return no messages
        if(!$rs) {echo "SQL ERROR! Does the file actually exist?";exit;}
		
		$file_assoc_array = $rs[0];
		
		switch($mode){
			case "image":
				$binary = $file_assoc_array['image'];
				$mimetype = $file_assoc_array['imagetype'];
				// Set headers
				header("Cache-Control: private, max-age=10800, pre-check=10800");
				header("Pragma: private");
				header("Expires: " . date(DATE_RFC822,strtotime(" 2 day")));
				header("Content-Type: $mimetype");
				echo $binary;
				break;
				
			case "thumbnail":
				$binary = $file_assoc_array['thumbnail'];
				// Set headers (thumbnails are always png)
				header("Cache-Control: private, max-age=10800, pre-check=10800");
				header("Pragma: private");
				header("Expires: " . date(DATE_RFC822,strtotime(" 2 day")));
				header("Content-Type: image/jpeg");
				echo $binary;
				
				/*
				// Get new sizes

				$desired_width = 50;
				$desired_height = 50;
				 
				$im = imagecreatefromstring($binary);
				$new = imagecreatetruecolor($desired_width, $desired_height);

				$x = imagesx($im);
				$y = imagesy($im);

				imagecopyresampled($new, $im, 0, 0, 0, 0, $desired_width, $desired_height, $x, $y);
				imagedestroy($im);

				header('Content-type: <span class="posthilit">image</span>/jpeg');
				imagejpeg($new, null, 85);
				*/
				
				break;
				
			case "file":
				$binary = $file_assoc_array['file'];
				$filename = $file_assoc_array['filename'];
				// Set headers
				header("Cache-Control: public");
				header("Content-Description: File Transfer");
				header("Content-Disposition: attachment; filename=$filename");
				//header("Content-Disposition: attachment; filename=\"$file\"\n"); 
				header("Content-Type: application/octet-stream");
				header("Content-Transfer-Encoding: binary");
				echo $binary;
				break;
		}
		
		exit;
		}
		

    /**
* Get a list of messages (optional tag search)
*
* @param array $tags
* @param type $limit
* @return type
*/
    public function getmessages($toID_array=array(),$fromID_array=array(), $postid=NULL, $mode="show sent post", $limit=50, $messagetype = NULL){
		
		/*
			Clear out any old expired messages
		*/
		$sql = "DELETE FROM messages WHERE expires < ".time();
		Database::query($sql,array(),array());
		
		/*
			Prepare toID
		*/
		
		// string should be placed into an array instead.
		if (!is_array($toID_array)) {$toID_array = array($toID_array);}//better use !is_array here? - yea
		// if not array, then make it an array
        if(!is_array($toID_array)) {$toID_array = array();}

        if(!empty($toID_array)){
            $sql_where_hashid = "toID IN ('".implode("','", $toID_array)."')"; // Make sure to check that its alphanumeric(plus '!') before inserting ID in.
        } else {
            $sql_where_hashid = '';
        }

		/*
			Prepare fromID
		*/
		// string should be placed into an array instead.
		if (is_string($fromID_array)) {$fromID_array = array($fromID_array);}
		// if not array, then make it an array
        if(!is_array($fromID_array)) {$fromID_array = array();}

        if(!empty($fromID_array) ){
            $sql_where_hashid = $sql_where_hashid." AND fromID IN ('".implode("','", $fromID_array)."')";
        }
		
		/*
			show sent post as well (Id from self to destination)
		*/
        if(!empty($toID_array) && !empty($fromID_array) && ($mode=="show sent post") ){
            $sql_where_hashid = $sql_where_hashid." OR ( toID IN ('".implode("','", $fromID_array)."') AND fromID IN ('".implode("','", $toID_array)."') )";
        }
		
		/*
			postid - ensure that we are displaying the right post when needed, even if its not in the top 50.
			If postid is provided, it means we want to display only that post.
		*/
        if(!empty($toID_array) && isset($postid ) && ($postid != "")){
			// enforce alphanumeric answer for md5id, to prevent sql injection
			$postid = PREG_REPLACE("/[^0-9a-zA-Z]/i", '', $postid);
			// add the post finding routine
			// REMEMBER: Check if $toID_array is really safe
			$sql_where_hashid = "( toID IN ('".implode("','", $toID_array)."') AND md5id = '".$postid."' )";
		}
		
		/*
			Some messages here are IM messages, we don't want to flood the inbox with IM messages so lets filter it out.
		*/
		if (!isset($messagetype) or !is_string($messagetype)){
			$messagetype_sql = "or messagetype = ?"; // can't do 'true' but 'or' will work for our purpose
		} else {
			$messagetype_sql = "AND messagetype = ?"; // stands for true in sql
		}
		
		
		// filter out old entries from useronline
		$timefilter = "usersonline.timestamp > ".strtotime( "-5 minutes" , time() );
		
		//Might be better to use placeholders instead of inline variables in the above IN conditions e.g. the '?' vars.
        /*Would use this except sqlite doesnt support it... : OUTER JOIN tags ON messages.id = tags.task_id */
		//						-- for sql 'case', we are checking if userA is NULL. NULL = false
        $sql = "SELECT 
						* ,	CASE WHEN usersonline.userA > 0 THEN 'online' ELSE 'offline' END AS onlinestatus
					FROM 
							messages 
						LEFT OUTER JOIN 
							(SELECT * FROM usersonline WHERE $timefilter ) AS usersonline
						ON messages.fromID = usersonline.userA
					WHERE
					$sql_where_hashid
					$messagetype_sql
					ORDER BY created DESC
					LIMIT ?";
        try {
            $rs = Database::query($sql, array($messagetype,$limit) , array("STR","INT") );
        } catch (Exception $e){
            return array();
        }
		

        // If something failed.. return no messages
        if(!$rs) return array(); //Exception usually indicates some serious problem which should not be ignored!

        // TODO: Get the tags for each task!
        return $rs;
    }

    /**
     * delete all messages from a trip (optional tag search)
     * 
     * @param array $tags
     * @param type $limit
     * @return type 
     */
    public function delete($toID_array=array(),$fromID_array=array(), $postid=NULL, $mode="delete sent post"){
		/*
			Prepare toID
		*/
		
		// string should be placed into an array instead.
		if (is_string($toID_array)) {$toID_array = array($toID_array);}
		// if not array, then make it an array
        if(!is_array($toID_array)) {$toID_array = array();}

        if(!empty($toID_array)){
            $sql_where_hashid = "toID IN ('".implode("','", $toID_array)."')"; //Use placeholders or at least check the array to prevent injection
        } else {
            $sql_where_hashid = '';
        }

		/*
			Prepare fromID
		*/
		// string should be placed into an array instead.
		if (is_string($fromID_array)) {$fromID_array = array($fromID_array);}
		// if not array, then make it an array
        if(!is_array($fromID_array)) {$fromID_array = array();}

        if(!empty($fromID_array) ){
            $sql_where_hashid = $sql_where_hashid." AND fromID IN ('".implode("','", $fromID_array)."')"; //Use placeholders or at least check the array to prevent injection
        }
		
		/*
			delete post that was sent to a particular person as well (Id from self to destination)
		*/
        if(!empty($toID_array) && !empty($fromID_array) && ($mode=="show sent post") ){
            $sql_where_hashid = $sql_where_hashid." OR ( toID IN ('".implode("','", $fromID_array)."') AND fromID IN ('".implode("','", $toID_array)."') )"; //Use placeholders or at least check the array to prevent injection
        }
		
		/*
			delete EVERY sent post (used in nuke mode)
		*/
        if(!empty($toID_array) && ($mode=="all sent post as well") ){
            $sql_where_hashid = $sql_where_hashid." OR ( fromID IN ('".implode("','", $toID_array)."') )";
        }
		
		/*
			postid - ensure that we are displaying the right post when needed, even if its not in the top 50.
			If postid is provided, it means we want to display only that post.
		*/
        if(!empty($toID_array) && isset($postid ) && ($postid != "")){
			// enforce alphanumeric answer for md5id, to prevent sql injection
			$postid = PREG_REPLACE("/[^0-9a-zA-Z]/i", '', $postid);
			// add the post finding routine
			$sql_where_hashid = "( toID IN ('".implode("','", $toID_array)."') AND md5id = '".$postid."' )";
		}

		//Should probably never DELETE if there is no where component; and maybe LIMIT 1 just to be sure
		// !!!!!!!!!!!!! YUP!
		if( $sql_where_hashid == ""){
			echo "deleting every single post in db is not allowed.";
			return;
		}
		
        /*Would use this except sqlite doesnt support it... : OUTER JOIN tags ON messages.id = tags.task_id */
        $sql = "DELETE FROM messages WHERE 
				$sql_where_hashid
			";

				
        try {
            $rs = Database::query($sql);
        } catch (Exception $e){
            return array();
        }
		

        // If something failed.. return no messages
        if(!$rs) return array();

        // TODO: Get the tags for each task!
        return $rs;
    }


    /**
     * Initializes the database.
     * 
     * @todo Clean up everything
     */
    public function initDatabase(){

        $sql = array();

        $dbType = Database::getDataBaseType();
        echo $dbType."<br/>";
        switch ( $dbType ){
			/*SQLite*/
			case "sqlite":
				$autoIncrementSyntax = "INTEGER PRIMARY KEY AUTOINCREMENT";
				break;
			default:
			/*MYSQL*/
			case "mysql":
				$autoIncrementSyntax = "INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT";
				break;
				
			//How about other cases? throw an exception?
		}

		$sql[] = <<<SQL
CREATE TABLE IF NOT EXISTS messages ( 
id $autoIncrementSyntax,

fromID VARCHAR(500),
toID VARCHAR(500),

created INT ,
expires INT ,

md5msg VARCHAR(500),
md5id VARCHAR(500),

message VARCHAR(2000),
messagetype VARCHAR(200), -- normal = normal post; im = instant messaging;

image BLOB,
thumbnail BLOB,
imagetype VARCHAR(100),
file BLOB,
filename VARCHAR(100)
);
SQL;

		$sql[] = <<<SQL
CREATE TABLE IF NOT EXISTS usersonline ( 
userA VARCHAR(500) PRIMARY KEY,
timestamp INT
);
SQL;

		$sql[] = <<<SQL
CREATE TABLE IF NOT EXISTS openedimwindow (  -- UserB is trying to reach UserA
id $autoIncrementSyntax,
userA VARCHAR(500), -- search UserA to find which UserB is trying to reach you.
userB VARCHAR(500),
timestamp INT
);
SQL;

/*			We are not using address book yet, this is the next feature we will be implementing

		$sql[] = <<<SQL
CREATE TABLE IF NOT EXISTS addressbook ( 
id $autoIncrementSyntax,
bumped INT ,
name VARCHAR(200),
contactaddress VARCHAR(200),
description VARCHAR(200),
torchat VARCHAR(200),
email VARCHAR(200)
);
SQL;
*/

		$sql[] = <<<SQL
CREATE TABLE IF NOT EXISTS settings ( 
id $autoIncrementSyntax,
name VARCHAR(200),
value VARCHAR(500),
numerical_value INT
);
SQL;


        foreach($sql as $s) {
            Database::query($s);
        }
    }
}