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

    /**
     * Creates a new task and adds it to the database.
     * 
     * @param type $tripcode The tripcode (idk what this is)
     * @param type $title The title of the task
     * @param type $message The message the task contains
     * @param type $tags The tags this task contains
     * @return type The task id
     */
    public function sendmsg($fromID, $toID, $message, $imageBinary = NULL, $fileBinary = NULL){
        
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
				
				//imagedestroy($new); //?? do we really need to?
			}else{$sBinaryThumbnail = NULL;}
		//Create the array we will store in the database
        $data = array(
			'fromID' => $fromID,
			'toID' => $toID,
            'created' => time(),
            'expires' => strtotime( "+4 week" , time() ), // should be adjustable in the future
            'message' => $message,
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
     * Deletes a task by either ID or Tripcode
     * NOTE: This does not remove any entries that are still in the 'tags' table
     * 
     * @param type $delType
     * @param array $input 
     */
    public function delTaskBy($delType, $input=array()) {
        if(!is_array($input)) $input = array(); // Input array is always zero

        switch($delType){
            case 'Delete a post':    // $input <-- post ID
                $s_id = $input[0];
                $sql[] = "DELETE FROM messages WHERE md5id = ?";
				$data[] = array( $s_id );
                $sql_data[] = array(
                    'id' => $s_id,
                );
                $sql_type[] = array(
                    'id' => 'INT'
                );
				
                break;

            case 'Delete all post by trip':    // $input <-- Tripcode name ##DANGER## This will delete everything done by a poster
                $s_pass = $input[0];
                $sql[] = "DELETE FROM messages WHERE toid =  ?";
				$sql_data[] = array(
                    'tripcode' => $s_pass,
                );
                $sql_type[] = array(
                    'tripcode' => 'STR'
                );
                break;

            case 'Delete single task with normal password': // $input <-- Task ID, Task Password
                $s_ID = $input[0];
                $s_pass = $input[1] ;
                $sql[] = "DELETE FROM messages WHERE toid = ? AND md5id= ?";
				$sql_data[] = array(
                    'id' => $s_ID,
                    'tripcode' => $s_pass,
                );
                $sql_type[] = array(
                    'id' => 'INT',
                    'tripcode' => 'STR'
                );
                break;
            
            default:
                echo '\n No action taken as there was an unknown delete option chosen for delTaskBy()\n';
                break;
        }
		     
			 
        try {
            foreach($sql as  $row_num => $s) {
                Database::query($s,$sql_data[$row_num],$sql_type[$row_num]);
                echo 'Delete command sent';
            }
        } catch(PDOException $e) {
            echo $e;
            echo 'Delete Operation failed, did you get your password wrong?';
        }
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
				$sql = "SELECT DISTINCT messages.image, messages.imagetype FROM messages WHERE messages.id = $id LIMIT 1";
				break;
			case "thumbnail":
				$sql = "SELECT DISTINCT messages.thumbnail, messages.imagetype FROM messages WHERE messages.id = $id LIMIT 1";
				break;
				
			case "file":
				$sql = "SELECT DISTINCT messages.image FROM messages WHERE messages.id = $id LIMIT 1";
				break;
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
    public function getmessages($toID_array=array(),$fromID_array=array(), $postid=NULL, $mode="show sent post", $limit=50){
		/*
			Prepare toID
		*/
		
		// string should be placed into an array instead.
		if (is_string($toID_array)) {$toID_array = array($toID_array);}
		// if not array, then make it an array
        if(!is_array($toID_array)) {$toID_array = array();}

        if(!empty($toID_array)){
            $sql_where_hashid = "toID IN ('".implode("','", $toID_array)."')";
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
			$sql_where_hashid = "( toID IN ('".implode("','", $toID_array)."') AND md5id = '".$postid."' )";
		}

        /*Would use this except sqlite doesnt support it... : OUTER JOIN tags ON messages.id = tags.task_id */
        $sql = "SELECT *
            FROM messages
            WHERE
            $sql_where_hashid
            ORDER BY created DESC
            LIMIT ?";

        try {
            $rs = Database::query($sql, array($limit) , array("INT") );
        } catch (Exception $e){
            return array();
        }
		

        // If something failed.. return no messages
        if(!$rs) return array();

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
            $sql_where_hashid = "toID IN ('".implode("','", $toID_array)."')";
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
			delete post that was sent to a particular person as well (Id from self to destination)
		*/
        if(!empty($toID_array) && !empty($fromID_array) && ($mode=="show sent post") ){
            $sql_where_hashid = $sql_where_hashid." OR ( toID IN ('".implode("','", $fromID_array)."') AND fromID IN ('".implode("','", $toID_array)."') )";
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

--title VARCHAR(100), -- No title, to reduce hiding spam
message VARCHAR(2000),

image BLOB,
thumbnail BLOB,
imagetype VARCHAR(100),
file BLOB,
filename VARCHAR(100)
);
SQL;

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