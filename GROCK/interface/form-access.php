<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>GROCK - web interface protein docking over the EGEE grid</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="css/grock.css" rel="stylesheet" type="text/css">
</head>

<body bgcolor="#FFCC66">


<form name="docking-form" enctype="multipart/form-data" method="POST" action="../script/processor.php">

<br />
<table border="0" align="center" cellpadding="0" cellspacing="0" class="negro" >
  <tr>
		
    <td><img src="Images/grock_01.gif" width="600" height="67" alt="cabecera"></td>
		
	</tr>
	
  <tr> 
    <td class="cajaalta" >		
	
    <div class="help"><a href="JavaScript:openNewWindow('./help.html','Help',650,200)" class="enlaceseco">[ ? HELP]</a></div>
    <div class="textonormal"> 
           
	 
	  <br />
          Insert User Login <br /><input type="text" name="user_login" size="25" value="<? $_POST["user_login"] ?>">
    	  <br />
	  <br />
          Insert User Password <br /><input type="password" name="user_password" size="25" value="<? $_POST["user_password"] ?>">
    	  <br />
          <br />
          Insert User Cert Passphrase <br /><input type="password" name="passphrase" size="25" value="<? $_POST["passphrase"] ?>">
    	  <br />
	  <br />
          Receptor molecule in PDB format<br />
          <input type="file" name="upload[0]" size="40" >
	  
          <!-- <input type="hidden" name="MAX_FILE_SIZE" value="3000000"> -->
          <br />
          <br />
          Database to search<br />
            <select name="database">
            <option selected value="pdbtest">PDB test database (19 mols)
            <option value="pdb40">PDB (non-redundant 40%, 6139 mols)
            <!-- <option value="pdb50">PDB (non-redundant 50%, 6872 mols) -->
            <option value="pdb65">PDB (non-redundant 65%, 7872 mols)
            <option value="pdb90">PDB (non-redundant 90%, 9467 mols)
            <option value="pdb">PDB (full)
            <option value=" ">
            <option value="msdchem">MSD Chemical compounds
            <option value="hic-up">HIC-Up compounds
            <option value="zinc-lead">ZINC lead compounds
          </select>
    	  
	  <!-- Displays dinamically the docker options -->
	  <script language="JavaScript">
    	  function showdocker(str)
    	  { 
	    var grammoptions="<br />GRAMM Resolution docking<br /><br /><span class=\"negro\">Low</span><input type=\"radio\" name=\"resolution\" value=\"low\" checked><span class=\"negro\">High</span><input type=\"radio\" name=\"resolution\" value=\"high\">"
	    
	    var ftdockoptions="FTDOCK"
	    
	    switch (str)
	    {
	    
    	    	case "gramm":
	    	//alert(str);
		document.getElementById("docker-options").innerHTML=grammoptions;
		break;
		case "ftdock":
    	    	//alert(str);
		document.getElementById("docker-options").innerHTML=ftdockoptions;
		break;
    	    	default : 
		document.getElementById("docker-options").innerHTML=grammoptions;
    	    }
	
          } 
          </script>
    	  <br />
	  <br /> Select a docker to run GROCK<br /><br />
	  <select name="docker" onchange="showdocker(this.value)" >
	  <option value="ftdock">FTDock
	  <option selected value="gramm">GRAMM
	  </select>
	
    	  <div class="textonormal" id="docker-options">
	  <br />
	  GRAMM Resolution docking<br />
          <span class="negro">Low</span>
          <input type="radio" name="resolution" value="low" checked>
          <span class="negro">High</span>
          <input type="radio" name="resolution" value="high">
	  </div>
	  
    	  
	  <br />
    	  <center><input type="submit" name="submit" value="New GROCK session"></center>
	  </form>  
          <br />
          <br />
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
