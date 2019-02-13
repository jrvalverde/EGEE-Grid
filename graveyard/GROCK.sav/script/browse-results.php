<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">

<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" >
	<title>GROCK Results</title>
</head>
<body>
<?
require_once("./config.php");
require_once("./grid.php");

$receptor=$_GET["receptor"];
$unpacked="./unpacked.txt";

/**
 * HTTP path
 */
$server= 'http://anarchy.cnb.uam.es';
$path=substr($receptor, 17, strlen($receptor)); 
$http_path="$server/$path/OUTPUT/test_x.outputdir";

/**
 * Directory with the docking results. If the directory is not available 
 * the script ends, there is nothing to show.
 */
$results_path="$receptor/OUTPUT/test_x.outputdir";
if (!chdir($results_path))
{
    letal("Results", "Results are not available.");
    echo "</body></html>";
    exit;
}

if (!file_exists($unpacked)) 
{   
    exec("/bin/tar -zxf ./gramm-come.tar.gz");
    exec("touch $results_path/unpacked.txt");  
}

/**
 * file_exists -- Checks whether a file or directory exists
 * the results of "file_exists" are cached
 * clearstatcache -- Clears file status cache
 */
clearstatcache();
   /* This is the correct way to loop over the directory. */


if ($handle = opendir(".")) 
{
   echo "<center><table border=\"1\">";
   echo "<tr><td align=\"center\" colspan=\"6\"> <b>Docking Results</b> </td></tr>";
   while (false !== ($file = readdir($handle))) 
   {
    	$show="$http_path/$file";
    	if ( $file !== "." && $file !== ".." && $file !== "unpacked.txt" && $file !== "gramm-come.tar.gz")
	{
	    if ( $file == 'receptor-ligand.res' )
	    {
		    echo "<tr>";
		    echo "<td>Listing of the 1000 best scoring docks</td>"; 
		    echo "<td colspan=\"5\"><a href=\"$show\">$file</a></td>";
		    echo "</tr>";	
	    }
	
	    if ( $file == 'gramm.log' )
	    {
		    echo "<tr>";
		    echo "<td>Log output produced by GRAMM</td>"; 
		    echo "<td colspan=\"5\"><a href=\"$show\">$file</a></td>";
		    echo "</tr>";	
	    }
	    
	    if ( $file == 'receptor.pdb' )
	    {
		show_structure('Structure of your receptor molecule', $file, $show);
    	    }
	    
	    if ( $file == 'ligand.pdb' )
	    {
		show_structure('Structure of your ligand molecule', $file, $show);
	    }
	    
  	    // Regular expression to show the receptor-ligand files
    	    if ( ereg("^receptor-ligand+_[0-9]+.pdb$", $file))
	    {
		show_structure('Structure of your receptor-ligand complex', $file, $show);
	    }
	
	}
	clearstatcache();
   }
   echo "</table></center>";
   closedir($handle);
} else
{
    letal("Results", "Results are not available.");
}


function show_structure($message, $file, $show)
{
	$pdb2vrml1="/cgi-src/pdb_servlets/pdb2vrml1.php";
	$pdb2vrml2="/cgi-src/pdb_servlets/pdb2vrml2.php";
	$pdb2ps="/cgi-src/pdb_servlets/pdb2ps.php";
	$pdb2png="/cgi-src/pdb_servlets/pdb2png.php";
	//$server="http://www.es.embnet.org/";
	//$path=dirname($_SERVER['SCRIPT_NAME']);

	echo "<tr>";
	echo "<td>$message</td>"; 
	echo "<td><a href=$show>". basename($file, '.pdb') ."</a></td>";
	echo "<td><a href=$pdb2vrml1?url=$show>VRML1</a></td>";
	echo "<td><a href=$pdb2vrml2?url=$show>VRML2</a></td>";
	echo "<td><a href=$pdb2ps?url=$show>PS</a></td>";
	echo "<td><a href=$pdb2png?url=$show>PNG</a></td>";
	echo "</tr>";	
}

?>
</body>
</html>
