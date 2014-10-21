<?php 
/******************************************************************************
 * Email.php
 *
 * This class is used for sending emails.
 * These emails can be Plain Text, HTML, or Both. Other uses include file
 * attachments and email Templates(from a file).
 *
 * File index.php provides detailed examples.
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
/*******************************************************************************
 * Class:	Email
 * Function Listing:
 * 		Public methods :
 * 			Email($mailToAddress, $mailSubject, $mailFrom)
 * 			setTo($inAddress)
 * 			setCC($inAddress)
 * 			setBCC($inAddress)
 * 			setFrom($inAddress, $inName)
 * 			setReplyTo($inAddress, $inName)
 * 			setSubject($inSubject)
 * 			setText($inText)
 * 			setHTML($inHTML)
 * 			setAttachments($inAttachments, $doStripSlashes)
 * 			setTAB($inTAB)
 * 			loadTemplate($inFileLocation, $inHash, $inFormat, $doStripSlashes)
 * 			send()
 * 		Private methods :
 * 			checkEmail($inAddress)
 *			buildVal($val, $inFormat, $doStripSlashes)
 * 			getRandomBoundary($offset)
 * 			getContentType()
 * 			formatBaseHeader()
 * 			formatTextHeader()
 * 			formatHTMLHeader()
 * 			formatAlternativeHeader($boundary)
 * 			formatMixedHeader($boundary)
 * 			formatAttachmentPart($inFileLocation)
 * 			formatAlternativeBody($boundary)
 * 			buildMessage()
 *******************************************************************************/ 

class Email 
{
  //---Static
  var $version		= "v1.3.3 by Antoine Delvaux, Cassiopea";	// version string

  //---Properties
  var $mailTo           = "";           // array of To addresses 
  var $mailCC           = "";           // copied recipients
  var $mailBCC          = "";           // hidden recipients
  var $mailFrom         = "";           // from address
  var $mailReplyTo      = "";           // ReplyTo address
  var $mailSubject	= "";           // email subject
  var $mailText		= "";           // content of plain text message
  var $mailHTML		= "";           // content of html message
  var $mailAttachments	= "";           // array of attachments (as files)
  var $mailTAB		= "";		// tab delimited form values to be send as attachment
  var $mailBody		= "";	       	// mail body
  var $mailHeader       = "";	       	// full mail header
  var $messageIsBuild	= false;       	// flag set when complete message has been build

  //---Constants
  var $tDelim		= "~";		// template variable delimiter   ~!var~
  var $tNameStart	= "!";		// template variable name start
  var $TABcitation	= '"';		// citation char used in TAB attachement
  var $TABcitationAlt	= '`';		// alternate citation char used in TAB attachement
	
