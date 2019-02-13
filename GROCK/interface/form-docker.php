<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>GROCK - web interface protein docking over the EGEE grid</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="css/grock.css" rel="stylesheet" type="text/css">

</head>

<body bgcolor="#FFCC66">
<form name="form" enctype="multipart/form-data" method="POST" action="./form.php">

<br />
<table border="0" align="center" cellpadding="0" cellspacing="0" class="negro" >
  <tr>
		
    <td><img src="Images/grock_01.gif" width="600" height="67" alt="cabecera"></td>
		
	</tr>
	
  <tr> 
    <td class="cajaalta" >		
	
    <div class="help"><a href="JavaScript:openNewWindow('./help.html','Help',650,200)" class="enlaceseco">[ ? HELP]</a></div>
    <div class="textonormal"> 
          <?
	  if ($_POST["passphrase"] == "")
	  {
	    echo "Please, browse back and insert a passphrase!<br>";
	    echo "<br />
	    <br />	
	    <br />
	    <br />
	    <br />
	    <br />	
	    <br />
	    <br />
	    <br />";
	    //exit;
	  }else
	  {
	    //We create a session to access the user grid passphrase
	    session_start();
	    $_SESSION['passphrase']  = $_POST["passphrase"];
	  ?>
	    Select a docker to run GROCK<br />
	    <br />
	    <select name="docker">
	    	<option selected value=""> -- Choose --
		<option value="ftdock">FTDock
	        <option value="gramm">GRAMM
	    </select>
	    <br />
	    <br />	
	    <br />
	    <br />
            <input type="submit" name="submit" value="Enter Grock">
            <br />
            <br />
            <br />
	  <?
	   }
	  ?>
        <!-- New functionality -->
	
	
        <div class="linea"></div></div>
	  </td>
		
	</tr>
	<tr>
	
		
    <td class="cajabaja"> 
      <div class="letrapeque">If you have any trouble, please contact our <a href="/cgi-bin/emailto?Bioinformatics+Administrator" class="enlaceseco">Bioinformatics 
      Administrator</a> | <a href="./Copyright-CSIC.html" class="enlaceseco">&copy; EMBnet/CNB</a><br /><br />
        <a href="http://validator.w3.org/check?uri=referer" ><img border="0" src="valid-html401.png"alt="Valid HTML 4.01!" height="31" width="88"> 
        </a> <a href="mailto:cajanegra@ya.com"><img src="Images/design-natxo.png" alt="" width="45" height="31" border=0></a> 
      </div>
	  </td>
	</tr>
</table>
<p class="help">&nbsp;</p>
<p class="textonormal">&nbsp;</p>
</form>
</body>
</html>
