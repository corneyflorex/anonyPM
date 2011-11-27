<?php

// Deals with the annoying problem of 'get_magic_quotes_gpc' in some shared hosting
if ( in_array( strtolower( ini_get( 'magic_quotes_gpc' ) ), array( '1', 'on' ) ) )
{
    $_POST = array_map( 'stripslashes', $_POST );
    $_GET = array_map( 'stripslashes', $_GET );
    $_COOKIE = array_map( 'stripslashes', $_COOKIE );
}

/*
	Initialize required files
*/
require_once("settings.php");
require_once("Database.php");
require_once("anonyPM.php");
require_once("anonregkit.php");





/*
	Important core functions
*/

// This system stores messages as username and password
// Multiple levels of security is provided from full text, to limited, to public
function __hashID($user,$password="") {
	GLOBAL $__salt;
	
	//addslashes in the case that we are inserting into an sql statement
	$user = addslashes($user);
	$password = addslashes($password);
	// enforce alphanumeric answer for user, to prevent stuff like $user="badcode!sef32fw3"
	$user = PREG_REPLACE("/[^0-9a-zA-Z@\.]/i", '', $user);
	
	$posthashed = hash("sha256",$user.$__salt.$password.$__salt);
	//password only hash will always have a '!' in front of it, to prevent people from typing the hash in the username
	// in order to bypass the password for no username passworded inboxes.
	// e.g. !dsafda will not recognize "dsafda" entered in the username box
	
	if( isset($password) and $password != "" ){
		for( $i = strlen($posthashed) ; $i>=0 ; $i-- ){
			$hashIDstrings[] = $user."!".substr($posthashed,0,$i);
		}
		$hashIDstrings[] = $user;

	} else {
		$hashIDstrings[] = $user;
	}
	return $hashIDstrings;
}

function __hashID_as_perm($fullHashID) {
	// enforce alphanumeric answer for fromID, to prevent stuff like $fromID="badcode!sef32fw3"
	$fullHashID = PREG_REPLACE("/[^0-9a-zA-Z@\.!]/i", '', $fullHashID);
	$senderIDparts = explode('!', $fullHashID);
	// generate permutations of the sender's id.
	// senderIDparts[1]-password ; senderIDparts[0] -username
	$sender_array = array();
	if( isset($senderIDparts[1]) and $senderIDparts[1] != "" ){
		for( $i = strlen($senderIDparts[1]) ; $i>=0 ; $i-- ){
			$sender_array[] = $senderIDparts[0]."!".substr($senderIDparts[1],0,$i);
		}
		$sender_array[] = $senderIDparts[0];
	}else {
		$sender_array[] = $senderIDparts[0];
	}
	//return permutations
	return $sender_array;
}

// This function is to help assist in visual IDing of names.
function __colourHash($content, $size=3){
	$hash1 = md5($content); $hash2 = md5($content."!"); $hash3 = md5($content."#");
	$string = "";
	for($i=0; $i<$size; $i++){
		$colorHash1 = "#".substr($hash1,$i*6,6); 
		$colorHash2 = "#".substr($hash2,$i*6,6); 
		$colorHash3 = "#".substr($hash3,$i*6,6);
		$string .= "<span style='color:$colorHash1; background-color:$colorHash1; border-bottom:2px solid $colorHash2;border-top:2px solid $colorHash3;'>_</span>";
	}	
	return $string;
}

