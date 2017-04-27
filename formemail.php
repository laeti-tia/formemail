<?PHP
/******************************************************************************
 * formemail.php
 *
 * Format a mail according to fields given by an HTML form using the POST
 * method and the "application/x-www-form-urlencoded" encoding type.
 * 
 * Recognized INPUT fields :
 * 		- to				To: recipient address (mandatory)
 * 		- name				To: recipient name
 * 		- firstname			To: recipient firstname
 * 		- subject			mail subject
 * 		- from				From: recipient address
 * 		- replyto			build a reply to header with the from address
 * 		- template			plain text template file
 * 		- HTMLtemplate			HTML template file
 * 		- TABtemplate			TAB template file
 * 		- redirect			page to redirect to (absolute, relative or full url)
 * 		- required			* TO DO
 * 		
 * 		- DEBUG				prints debugging information if present
 * 							(no redirect in DEBUGGING mode)
 * 		
 * All the other INPUT fields maybe given to be replaced in the template files
 * 
 * See file CHANGES for version history.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA,
 * or go to http://www.gnu.org/copyleft/gpl.html
 *
 * See file LICENSE for full license text.
 *
 * Copyright © 2000-2014 Antoine Delvaux
 ******************************************************************************/
// Load needed classes
require "class.Email.php";
require "class.CodeEmail.php";

/******************************************************************************
 *  Configuration variables and default values
 *	$log_file : prints a message for every use in that file if specified
 *	$to : default to address
 *	$subject : default subject
 *	$from : default from address
 ******************************************************************************/
$log_file = "/var/log/apache2/formemail.log";
$subject = "Message from formemail";
$to = "gro#aepoissac|llun";
$from = "gro#aepoissac|php#liamemrof";
$name = "";
$firstname = "";
$replytoname = "";

/******************************************************************************
 * PrintErrorPage($error_msg)
 * 		Description : prints an error receipt
 *		Agruments   : $error_msg : error message to report
 *		Returns     : -
 ******************************************************************************/
function PrintErrorPage($error_msg) { 
?> 
 <html> 
    <head><title>Error</title></head> 
    <body> 
    <div align="center"> 
    <table cellpadding="8">
    <tr> 
    <td bgcolor="#8A8A8A"><font size="+2">Error when sending form</font></td> 
    </tr>
    <tr> 
    <TD BGCOLOR=#C0C0C0><B>Use the BACK button to correct the errors.</B>
    <br /><br />
    <font size="-1">formemail.php</font>
    <?= $error_msg; ?> 
    </td> 
    </tr>
    </table>            
    </div>           
    </body>
    </html> 
<?PHP 
} 

/******************************************************************************
 * PrintRobotPage($error_msg)
 * 		Description : prints a robot error page
 *		Agruments   : $error_msg : error message to report
 *		Returns     : -
 ******************************************************************************/
function PrintRobotPage($error_msg) { 
?> 
 <html> 
    <head><title>Thanks!</title></head> 
    <body> 
    <div align="center"> 
    <table cellpadding="8">
    <tr> 
    <td bgcolor="#8A8A8A"><font size="+2">You sent the form</font></td> 
    </tr>
    <tr> 
    <TD BGCOLOR=#C0C0C0><B>Thanks, however...</B>
    <br /><br />
    <?= $error_msg; ?> 
    </td> 
    </tr>
    </table>            
    </div>           
    </body>
    </html> 
<?PHP 
} 

/******************************************************************************
 * PrintSuccessPage($success_msg)
 * 		Description : prints a success receipt
 *		Agruments   : $success_msg : success message to report
 *		Returns     : -
 ******************************************************************************/
function PrintSuccessPage($success_msg) { 
  ?> 
 <HTML> 
    <HEAD><TITLE>Success</TITLE></HEAD> 
    <BODY> 
    <DIV ALIGN=center> 
    <P STYLE="SIZE=+1"><B>Thanks. Your form has been sent.</B>
    <P>&nbsp;
    <?= $success_msg; ?> 
    </div>           
    </body>
    </html> 
<?PHP 
}

/******************************************************************************
 * CheckFile($file)
 *	Desc :	check if the file exist
 *	Args :	$file : the file to check (relative or absolute path)
 *		$root : document root of the web server
 *		$referer : url refering to the calling document
 *	Returns:the absolute path to the file if it exists, an empty string otherwise
 ******************************************************************************/