  /***************************************************************************
   * Email($mailToAddress, $mailSubject, $mailFrom = '<>')
   * 
   * Description:	constructor
   * Type:		public
   * Arguments:		$mailToAddress as string
   *				separate multiple values with comma
   * 			$mailSubject as string
   * 			$mailFrom as string
   *				defaults to <>, mail deamon, so it's never rejected
   * Returns:		true if set 
   ***************************************************************************/ 
  function Email($mailToAddress, $mailSubject, $mailFrom = "<>", $mailFromName = "", $mailReplyTo = "", $mailReplyToName = "") {
    if(!$this->setTo($mailToAddress)) {
      return;
    }
    if(!$this->setSubject($mailSubject)) {
      return;
    }
    if($mailFrom) {
      if(!$this->setFrom($mailFrom, $mailFromName)) {
        return;
      }
    }
    if($mailReplyTo) {
      if(!$this->setReplyTo($mailReplyTo, $mailReplyToName)) {
        return;
      }
    }
  }
  /***************************************************************************
   * setTo($inAddress)
   * 
   * Description:	sets the email To address
   * Type:		public
   * Arguments:		$inAddress as string
   * 			separate multiple values with comma
   * Returns:	true if set
   ***************************************************************************/ 
  function setTo($inAddress) {
    //--split addresses at commas
    $addressArray = explode(",",$inAddress);
    //--loop through each address and exit on error
    for($i=0;$i<count($addressArray);$i++){
      if($this->checkEmail($addressArray[$i])==false) return false;
    }
    //--all values are OK so implode array into string
    $this->mailTo = "<".implode($addressArray,">, <").">";
    return true;
  }
  /***************************************************************************
   * setCC($inAddress)
   * 
   * Description:	sets the email cc address
   * Type:		public
   * Arguments:		$inAddress as string
   * 			separate multiple values with comm
   * Returns:		true if set
   ***************************************************************************/
  function setCC($inAddress){
    //--split addresses at commas
    $addressArray = explode(",",$inAddress);
    //--loop through each address and exit on error
    for($i=0;$i<count($addressArray);$i++){
      if($this->checkEmail($addressArray[$i])==false) return false;
    }
    //--all values are OK so implode array into string
    $this->mailCC = "<".implode($addressArray,">, <").">";
    $this->messageIsBuild = false;
    return true;
  }
  /***************************************************************************
   * setBCC($inAddress)
   * 
   * Description:	sets the email bcc address
   * Type:		public
   * Arguments:		$inAddress as string, separate multiple values with comma 
   * Returns:		true if set
   ***************************************************************************/
  function setBCC($inAddress){
    //--split addresses at commas
    $addressArray = explode(",",$inAddress);
    //--loop through each address and exit on error
    for($i=0;$i<count($addressArray);$i++){
      if($this->checkEmail($addressArray[$i])==false) return false;
    }
    //--all values are OK so implode array into string
    $this->mailBCC = "<".implode($addressArray,">, <").">";
    $this->messageIsBuild = false;
    return true;
  }
  /***************************************************************************
   * setFrom($inAddress, $inName="")
   * 
   * Description:	sets the email FROM address
   * Type:		public
   * Arguments:		$inAddress as string (takes single email address)
   * 			$inName as string
   * Returns:		true if set
   ***************************************************************************/
  function setFrom($inAddress, $inName=""){
    if($this->checkEmail($inAddress)){
      if($inName != "") {
	$this->mailFrom = '"'.$this->qpEncode($inName).'" ';
      } else {
	$this->mailFrom = "";
      }
      $this->mailFrom .= "<".$inAddress.">";
      $this->messageIsBuild = false;
      return true;
    }
    return false;
  }
  /***************************************************************************
   * setReplyTo($inAddress, $inName="")
   * 
   * Description:	sets the email ReplyTo: address
   * Type:		public
   * Arguments:		$inAddress as string (takes single email address)
   * 			$inName as string
   * Returns:		true if set
   ***************************************************************************/
  function setReplyTo($inAddress, $inName=""){
    if($this->checkEmail($inAddress)){
      if($inName != "") {
	$this->mailReplyTo = '"'.$this->qpEncode($inName).'" ';
      } else {
	$this->mailReplyTo = "";
      }
      $this->mailReplyTo .= "<".$inAddress.">";
      $this->messageIsBuild = false;
      return true;
    }
    return false;
  }
  /***************************************************************************
   * setSubject($inSubject)
   * 
   * Description:	sets the email subject
   * Type:		public
   * Arguments:		$inSubject as string
   * Returns:		true if set
   ***************************************************************************/
  function setSubject($inSubject){
    if(strlen(trim($inSubject)) > 0){
      $this->mailSubject = ereg_replace("[\n\r\t\f]","",$inSubject);
      $this->mailSubject = $this->qpEncode($this->mailSubject);
      return true;
    }
    return false;
  }
  /***************************************************************************
   * qpEncode($inText)
   * 
   * Description:	Encode an 8 bit string into Quoted Printable string
   * Type:		public
   * Arguments:		$inText as string
   * Returns:		the encoded string
   ***************************************************************************/
  function qpEncode($inText){
    // TODO: allow for different encoding or guess the encoding from input
    $outText = '=?UTF-8?Q?';
    $outText .= ereg_replace("=[\r\n]+", "?=\r\n =?UTF-8?Q?",
      quoted_printable_encode(str_replace(' ', '_', $inText)));
    $outText .= '?=';
    return $outText;
  }
  /**************************************************************************
   * setText($inText)
   * 
   * Type:		public
   * Description:	sets the email text
   * Arguments:		$inText as string 
   * Returns:		true if set
   ***************************************************************************/
  function setText($inText){
    if(strlen(trim($inText)) > 0){
      $this->mailText = $inText;
      $this->messageIsBuild = false;
      return true;
    }
    return false;
  }
  /***************************************************************************
   * setHTML($inHTML)
   * 
   * Description:	sets the email HMTL
   * Type:		public
   * Arguments:		$inHTML as string
   * Returns:		true if set
   ***************************************************************************/
  function setHTML($inHTML){
    if(strlen(trim($inHTML)) > 0){
      $this->mailHTML = $inHTML;
      $this->messageIsBuild = false;
      return true;
    }
    return false;
  }
  /***************************************************************************
   * setAttachments($inAttachments)
   * 
   * Description:	stores the Attachment string
   * Type:		public
   * Arguments:		$inAttachments as string with directory included
   * 	            	separate multiple values with comma
   * Returns:		true if stored
   ***************************************************************************/
  function setAttachments($inAttachments){
    if(strlen(trim($inAttachments)) > 0){
      $this->mailAttachments = $inAttachments;
      $this->messageIsBuild = false;
      return true;
    }               
    return false;
  }
  /***************************************************************************
   * setTAB($inTAB)
   * 
   * Description:	builds and stores the Attachment full text
   * Type:		private
   * Arguments:		$inTAB as string
   * Returns:		true if stored
   ***************************************************************************/
  function setTAB($inTAB){
    if(strlen(trim($inTAB)) > 0){
      $this->mailTAB = $inTAB;
      $this->messageIsBuild = false;
      return true;
    }               
    return false;
  }
  /***************************************************************************
   * checkEmail($inAddress)
   * 
   * Description:	checks for valid email
   * Type:		private
   * Arguments:		$inAddress as string
   * Returns:		true if valid
   ***************************************************************************/
  function checkEmail($inAddress){
    return (ereg( "^[^@ ]+@([a-zA-Z0-9\-]+\.)+([a-zA-Z0-9\-]+)\$",$inAddress));
  }
  /***************************************************************************
   * buildVal($val, $inFormat, $doStripSlashes)
   * 
   * Description:	builds formated value to replace variable name
   * Type:		private
   * Arguments:		$val : the value of the variable
   * 			$inFormat as string either "TEXT", "HTML" or "TAB"
   *			$doStripSlashes strip slashes added before special chars in the values
   * Returns:		the value build
   ***************************************************************************/
  function buildVal($val, $inFormat, $doStripSlashes) {
    //--(string) casts all values as "strings"
    if ($doStripSlashes) {
      $strVal = stripslashes((string)$val);
    } else {
      $strVal = (string)$val;
    }
    if (strtoupper($inFormat)=="HTML") {
      $outVal = htmlentities($strVal);
    } else if ((strtoupper($inFormat)=="TAB") && (strstr($strVal, "\t") || strstr($strVal, "\n"))) {
      // surround with TAB_citation char and double TAB_citation inside
      $outVal = $this->TABcitation.str_replace($this->TABcitation, $this->TABcitationAlt, $strVal).$this->TABcitation;
    } else {
      $outVal = $strVal;
    }
    return $outVal;
  }
  /***************************************************************************
   * loadTemplate($inFileLocation, $inHash, $inFormat, $doStripSlashes)
   * 
   * Description:	reads in a template file and replaces hash values
   * Type:		public
   * Arguments:		$inFileLocation as string with relative directory
   * 			$inHash as Hash with populated values
   * 			$inFormat as string either "TEXT", "HTML" or "TAB"
   *			$doStripSlashes strip slashes added before special chars in the values
   * Returns:		true if loaded
   ***************************************************************************/
  function loadTemplate($inFileLocation, $inHash, $inFormat = "TEXT", $doStripSlashes = true){
    //--set out string
    $templateOut = "";
    //--open template file
    if ($templateFile = fopen($inFileLocation,"r")) {
      //--loop through file, line by line
      while (!feof($templateFile)){
	//--get 1000 chars or (line break internal to fgets)
	$templateLine = fgets($templateFile,1000);
	//--split line into array of variables arround delimiters
	$templateLineArray = explode($this->tDelim,$templateLine);
	//--loop through lines
	for ($i=0; $i<count($templateLineArray);$i++){
	  //--look for variable start at position 0
	  if (strcspn($templateLineArray[$i],$this->tNameStart)==0) {
	    //--get variable name
	    $hashName = substr($templateLineArray[$i],1);
	    $value = $inHash[$hashName];
	    //--replace with variable value
	    if (is_array($value)) {
	      //--multiple select input field case
	      $templateLineArray[$i] = "";
	      foreach ($value as $subVal) {
		//--loops through the multiple values
		$templateLineArray[$i] .= $this->buildVal($subVal, $inFormat, $doStripSlashes).",";
	      }
	      $templateLineArray[$i] = substr($templateLineArray[$i], 0, strlen($templateLineArray[$i])-1);
	    } else {
	      $templateLineArray[$i] = $this->buildVal($value, $inFormat, $doStripSlashes);
	    }
	  }
	}
	//--output array as string and add to out string
	$templateOut .= implode($templateLineArray,"");
      }
      //--close file          
      fclose($templateFile);
      //--set Mail body to proper format
      if (strtoupper($inFormat)=="TEXT") {
	return($this->setText($templateOut));
      }
      else if (strtoupper($inFormat)=="HTML") {
	return($this->setHTML($templateOut));
      }
      else if (strtoupper($inFormat)=="TAB") {
	//--set attachement as string in memory
	return($this->setTAB($templateOut));
      }
    }
    return false;
  }
  /**************************************************************************
   * getRandomBoundary($offset)
   * 
   * Description:	returns a random boundary
   * Type:		private
   * Arguments:	$offset as integer - used for multiple calls
   * Returns:		string
   ***************************************************************************/
  function getRandomBoundary($offset = 0){
    //--seed random number generator
    srand(time()+$offset);
    //--return md5 32 bits plus 4 dashes to make 38 chars
    return ("----=_Next".(md5(rand())));
  }
  /***************************************************************************
   * getContentType($inFileName)
   * 
   * Description:	returns content type for the file type
   * Type:		private
   * Arguments:	$inFileName as file name string (can include path)
   * Returns:	string
   ***************************************************************************/
  function getContentType($inFileName){
    //--strip path
    $inFileName = basename($inFileName);
    //--check for no extension
    if(strrchr($inFileName,".") == false){
      return "application/octet-stream";
    }
    //--get extension and check cases
    $extension = strrchr($inFileName,".");
    switch($extension) {
    case ".gif":
      return "image/gif";
    case ".gz":
      return "application/x-gzip";
    case ".htm":
      return "text/html"; 
    case ".html":
      return "text/html"; 
    case ".jpg":
      return "image/jpeg"; 
    case ".tar":
      return "application/x-tar";
    case ".txt":
      return "text/plain";
    case ".zip":
      return "application/zip";
    case ".pdf":
      return "application/pdf";
    default:
      return "application/octet-stream"; 
    }
    return "application/octet-stream";
  }
  /***************************************************************************
   * formatBaseHeader
   * 
   * Description:	returns a full formated header
   * Type:		private
   * Arguments:	none
   * Returns:		string
   ***************************************************************************/
  function formatBaseHeader() {
    //--set  mail header to blank
    $outBaseHeader = "";
    //--add CC
    if($this->mailCC != "") {
      $outBaseHeader .= "Cc: ".$this->mailCC."\n";
    }
    //--add BCC 
    if($this->mailBCC != "") {
      $outBaseHeader .= "Bcc: ".$this->mailBCC."\n"; 
    }
    //--add From 
    if($this->mailFrom != "") {
      $outBaseHeader .= "From: ".$this->mailFrom."\n"; 
    }
    //--add ReplyTo
    if($this->mailReplyTo != "") {
      $outBaseHeader .= "ReplyTo: ".$this->mailReplyTo."\n"; 
    }
    //--set MIME-Version 
    $outBaseHeader .= "MIME-Version: 1.0\n"; 
    $outBaseHeader .= "X-Mailer: Email.php class ".$this->version."\n";
    return $outBaseHeader;
  }
  /****************************************************************************
   * formatTextHeader
   * 
   * Description:	returns a formated header for text
   * Type:		private
   * Arguments:	none
   * Returns:		string
   ***************************************************************************/
  function formatTextHeader(){
    $outTextHeader = "Content-Type: text/plain;\n";
    // no need for charset header in email as it can be included in the HTML page
    $outTextHeader .= "Content-Transfer-Encoding: 8bit";
    return $outTextHeader;
  }
  /***************************************************************************
   * formatHTMLHeader
   * 
   * Description:	returns a formated header for HTML
   * Type:		private
   * Arguments:	none
   * Returns:		string
   ***************************************************************************/
  function formatHTMLHeader(){
    $outHTMLHeader = "Content-Type: text/html; charset=iso-8859-1\n";
    $outHTMLHeader .= "Content-Transfer-Encoding: 8bit";
    return $outHTMLHeader;
  }
  /***************************************************************************
   * formatTABHeader
   * 
   * Description:	returns a formated header for a TAB separeted velus attachment
   * Type:		private
   * Arguments:	none
   * Returns:		string
   ***************************************************************************/
  function formatTABHeader(){
    $uniqueFileName = "val".time().".tsv";
    $outTABHeader .= "Content-Type: text/tab-separated-values;\n";
    $outTABHeader .= '    name="'.$uniqueFileName.'"'."\n";
    $outTABHeader .= "Content-Transfer-Encoding: 8bit\n";
    $outTABHeader .= "Content-Disposition: attachment;\n";
    $outTABHeader .= '    filename="'.$uniqueFileName.'"'."\n\n";
    return $outTABHeader;
  }
  /***************************************************************************
   * formatAlternativeHeader($boundary)
   * 
   * Description:	returns a formated header for multipart/alternative Content-Type
   * Type:		private
   * Arguments:	$boundary : boundary used to seperate TEXT and HTML parts
   * Returns:		string
   ***************************************************************************/
  function formatAlternativeHeader($boundary) {
    $outAlternativeHeader = "";
    $outAlternativeHeader .= "Content-Type: multipart/alternative;\n"; 
    $outAlternativeHeader .= '    boundary="'.$boundary.'"'."\n"; 
    return $outAlternativeHeader;
  }
  /***************************************************************************
   * formatMixedHeader($boundary)
   * 
   * Description:	returns a formated header for multipart/mixed Content-Type
   * Type:		private
   * Arguments:	$boundary : boundary used to seperate attached parts
   * Returns:		string
   ***************************************************************************/
  function formatMixedHeader($boundary) {
    $outMixedHeader = "";
    $outMixedHeader .= "Content-Type: multipart/mixed;\n";
    $outMixedHeader .= '    boundary="'.$boundary.'"'."\n"; 
    $outMixedHeader .= "This is a multi-part message in MIME format.\n"; 
    $outMixedHeader .= "--".$boundary."\n"; 
    return $outMixedHeader;
  }
  /***************************************************************************
   * formatAttachmentPart($inFileLocation) 
   * 
   * Description:	returns a formated header for an attachment
   * Type:		private
   * Arguments:	$inFileLocation as string with relative directory
   * Returns:		string
   ***************************************************************************/
  function formatAttachmentPart($inFileLocation){
    $outAttachmentPart = "";
    //--get content type based on file extension
    $contentType = $this->getContentType($inFileLocation);
    //--if content type is TEXT the standard 7bit encoding
    if(ereg("text",$contentType)){
      //--format header
      $outAttachmentPart .= "Content-Type: ".$contentType.";\n";
      $outAttachmentPart .= '    name="'.basename($inFileLocation).'"'."\n";
      $outAttachmentPart .= "Content-Transfer-Encoding: 8bit\n";
      $outAttachmentPart .= "Content-Disposition: attachment;\n";   //--other: inline
      $outAttachmentPart .= '    filename="'.basename($inFileLocation).'"'."\n\n";
      $textFile = fopen($inFileLocation,"r");
      if($textFile) {
	//--loop through file, line by line
	while(!feof($textFile)){
	  //--get 1000 chars or (line break internal to fgets)
	  $outAttachmentPart .= fgets($textFile,1000);
	}
	//--close file          
	fclose($textFile);
      }
      $outAttachmentPart .= "\n";
    }
    //--NON-TEXT use 64-bit encoding
    else{
      //--format header
      $outAttachmentPart .= "Content-Type: ".$contentType.";\n";
      $outAttachmentPart .= '    name="'.basename($inFileLocation).'"'."\n";
      $outAttachmentPart .= "Content-Transfer-Encoding: base64\n";
      $outAttachmentPart .= "Content-Disposition: attachment;\n";   //--other: inline
      $outAttachmentPart .= '    filename="'.basename($inFileLocation).'"'."\n\n";
      //--call uuencode - output is returned to the return array
      exec("uuencode -m $inFileLocation nothing_out",$returnArray);
      //--add each line returned
      for ($i=1;$i<(count($returnArray));$i++){
	$outAttachmentPart .= $returnArray[$i]."\n";
      }
    }
    return $outAttachmentPart;
  }
  /***************************************************************************
   * formatAlternativeBody($boundary)
   * 
   * Description:	returns a formated mail body with TEXT and HTML
   * Type:		private
   * Arguments:	$boundary : body parts boundary
   * Returns:		string
   ***************************************************************************/
  function formatAlternativeBody($boundary) {
    $outBody = "--".$boundary."\n";
    $outBody .= $this->formatTextHeader()."\n\n";
    $outBody .= $this->mailText."\n";
    $outBody .= "--".$boundary."\n"; 
    $outBody .= $this->formatHTMLHeader()."\n\n";
    $outBody .= $this->mailHTML."\n";
    $outBody .= "\n--".$boundary."--";
    return $outBody;
  }
  /***************************************************************************
   * buildMessage()
   * 
   * Description:	builds the complete message (header + body)
   * Type:		private
   * Arguments:	none
   * Returns:		true if message is build
   ***************************************************************************/
  function buildMessage() {
    //--build base header
    $this->mailHeader = $this->formatBaseHeader();
		
    //--FORMAT HEADER WITH ATTACHEMENTS
    if (($this->mailAttachments != "") || ($this->mailTAB != "")) {
      //--get random boundary for attachments 
      $attachmentBoundary = $this->getRandomBoundary(); 
      //--build header for all parts with boundary
      $this->mailHeader .= $this->formatMixedHeader($attachmentBoundary);
    }
		
    //---------------------------MESSAGE TYPE-------------------------------
    if ($this->mailHTML == "") {
      //--TEXT ONLY
      $this->mailHeader .= $this->formatTextHeader();
      $this->mailBody = $this->mailText;
    } else {
      if ($this->mailText == "") {
	//--HTML ONLY
	$this->mailHeader .= $this->formatHTMLHeader();
	$this->mailBody = $this->mailHTML;
      } else {
	//--TEXT and HTML
	//--get random boundary for content types 
	$bodyBoundary = $this->getRandomBoundary(1);
	//--build header and body with boundary
	$this->mailHeader .= $this->formatAlternativeHeader($bodyBoundary);
	$this->mailBody = $this->formatAlternativeBody($bodyBoundary);
      }
    }
    //--FORMAT BODY WITH TAB ATTACHMENT
    if ($this->mailTAB != "") {
      //--attachment separator and attachment
      $this->mailBody .= "\n--".$attachmentBoundary."\n";
      $this->mailBody .= $this->formatTABHeader();
      $this->mailBody .= $this->mailTAB; 
      $this->mailBody .= "\n--".$attachmentBoundary."--";
    }
    //--FORMAT BODY WITH ATTACHMENTS
    if ($this->mailAttachments != "") {
      //--get array of attachment filenames 
      $attachmentArray = explode(",",$this->mailAttachments); 
      //--loop through each attachment 
      for($i=0;$i<count($attachmentArray);$i++){ 
	//--attachment separator 
	$this->mailBody .= "\n--".$attachmentBoundary."\n"; 
	//--get attachment info 
	$this->mailBody .= $this->formatAttachmentPart($attachmentArray[$i]); 
      } 
      $this->mailBody .= "\n--".$attachmentBoundary."--"; 
    }
    return true;
  }		
  /***************************************************************************
   * send()
   * 
   * Description:	sends the email
   * Type:		public
   * Arguments:	none
   * Returns:		true if sent
   ***************************************************************************/
  function send(){
    if (!$this->messageIsBuild) {
      //--builds the message if not already built
      //--the To: and Subject: fields can be changed and the message sent
      //--again without the need to rebuild it.  So better performance.
      $this->messageIsBuild = $this->buildMessage();
    }
    //--send message 
    return mail($this->mailTo, $this->mailSubject, $this->mailBody, $this->mailHeader); 
  } 
} 
?>