// Displays any message you get
	function __MsgDisplay($Msgs){
		$DisplayContent ="";
		if (empty($Msgs)){
			return "<div class='cloudbox' style='width:100%; text-align:center'>
						<h2>No Messages</h2>
					</div>
			";
		}
		foreach($Msgs as $msg){
			
			//thumbnail routines
			/*
			if($msg['imagetype'] != NULL){
				$thumbnailHTML = "<a href='?x=image&id".$msg['id']."'><img border='0' src='?x=image&id".$msg['id']."&mode=thumbnail' /></a>";
			} else {
				$thumbnailHTML = "";
			}
			*/
				$thumbnailHTML = "";

	
			//Current user ID
			if(isset($_SESSION['userID'])){
				$currUserID = $_SESSION['userID'];
			}else{
				$currUserID = $msg['toID'];
			}
			
			//Display fromID if defined
			if(isset($msg['fromID']) AND ($msg['fromID'] != "")){
				/* Display online status*/
				if ($msg['onlinestatus'] == 'online'){
					$onlinestatus = "<b>".$msg['onlinestatus']."</b>";
				} else {
					$onlinestatus = $msg['onlinestatus'];
				}
				/* Display fromID info*/
				$fromIDdisplay = "From: ".substr(htmlentities(stripslashes($msg['fromID']),null, 'utf-8'),0,30)."... 
								(<a href='?x=PAGE+writemessage&to=". htmlentities(urlencode($msg['fromID']),null, 'utf-8')."&from=". htmlentities(urlencode($currUserID),null, 'utf-8')."' target='_blank'>Reply</a>) 
								(<a target='_top' href='?x=convo&fromID=".$msg['fromID'].__SID_URL()."#".$msg['md5id']."' >View Thread</a>)
								-- $onlinestatus 
								<br/>
								";
			}else{
				$fromIDdisplay = "From: Anonymous (No Address)<br/>";
			}
			
			$DisplayContent = $DisplayContent."
				<div class='cloudbox' style='width:100%; text-align:left'>
					<div style=' text-align:right; position:relative; float:right; padding:20px'>
						 | <b> ".__humanTiming ($msg['created'])." </b> ago | Created: ".date('M j, Y', $msg['created'])."
						</br>
						Expires: ".date('M j, Y', $msg['expires'])."
					</div>
					<div style='display: inline-block;float:right; margin-right:10px;' >$thumbnailHTML</div>
										
					<div style='padding:20px' class='title'>
						<p>".__colourHash($msg['fromID'])." --> ".__colourHash($msg['toID'])."</p>
						To: ".substr(htmlentities($msg['toID'],null, 'utf-8'),0,30)."...
						<br/>
						$fromIDdisplay
						<p class='message'>Message: ".__cut_text( htmlentities($msg['message'],null, 'utf-8') , 500 )."
							(<a target='_top' href='?x=convo&fromID=".htmlentities(stripslashes($msg['fromID'])).__SID_URL()."&postid=".htmlentities(stripslashes($msg['md5id']))."#".htmlentities(stripslashes($msg['md5id']))."' >View Msg</a>)
						</p>
					</div>
					
					<div style='clear:both;'>
					</div>
					
				</div>";
		};
		return $DisplayContent;
	}
	


// Displays any message you get
	function __MsgFullDisplay($Msgs){
		$DisplayContent ="";		
		if (empty($Msgs)){
			return "<div class='cloudbox' style='width:100%; text-align:center'>
						<h2>Cannot Find Message</h2>
					</div>
			";
		}
		foreach($Msgs as $msg){
			
			//image routines
			/*
			if($msg['imagetype'] != NULL){
				$imageHTML = "<div class='cloudbox' margin-right:10px;' ><a href='?q=/image/".$msg['task_id']."'><img border='0' src='?q=/image/".$msg['task_id']."' style='max-width:100%'/></a></div>";
			} else {
				$imageHTML = "";
			}
			*/
			$imageHTML = "";
			
			//Current user ID
			if(isset($_SESSION['userID'])){
				$currUserID = $_SESSION['userID'];
			}else{
				$currUserID = $msg['toID'];
			}
			
			//Display fromID if defined
			if(isset($msg['fromID']) AND ($msg['fromID'] != "")){
				/* Display online status*/
				if ($msg['onlinestatus'] == 'online'){
					$onlinestatus = "<b>".$msg['onlinestatus']."</b>";
				} else {
					$onlinestatus = $msg['onlinestatus'];
				}
				/* Display fromID info */
				$fromIDdisplay = "<p>From: ".htmlentities(stripslashes($msg['fromID']),null, 'utf-8')." (<a href='?x=PAGE+writemessage&to=".htmlentities(urlencode($msg['fromID']),null, 'utf-8')."&from=".htmlentities(urlencode($currUserID),null, 'utf-8')."' target='_blank'>Reply</a>) -- $onlinestatus </p>";
			}else{
				$fromIDdisplay = "<p>From: Anonymous (No Address)</p>";
			}
			
			$DisplayContent = $DisplayContent."
				<div class='cloudbox' style='text-align:left; padding:20px' >
				
					<a id='".$msg['md5id']."' name='".$msg['md5id']."'></a>
					
					<div style='float:right; text-align:right;'>
						 | ".__humanTiming ($msg['created'])." ago | Created: ".date('M j, Y', $msg['created'])."
						</br>
						Expires:".date('M j, Y', $msg['expires'])."
					</div>
					
					$imageHTML
					
					<br>
					
					<p>".__colourHash($msg['fromID'])." --> ".__colourHash($msg['toID'])." | 'sender' --> 'receiver'</p>
					<p>To: ".htmlentities($msg['toID'],null, 'utf-8')."</p>
					$fromIDdisplay
					
					<span style='display: inline-block; text-align:left; padding:10px' class='title'>
						<span class='message'>".nl2br(
												__encodeTextStyle(htmlentities(
													$msg['message'] 
												,null, 'utf-8'))
											)."</span>
					</span>
					
					<div style='clear:both;'>
					</div>
					
				</div>";
		};
		return $DisplayContent;
	}