function CheckFile($file, $root, $referer) {
  if ($file[0] == "/") {
    //--we have an absolute path
    $full_path_file = $root.$file;
  } else {
    //--we have a relative path to the calling page
    $url = parse_url($referer);
    $full_path_file = $root.substr($url["path"], 0, strrpos($url["path"], '/'))."/".$file;
  }
  if (file_exists($full_path_file)) {
    return $full_path_file;
  } else {
    error_log ("File doesn't exist: ".$full_path_file, 3, $log_file);
    return "";
  }
}

//--Get main posted vars
//--robot prevention
if (!empty($_POST["CassePammeur"])) {
  $logMsg = date("y/m/d H:i:s")." Abuse attempt from <".$_SERVER["HTTP_REFERER"].">.\n";
  error_log($logMsg, 3, $log_file);
  PrintRobotPage("Nice try, cool but improper use!");
  exit;
}

//--Input sanitizing
foreach ($_POST as $field_name => $field_value) {
  // check the posted field for strange chars
  switch ($field_name) {
    case "from": 
    case "to": 
    case "replyto": 
      $$field_name = $field_value;
      break;
    case "subject": 
    case "name":
    case "firstname":
    case "replytoname":
      $$field_name = stripslashes($field_value);
      break;
  }
}

//--Constructing the pseudo doc_root
$doc_root = "/srv/membres/".$_SERVER["REDIRECT_MEMBRE"]."/".$_SERVER['SERVER_NAME'];

//--Decode email addresses
$addr = new CodeEmail($to);
$to = $addr->decodedAddress;
$addr = new CodeEmail($from);
$from = $addr->decodedAddress;

//--Compose name from name and firstname fields
$name = $name.$firstname;

//--check for number of email to post to
if (substr_count($to, "@") > 5) {
  // Houston, we've got a problem...
  $logMsg = date("y/m/d H:i:s")." Too many email adresses given from <".$_SERVER["HTTP_REFERER"].">.\n";
  error_log($logMsg, 3, $log_file);
  PrintRobotPage("This form only accept 5 different email adresses to send to!");
  exit;
}

//--create email object
try {
  $mail = new Email($to, $subject, $from, $name, $replyto, $replytoname);
} catch (Exception $e) {
  // Houston, we've got a problem...
  $logMsg = date("y/m/d H:i:s")." Bad email address given from <".$_SERVER["HTTP_REFERER"]."> : ".$e->getMessage()."\n";
  error_log($logMsg, 3, $log_file);
  PrintRobotPage("One of the email address ($to, $from, $replyto) you sent us wasn't good!  Please go back to correct it.");
  exit;
}

//--build email body and attachements
$ok = false;
if (!empty($_POST["template"])) {
  if ($file = CheckFile($_POST["template"], $doc_root, $_SERVER["HTTP_REFERER"])) {
    //--plain text template file
    $ok = $mail->loadTemplate($file,
			      $_POST,
			      "TEXT", true);
  }
}
if(!empty($_POST["TABtemplate"])) {
  if ($file = CheckFile($_POST["TABtemplate"], $doc_root, $_SERVER["HTTP_REFERER"])) {
    //--TAB delimited template file
    $ok = $mail->loadTemplate($file,
			      $_POST,
			      "TAB", true);
  }
}
if (!empty($_POST["HTMLtemplate"])) {
  if ($file = CheckFile($_POST["HTMLtemplate"], $doc_root, $_SERVER["HTTP_REFERER"])) {
    //--HTML template file
    $ok = $mail->loadTemplate($file,
			      $_POST,
			      "HTML", true);
  }
}
if (!$ok) {
  //--without template file
  $message = "";
  reset($_POST);
  while (list($key, $val) = each($_POST)) { 
    if (is_array($val)) {
      //--Multiple select input field
      $message .= $key.": ";
      foreach ($val as $sub_val) {
	$message .= stripslashes($sub_val).", ";
      }
      $message = substr($message, 0, strlen($message)-1)."\n\n"; 
    } else {
      $message .= $key.": ".stripslashes($val)."\n\n"; 
    }
    //$logMsg = date("y/m/d H:i:s")." Variables posted: '".$key."' '".$val."'\n";
    //error_log($logMsg, 3, $log_file);
  }
  $ok = $mail->setText($message);
  if (!$ok) {
    //--Message is empty
    $ok = $mail->setText("\n\nMessage body is empty.\n\n");
    $logMsg = date("y/m/d H:i:s")." Empty message is being built: '".$message."'\n";
    error_log($logMsg, 3, $log_file);
  }
}

//--Actually send email
if ($ok) {
  $ok = $mail->send();
  $referer = $_SERVER["HTTP_REFERER"];
  if (!empty($log_file)
      && file_exists($log_file)) {
    $logMsg = date("y/m/d H:i:s")." Message sent from <".$referer."> to <".$to."> with subject : '".$subject."'\n";
    $status = error_log($logMsg, 3, $log_file);
  }
}

