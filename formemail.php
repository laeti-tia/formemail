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
 * Copyright © 2000-2002 Antoine Delvaux
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
$log_file = "/var/log/apache/php/formemail.log";
$subject = "Message from formemail";
$to = "gro#aepoissac|llun";
$from = "gro#aepoissac|php#liamemrof";

/******************************************************************************
 * PrintErrorPage($error_msg)
 * 		Description : prints an error receipt
 *		Agruments   : $error_msg : error message to report
 *		Returns     : -
 ******************************************************************************/
function PrintErrorPage($error_msg) { 
  ?> 
 <HTML> 
    <HEAD><TITLE>Error</TITLE></HEAD> 
    <BODY> 
    <DIV ALIGN=center> 
    <TABLE CELLPADDING=8>
    <TR> 
    <TD BGCOLOR=#8A8A8A><FONT SIZE=+2>Error when sending form</FONT></TD> 
    </TR>
    <TR> 
    <TD BGCOLOR=#C0C0C0><B>Use the BACK button to correct the errors.</B>
    <BR><BR>
    <?PHP print $error_msg; ?> 
    </TD> 
	</TR>
	</TABLE>            
	</DIV>           
	</BODY>
	</HTML> 
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
    <?PHP print $success_msg; ?> 
    </DIV>           
	</BODY>
	</HTML> 
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
  if($file[0] == "/") {
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
    error_log ("File doesn't exist : ".$full_path_file);
    return "";
  }
}

//--Get main posted vars
//--to field
if (!empty($HTTP_POST_VARS["to"])) {
  $addr = new CodeEmail($HTTP_POST_VARS["to"]);
  $to = $addr->decodedAddress;
} else {
  $addr = new CodeEmail($to);
  $to = $addr->decodedAddress;
}

//--subject field
if (!empty($HTTP_POST_VARS["subject"])) {
  $subject = stripslashes($HTTP_POST_VARS["subject"]);
}

//--from, name and firstname fields
if (!empty($HTTP_POST_VARS["from"])) {
  $addr = new CodeEmail($HTTP_POST_VARS["from"]);
  $from = $addr->decodedAddress;
  $name = "";
  if (!empty($HTTP_POST_VARS["name"])) {
    $name .= $HTTP_POST_VARS["name"]." ";
  }
  if (!empty($HTTP_POST_VARS["firstname"])) {
    $name .= $HTTP_POST_VARS["firstname"];
  }
  //--replyto field
  if (!empty($HTTP_POST_VARS["replyto"])) {
    $replyto = $from;
    $replytoname = $name;
  }
} else {
  $addr = new CodeEmail($from);
  $from = $addr->decodedAddress;
}

//--create email object
$mail = new Email($to, $subject, $from, $name, $replyto, $replytoname);

//--build email body and attachements
$ok = false;
if (!empty($HTTP_POST_VARS["template"])) {
  if ($file = CheckFile($HTTP_POST_VARS["template"], $DOCUMENT_ROOT, $HTTP_SERVER_VARS["HTTP_REFERER"])) {
    //--plain text template file
    $ok = $mail->loadTemplate($file,
			      $HTTP_POST_VARS,
			      "TEXT", true);
  }
}
if(!empty($HTTP_POST_VARS["TABtemplate"])) {
  if ($file = CheckFile($HTTP_POST_VARS["TABtemplate"], $DOCUMENT_ROOT, $HTTP_SERVER_VARS["HTTP_REFERER"])) {
    //--TAB delimited template file
    $ok = $mail->loadTemplate($file,
			      $HTTP_POST_VARS,
			      "TAB", true);
  }
}
if (!empty($HTTP_POST_VARS["HTMLtemplate"])) {
  if ($file = CheckFile($HTTP_POST_VARS["HTMLtemplate"], $DOCUMENT_ROOT, $HTTP_SERVER_VARS["HTTP_REFERER"])) {
    //--HTML template file
    $ok = $mail->loadTemplate($file,
			      $HTTP_POST_VARS,
			      "HTML", true);
  }
}
if (!$ok) {
  //--without template file
  $message = "";
  while (list($key, $val) = each($HTTP_POST_VARS)) { 
    if (is_array($val)) {
      //--Multiple select input field
      $message .= $key.": ";
      foreach ($val as $sub_val) {
	$message .= stripslashes($sub_val).",";
      }
      $message = substr($message, 0, strlen($message)-1)."\n\n"; 
    } else {
      $message .= $key.": ".stripslashes($val)."\n\n"; 
    }
  }
  $ok = $mail->setText($message);
}

//--Actually send email
if ($ok) {
  $ok = $mail->send();
  $referer = $HTTP_SERVER_VARS["HTTP_REFERER"];
  if (!empty($log_file)
      && file_exists($log_file)) {
    $logMsg = date("y/m/d H:i:s")." Message sent from <".$referer."> to <".$to."> with subject : '".$subject."'\n";
    error_log($logMsg, 3, $log_file);
  }
}

//--Print some debugging information
$message = "";
if (!empty($HTTP_POST_VARS["DEBUG"])) {
  reset ($HTTP_POST_VARS);
  $message .= "<P>Debugging mode turned on by user request.<P>Here are the variables received&nbsp;<P>&nbsp;\n";
  while (list($key, $val) = each($HTTP_POST_VARS)) {
    if (is_array($val)) {
      //--Multiple select input field
      $message .= "<U>".$key."</U>: ";
      foreach ($val as $sub_val) {
	$message .= $sub_val.",";
      }
      $message = substr($message, 0, strlen($message)-1)."<BR>\n";
    } else {
      $message .= "<U>".$key."</U>: ".$val; 
      if (preg_match("/.*template$/", $key)) {
	if (CheckFile($val, $DOCUMENT_ROOT, $HTTP_SERVER_VARS["HTTP_REFERER"])) {
	  $message .= " - file exists";
	} else {
	  $message .= " - <B>file doesn't exist</B>";
	}
      }
      $message .= "<BR>\n";
    }
  }
  $message .= "<P>And some more debugging output&nbsp;:\n";
  $message .= "CONTENT_TYPE: ".$HTTP_SERVER_VARS["CONTENT_TYPE"]."<BR>\n";
  $message .= "REQUEST_METHOD: ".$HTTP_SERVER_VARS["REQUEST_METHOD"]."<BR>\n";
  $message .= "DOCUMENT_ROOT: ".$DOCUMENT_ROOT."<BR>\n";
  $message .= "HTTP_REFERER: ".$HTTP_SERVER_VARS["HTTP_REFERER"]."<BR>\n";
  $message .= "Decoded ".'$'."to: ".$to."<BR>\n";
  $message .= "Decoded ".'$'."from: ".$from."<BR>\n";
}

if ($ok) {
  $redirect = $HTTP_POST_VARS["redirect"];
  $referer = $HTTP_SERVER_VARS["HTTP_REFERER"];
  if (empty($redirect) || !empty($HTTP_POST_VARS["DEBUG"])) 
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
  PrintErrorPage($message);
}
/*        
 		$required = $HTTP_POST_VARS["required"];
		$required_array = $HTTP_POST_VARS["required_array"];
		$redirect = $HTTP_POST_VARS["redirect"];
  
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