/*
	Start up Database and anonyPM engine as well as the page mode select
*/

//Open up the database connection
Database::openDatabase('rw', $config['database']['dsn'], $config['database']['username'], $config['database']['password']);

//Create our anonyPM object
$board = new anonyPM();

//empty page mode array (for layout.php)
$mode = array();

/*
	setup session
*/
// session system to help store not yet approved 'files'
// or images, while capcha is being processed.
ini_set("session.use_cookies",0	);
ini_set("session.use_only_cookies",0);
//ini_set("session.use_trans_sid",1);

// grab session id
if(isset($_GET['PHPSESSID'])){
	session_start($_GET['PHPSESSID']);
} else {
	session_start();
}

// Checked if its first time logged in, or is inactive for too long
if( !isset($_SESSION['loggedin']) or !isset($_SESSION['expiry']) or ($_SESSION['expiry']<time()) ){
    $_SESSION['loggedin'] = false;
} else if (isset($_SESSION['loggedin']) AND ($_SESSION['loggedin'] == true)) {
	// if logged in, then update expiry time and also check into sql status table
	$_SESSION['expiry'] = strtotime( "+1 hour" , time() );
	$board->isOnlineUpdate($_SESSION['userID_perm']);
}

/*
	Dedicated Session function. Used to work out if session ID should be inserted into URL
*/
function __loggingIn($userHashID) {
	$_SESSION['loggedin'] = true;
	$_SESSION['expiry'] = strtotime( "+1 hour" , time() );
	$_SESSION['userID'] = $userHashID[0];
	$_SESSION['userID_perm'] = $userHashID; //userID_permutations
	return true;
}

function __SID_URL($mode="onlogin") {
	if($mode == "onlogin"){
		if( !isset($_SESSION['loggedin']) or !($_SESSION['loggedin'] == true) ){
			return "";
		}
	}
	return "&".htmlspecialchars(SID);
}

/*
	Read and Execute commands from x
*/

/*
How does this system work? it works via x being the 'command line input'

PLAN:
write this system backend as if it was semi-commandline
*/
$query = isset($_GET['x']) ? $_GET['x'] : ' ';
$query_parts = explode(' ', trim($query, ' '));