//--Print some debugging information
$message = "";
if (!empty($_POST["DEBUG"])) {
  reset ($_POST);
  $message .= "<div align=left><p>Debugging mode turned on by user request.</p><p>Here are the variables received.</p><p>&nbsp;<br />\n";
  $message .= "<p>Content of <code>\$_POST</code>:</p>\n";
  while (list($key, $val) = each($_POST)) {
    if (is_array($val)) {
      //--Multiple select input field
      $message .= "<u>".$key."</u>: ";
      foreach ($val as $sub_val) {
	$message .= $sub_val.", ";
      }
      $message = substr($message, 0, strlen($message)-1)."<br />\n";
    } else {
      $message .= "<u>".$key."</u>: ".$val; 
      if (preg_match("/.*template$/", $key)) {
	if (CheckFile($val, "/srv/membres/".$_SERVER["REDIRECT_MEMBRE"]."/".$_SERVER['SERVER_NAME'], $_SERVER["HTTP_REFERER"])) {
	  $message .= " - file exists";
	} else {
	  $message .= " - <b>file doesn't exist</b>";
	}
      }
      $message .= "<br />\n";
    }
  }
  $message .= "</p><p>Content of <code>\$_GET</code>:<br />".var_dump($_GET)."<br />\n";
  $message .= "</p><p>And some more debugging output&nbsp;:\n";
  $message .= "CONTENT_TYPE: ".$_SERVER["CONTENT_TYPE"]."<br />\n";
  $message .= "REQUEST_METHOD: ".$_SERVER["REQUEST_METHOD"]."<br />\n";
  $message .= "REMOTE_ADDR: ".$_SERVER["REMOTE_ADDR"]."<br />\n";
  $message .= "HTTP_X_FORWARDED_FOR: ".$_SERVER["HTTP_X_FORWARDED_FOR"]."<br />\n";
  $message .= "HTTP_REFERER: ".$_SERVER["HTTP_REFERER"]."<br />\n";
  $message .= "pseudo DOCUMENT_ROOT: ".$doc_root."<br />\n";
  $message .= "REDIRECT_MEMBRE: ".$_SERVER["REDIRECT_MEMBRE"]."<br />\n";
  $message .= "SCRIPT_FILENAME: ".$_SERVER["SCRIPT_FILENAME"]."<br />\n";
  $message .= "Decoded ".'$'."to: ".$to."<br />\n";
  $message .= "Decoded ".'$'."from: ".$from."<br />\n";
  $message .= "Encoded mailSubject:<br />\n<pre>\n".$mail->mailSubject."\n</pre><br />\n";
  $message .= "Encoded mailHeader:<br />\n<pre>\n".$mail->mailHeader."\n</pre><br />\n";
  $message .= "</p><p>Finally, the log message is:<br />\n<pre>\n".$logMsg."\n</pre>\n";
  $message .= "</div>\n";
}

if ($ok) {
  $redirect = $_POST["redirect"];
  $referer = $_SERVER["HTTP_REFERER"];
  if (empty($redirect) || !empty($_POST["DEBUG"])) 
    PrintSuccessPage($message);
  else {
    if($redirect[0] == "/") {
      //--we have an absolute path
      $url = parse_url($referer);
      $redir_url = $url["scheme"]."://".$url["host"].$redirect;
    } else if(preg_match("/^http[s]*:\/\//i", $redirect)) {
      //--we have a full url
      $redir_url = $redirect;
    } else {
      //--we have a relative path
      $url = parse_url($referer);
      $redir_url = $url["scheme"]."://".$url["host"].substr($url["path"], 0, strrpos($url["path"], '/'))."/".$redirect;
    }
    HEADER("Location: $redir_url"); 
  }
} else {
  //--Send error page 
  PrintErrorPage("Message wasn't send because of form error.");
}
/*        
 		$required = $_POST["required"];
		$required_array = $_POST["required_array"];
		$redirect = $_POST["redirect"];
  
		// load required variables into $required_array 
        $required = ereg_replace(" ", "", $required); 
        $required_array = array(); 
        if ($required) 
                $required_array = split(",", $required); 
         
	 	// check for required fields 
        $loop = count($required_array); 
        $error_msg = ""; 
        for ($i = 0; $i < $loop; $i++) { 
                if (!$required_array[$i]) 
                        $error_msg .= "Required Field, ".$required_array[$i].", was left blank<BR>\n"; 
        } 
*/
 
?>
