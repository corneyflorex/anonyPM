<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" ></meta>
<meta charset="UTF-8"/>

<title>AnonyPM</title>

<link rel="icon" href="favicon.ico" type="image/x-icon">
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon"> 

<link rel="stylesheet" media="screen and (min-width: 480px)" href="css/styles.css" type="text/css" />


</head>



<body>
	<div class="center">
	
		<!-- Home Page -->
		<?php if (in_array("home", $mode)) { ?>
		
		<div style="position: absolute; top: 40%; width:760px">
			<div class="cloudbox" style="margin: 0 auto; padding: 20px; width:400px">

				<a href="?<?php echo __SID_URL()?>" ><h1>AnonyPM</h1></a> No registration required
			
				<br>
				
				<FORM action='?x=inbox<?php echo __SID_URL()?>' method="POST" enctype='multipart/form-data'>
					<input type='text' name='user' value='' placeholder='Username'>
					#
					<input type='password' name='pass' value='' placeholder='Password'>
					<br />
					<INPUT type='submit' value='Enter Inbox'> 
				</FORM>
				<br>
				<?php 
				//generate a new potential inbox name
				$wordbit = array('axe','aye','ark','air','doc','emu','gal','gar','git','hoe','gig','fab','wiz','sew','rub','man','map','mad','uke','vow','kiss','blob','blind','barf','chin','coup','claw','croc','cope');
				shuffle($wordbit);
				$newusername = $wordbit[0].$wordbit[1].substr(rand(),0,rand(2,4));
				$newpassword = substr(md5(rand()),0,rand(6,12));
				$newuserpassperm = __hashID($newusername,$newpassword);
				?>
				<h4>Can't think of a user/pass combination?:</h4>
				<p> <?php echo __colourHash($newuserpassperm[0]).' User: '.$newusername.' | Pass: '.$newpassword?> | (<a href='<?php echo "?x=INBOX+$newusername+$newpassword" ?>' target='_blank'>Open</a>)</p>
				<br>
				
				<a href="?x=PAGE+about<?php echo __SID_URL()?>" >About</a> | <a href="?x=PAGE+writemessage<?php echo __SID_URL()?>" >Write Message</a> | <a href="?x=PAGE+addressbook<?php echo __SID_URL()?>" >AddressBook</a> | <a href="#query" >Query</a>
			</div>			
		</div>
		<?php } ?>
	
		<!-- header Page -->
		<?php if (!( in_array("home", $mode) or in_array("noheader", $mode) ) ) { ?>
		
		<div class="cloudbox" style="margin: 0 auto; width:100%; padding-top:20px; padding-bottom:20px; ">
			<a href="?<?php echo __SID_URL()?>" ><h1>AnonyPM</h1></a> No registration required
			<br>
			<a href="?x=PAGE+about<?php echo __SID_URL()?>" >About</a> | <a href="?x=PAGE+writemessage<?php echo __SID_URL()?>" >Write Message</a> | <a href="?x=PAGE+addressbook<?php echo __SID_URL()?>" >AddressBook</a> | <a href="#query" >Query</a>
		</div>			
		<?php } ?>
		
		<!-- Logged in Header -->
		<?php if (in_array("loggedinheader", $mode)) { 
			if( isset($_SESSION['loggedin']) and ($_SESSION['loggedin'] == true) ){
		?>
				<div class="cloudbox" style="margin: 0 auto; width:100%; padding-top:10px; padding-bottom:10px; ">
					
					<h3><a href="?x=INBOX<?php echo __SID_URL()?>" >Check Inbox</a> | <a href='?x=PAGE+writemessage&from=<?php echo $_SESSION['userID'];?><?php echo __SID_URL()?>' target='_blank'>Send PM</a> |  <a href="?x=LOGOUT<?php echo __SID_URL()?>" >logout</a> | <a href="?x=NUKE<?php echo __SID_URL()?>" >Nuke/Clear Inbox</a></h3>
					
					<br>
					<h3 style="text-align:center;" >Your full AnonyPM Address is:</h3>
					<h4><?php echo  __colourHash($_SESSION['userID'])." ".htmlentities($_SESSION['userID']);?></h3>
					<h5><a href='?x=PAGE+writemessage&to=<?php echo  htmlentities(urlencode($_SESSION['userID']));?>' target='_blank'>Give this url to other people, so they can message you</a> | <a href='?x=imtalk&userB=<?php echo  htmlentities(urlencode($_SESSION['userID']));?>' target='_blank'>Share this link for Instant Messaging.</a></h5>
					<div>
						<iframe style="float:right; margin: 5px; border-style:none; overflow-y: scroll; overflow-x: hidden;" src="?x=notification<?php echo __SID_URL()?>" width="300px" height="80px">
							<p>Your browser does not support iframes. SOOOO Therefor you cannot use IM ;_; </p>
						</iframe> 
					</div>
					<?php
						$userIDparts = explode('!', $_SESSION['userID']);
						if(isset($userIDparts[1])){ 
							$userid_lv1 = $userIDparts[0]."!".substr($userIDparts[1],0,10);
							echo "<div style='padding:20px; text-align:left; ' >";
							echo "Also valid: ";
							echo __colourHash($userid_lv1)." ( <a href='?x=PAGE+writemessage&to=".$userid_lv1."' target='_blank'>PM</a> | <a href='?x=imtalk&userB=".$userid_lv1."' target='_blank'>IM</a> ) ".$userid_lv1."<br>";
							echo "</div>";
						}
					?>
					<div style="clear:both;"></div>

				</div>			 
		<?php 
			}
		} ?>
	
		<!-- about -->
		<?php if (in_array("about", $mode)) { ?>
		
		<div style="position: absolute; top: 40%; width:760px;">
			<div class="cloudbox" style="margin: 0 auto; padding: 20px; width:500px">

				<h1>AnonyPM</h1> No registration required
			
				<br>
				
				<p>Hi this is AnonyPM, a new experimental PM system, that requires no registration.</p>
				<br>
				<p>It is based off the concept of SimplePM in </p> <p>http://4v6veu7nsxklglnu.onion/SimplePM.php</p>
				<br>
				<p>AnonyPM aims to support both sqlite and mysql, and to support Addressbook, as well as instant messaging.</p>
				
				<br>
				
				<hr>
				<h3>Why I cannot offer encryption or any kind of security (CONTENT FROM SIMPLEPM)</h3>
				<div class="instructions">It seems that some people believe I should have offered some kind of encryption or security for this service. The reason I will NEVER offer security is simple:	<b>I don't want to make people think they are more secure than they actually are.</b><br><br>
				I cannot think of any scheme that would protect your privacy. First of all, no matter what encryption scheme I use, you can never be sure that I have actually implemented any kind of security. The fact that SimplePM gives you the source code of the site, doesn't mean that it's the actual code. I could easily make it serve source code that has the encryption scheme enabled while in reality the server would run an insecure version of the site. Also any scheme that relies on the server doing the encryption is pointless since the server operator can always sniff messages right before they are encrypted by the web server. The only secure way would be to do the encryption on the client side. This could possibly be done with JavaScript by the browser but this violates the basic principle of SimplePM: no javascript. Also it's pointless since you would have to constantly check if your browser is actually running the correct javascript using some tool like FireBug (remember that I can always change the javascript without notice). But you are willing to go to such lengths to secure your communication (checking that the correct javascript runs like a maniac every time you send or read a message) why not use GPG in the first place which is surely much more secure? So no matter what I implement it's a matter of whether you trust me and my host (FreedomHosting) in the first place. If you don't trust me then use GPG (and even then do not forget about possible MITM attacks while exchanging public keys). If you trust me then why should I even implement any practically fake security?<br><br>You shouldn't feel safe just because someone says that he encrypts you messages, after all you can never know if he actually does! Always use end to end encryption as this is the only way for real security and authentication.<br><br>
				Of course the anonymity provided by SimplePM is obviously real. You can make as many inboxes as you want (they don't even take space in the database) and you can even send messages anonymously to any inbox.</div>
				
			</div>
		</div>
		<?php } ?>

		<!--Submit field-->
		<?php if (in_array("messageform", $mode)) { ?>
		<div class="cloudbox"  style="margin: 0 auto; width:100%; padding-top:10px; padding-bottom:10px; ">
			<FORM action='?x=send<?php echo __SID_URL()?>' method='post' enctype='multipart/form-data'>
				<P>
					Send To*:<br /> <INPUT type='text' size=50 name='to' value='<?php if(isset($_REQUEST['to'])){echo htmlentities($_REQUEST['to']);}?>'><br />
					Sender ID or email (optional):<br /> <INPUT type='text' size=50 name='from' value='<?php if(isset($_REQUEST['from'])){echo htmlentities($_REQUEST['from']);}?>'><br />
					
					Expiry Setting (optional):
						<select name='expiryset' >
						  <option value="+4 weeks">4 weeks</option>
						  <option value="+4 weeks"></option>
						  <option value="+1 hour">1 hour</option>
						  <option value="+1 day">1 day</option>
						  <option value="+1 week">1 week</option>
						  <option value="+2 weeks">2 weeks</option>
						  <option value="+4 weeks">4 weeks</option>
						  <option value="+1 year">1 year</option>
						  <option value="+60 seconds">60 sec</option>
						</select>
					<br />
					
					Message*:<br />	<textarea class='' rows=10 cols=50 name='message' autofocus><?php if(isset($_REQUEST['message'])){echo htmlentities($_REQUEST['message'],null, 'utf-8');}?></textarea><br />			
					<!-- 
					<label for='file'>Image:</label><br /> <input type='file' name='image' />
					-->
					<br /><INPUT type='submit' value='Send'>
				</P>
			</FORM>
		</div>
		<?php } ?>
		<!--Submit field-->
		
		<!--inbox-->
		<?php if (in_array("inbox", $mode)) { ?>

			<div id="msgDIV" class="msglist">
				<?php echo __MsgDisplay($msgs);?>
			</div>
			
		<?php } ?>
		<!--List of task-->
		
		<!--Conversation View-->
		<?php if (in_array("convo", $mode)) { ?>

			<div id="msgDIV" class="msglist">
				<?php echo __MsgFullDisplay($msgs);?>
			</div>
			
		<?php } ?>
		<!--List of task-->

		<!-- query window -->
		<?php if (in_array("querywindow", $mode)) { ?>
			<style>
					a.query{display:none;}
					a.query:target  { display:inline; text-decoration:none;}
					.queryinput {
						border: 1px solid #006;
						background: #ffc;
					}
					.querybutton {
						border: 1px solid #006;
						background: #9cf;
					}
			</style>
			<a class='query' name="query" style="" >
				<FORM action='' method="get" enctype='multipart/form-data'>
					<INPUT class="querybutton" type='submit' value='query'> 
					<input class="queryinput" type='text' size=100 name='x' value='' placeholder='Enter Query'>
					<input type='hidden' name='PHPSESSID' value='<?php echo session_id(); ?>'>
				</FORM>
				
				<br>
				<p>
				SEND (To) (From) (message) - send a message<br>
					e.g. x=SEND+sdfsd!sdfsd+wefewfe!dfsdf+hello+how+are+you+today 
				</p>
				<p>
				INBOX (username) (password) - check inbox<br>
					e.g. x=INBOX+myusername+mypassword
				<p/>
				<p>
				NUKE (username) (password) - clear inbox<br>
					e.g. x=NUKE+myusername+mypassword
				</p>
				<p>
				PAGE (pagetype)<br>
					e.g. x=PAGE+about
				</p>
			</a>
		<?php } ?>
		
		<!-- Instant Messaging Window -->
		<?php if (in_array("imtalk", $mode)) { ?>
		<div class="cloudbox"  style="margin: 0 auto; width:100%; padding-top:10px; padding-bottom:10px; ">
			<p>
				<?php echo "You: ".__colourHash($userA).htmlentities(stripslashes(substr($userA,0,20)))."..."; ?>
				<?php echo "<br> are talking to : ".__colourHash($userB).htmlentities(stripslashes(substr($userB,0,20)))."..."; ?>
			</p>
			<p>
				<FORM action='?x=imtalk<?php echo "&userA=".htmlentities($userA)."&userB=".htmlentities($userB).__SID_URL()?>' method='post'>		
					MSG: <INPUT type='text' size='90%' name='message' autofocus />
					<!-- <textarea rows="2" cols="90%" name='message' autofocus ></textarea> -->			
					<INPUT type='submit' value='Send'>
				</FORM>
			</p>
			<iframe src="?x=imtalk&displaymode=msgdisplay<?php echo htmlentities("&userA=".urlencode($userA)."&userB=".urlencode($userB)).__SID_URL()?>" width="100%" height="1000">
			  <p>Your browser does not support iframes. SOOOO Therefor you cannot use IM ;_; </p>
			</iframe> 
		</div>
		<?php } ?>
		<!-- Instant Messaging Window -->
		
		<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>
		
	</div>		
</body>
</html>