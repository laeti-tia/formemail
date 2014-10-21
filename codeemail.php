<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<link href="http://www.cassiopea.org/cassiopea.css" rel="stylesheet" type="text/css">
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-15">
<meta name="author" content="Antoine Delvaux antoine(at)delvaux(dot)net">
<title>Prevent email harvesting : encode your email addresses</title>
</head>

<body>

<h2>Prevent email harvesting : encode your email addresses</h2>

<h3>Use</h3>

<p>If you want to make an email address available on a web page or inside an HTML form but are concerned by this address potentially being harvested by a web robot, here is a simple solution : <em>encode it !</em>  This simple <a href="class.CodeEmail.phps">CodeEmail.php</a> class provides a very simple way of encoding email addresses that should prevent such robots to harvest your web site.

<h3>Encode or Decode</h3>

<fieldset>
<legend>input</legend>

<p>Enter here your email address and a cgi script will try to encode or decode it (depending of the actual coded state of the address).

<form name="encode-decode" method="post" action="codeemail.php">
<p>The address : <input type="text" name="addr" size="40"> <input type="submit" name="submit" value="Guess !">
</form>
</fieldset>

<fieldset>
<legend>output</legend>

<table border="0" cellspacing="5">
<?PHP
// Load needed classes
require "class.CodeEmail.php";

if (!empty($HTTP_POST_VARS["addr"])) {
  $addr = new CodeEmail($HTTP_POST_VARS["addr"]);
  print "<tr>\n<td>The clear text address is\n";
  print "<td><code>$addr->decodedAddress</code>\n</tr>\n";
  print "<tr>\n<td>The encoded address is\n";
  print "<td><code>$addr->encodedAddress</code>\n</tr>\n";
} else {
  print "<tr><td>Please fill in the address field !</tr>";
}
?>

</table>
</fieldset>
</body>
</html>