switch(strtolower($query_parts[0])){
		
	case 'logout':
		$_SESSION['loggedin'] = NULL;
		$_SESSION['expiry'] = NULL;
		$_SESSION['userID'] = NULL;
		$_SESSION['userID_perm'] = NULL;
		session_destroy();
		echo "Logged Out";
		$mode = array('home');
		break;
		
	// show specific pages
	case 'page':
		switch(strtolower($query_parts[1])){
		
			case 'about':
				$mode = array('about');
				break;
				
			case 'writemessage':
				$mode = array('messageform');
				break;
				
			case 'addressbook':
				echo "feature not yet supported";
				$mode = array('addressbook');
				break;
		}
		break;
	
	case 'send':
		// grab addresses from (sender) and (receiver)
	
		if(isset($query_parts[1]))
			{$toID=$query_parts[1];}
		else if(isset($_POST['to']))
			{$toID=$_POST['to'];}
		else
			{echo "Must have a receiver";exit;}
			
		if(isset($query_parts[2]))
			{$fromID=$query_parts[2];}
		else if(isset($_POST['from']))
			{$fromID=$_POST['from'];}
		else
			{$fromID=NULL;}
			
		//Grab message content
		if( isset($query_parts[3]) and ($query_parts[3] != ""))
			{
				$msg = "";
				for( $i = 3 ; isset($query_parts[$i]) ; $i++){
					$msg = $msg.$query_parts[$i]." ";
				}
			}
		else if(isset($_POST['message']) and ($_POST['message'] != ""))
			{$msg=$_POST['message'];}
		else
			{echo "Must have a message";exit;}
		
		// Filter out any non alphanumeric entry in (receiver) or (sender)
		$fromID = PREG_REPLACE("/[^0-9a-zA-Z@\.!]/i", '', $fromID);
		$toID = PREG_REPLACE("/[^0-9a-zA-Z@\.!]/i", '', $toID);
		
		$newMsgID = $board->sendmsg($fromID, $toID, $msg, $imageBinary = NULL, $fileBinary = NULL);
		//echo "msgID: ".$newMsgID."<br>";
		echo htmlentities("To: $toID", null, 'utf-8')."<br>".htmlentities("From: $fromID", null, 'utf-8')."<br>".htmlentities("msg: $msg", null, 'utf-8');
		break;
		
	case 'inbox':
		// grab username and password
		if(isset($query_parts[1]))
			{$username=$query_parts[1];}
		else if(isset($_POST['user']))
			{$username=$_POST['user'];}
		else
			{$username="";}
		
		if(isset($query_parts[2]))
			{$password=$query_parts[2];}
		else if(isset($_POST['pass']))
			{$password=$_POST['pass'];}
		else
			{$password = "";}
			
		/* if empty login e.g. x=INBOX, then check if there is already a session*/
		if($username == "" and $password == "" ){
			if($_SESSION['loggedin'] == true){
				$hashid_array = $_SESSION['userID_perm'];
			} else {
				echo "empty username and password";
				exit;
			}
		} else {
			$hashid_array = __hashID($username,$password);
			__loggingIn($hashid_array);
			$board->isOnlineUpdate($_SESSION['userID_perm']);

		}
				
		$msgs = $board->getmessages($hashid_array);
				
		// Show inbox layout
		$mode = array('inbox');
		break;
		
	/* view a conversation thread from a 'fromID'*/
	case 'convo':
		/* if empty login e.g. x=INBOX, then check if there is already a session*/
		if($_SESSION['loggedin'] == true){
			/*
				grab toID from currenly logged in user
			*/
			$hashid_array = $_SESSION['userID_perm'];
			
			/*
				Grab postid
			*/
			//if(isset($_GET['postid'])){ $postid = $_GET['postid']; }else{ $postid = NULL; } // EQUIV
			$postid = isset($_GET['postid']) ? $_GET['postid'] : NULL;
			
			/*
				obtain and prepare the fromID variable from user
			*/
			if( !isset($_GET['fromID']) ) { die("lacking fromID"); }
			$sender_array = __hashID_as_perm($_GET['fromID']);
			
			/*
				Grab the post
			*/
			// toid are addressed to 'you', while fromid is from sender.
			$msgs = $board->getmessages($hashid_array, $sender_array, $postid, "show sent post");
			$mode[] = 'convo';

		} else {
			echo "Who are you? You are not logged in. I don't know you.";
			exit;
		}
		break;	
	
	case 'delete':
		break;
		
	case 'nuke':
		// grab username and password
		if(isset($query_parts[1]))
			{$username=$query_parts[1];}
		else if(isset($_POST['user']))
			{$username=$_POST['user'];}
		else
			{$username="";}
		// enforce alphanumeric answer for user, to prevent stuff like $user="badcode!sef32fw3"
		$username = PREG_REPLACE("/[^0-9a-zA-Z]/i", '', $username);
		
		if(isset($query_parts[2]))
			{$password=$query_parts[2];}
		else if(isset($_POST['pass']))
			{$password=$_POST['pass'];}
		else
			{$password = "";}
			
		/* if empty login e.g. x=NUKE, then check if there is already a session*/
		if($username == "" and $password == "" ){
			echo "Syntax is x=NUKE+username+password; You did not enter username or password;";
			if($_SESSION['loggedin'] == true){
				echo "<br>BTW: To nuke currently logged in accounts, type or click <a href='?x=NUKE+yes".__SID_URL()."'>?x=NUKE+yes;</a>";
			}
			exit;
		} else if($query_parts[1] == "yes") { 
			if($_SESSION['loggedin'] == true){
				$hashid_array = $_SESSION['userID_perm'];
			} else {
				$hashid_array = __hashID($username,$password);
			}
		} else {
			$hashid_array = __hashID($username,$password);
		}
		
		/*
			nuke all user's post
		*/
		
		$msgs = $board->delete($hashid_array, NULL, NULL, "all sent post as well");
		echo "Inbox totally, totally cleared";
		break;
		
	case 'whoisonline':
		$msgs = $board->getstatus(array(), "isonline", 100);
			var_dump( $msgs );
		break;

	default:
		$mode = array('home');
		break;
}

//show skin
$mode[] = 'loggedinheader';
$mode[] = 'querywindow';

require("layout.php");

?>