<?
echo "<HTML><HEAD><TITLE>YaMI results</TITLE></HEAD><BODY>";

$job=basename( dirname(__FILE__) );
$status="Runnig";

$file=exec("ls *.top");
$date=date( "j F Y H:i", filemtime("$file") );

if ( file_exists("./done") )
{ 
	$current_dir = ".";
  	$dir = dir($current_dir);

	echo "<center><h1>YaMI. Modeller results:<br></h1><center><hr>";
	echo "<center><table border=2>";

  	while ($file = $dir->read())
  	{
		if ($file !="." && $file!=".." && $file!="index.php" && $file!="done")
		{
			if ( ereg(".pir$", $file) )
			{
				echo "<tr>";
				echo "<td><a href=./$file>$file</a></td>";
				echo "<td>PIR sequence file</td>"; 
				echo "</tr>";	
			}
		}
	}
	$dir->rewind();
  	while ($file = $dir->read())
  	{
		if ($file !="." && $file!=".." && $file!="index.php" && $file!="done")
		{
			if ( ereg(".ali$", $file) )
			{
				echo "<tr>";
				echo "<td><a href=./$file>$file</a></td>";
				echo "<td>alignment or sequences in the PIR format</td>"; 
				echo "</tr>";	
			}
		}
	}
	$dir->rewind();
  	while ($file = $dir->read())
  	{
		if ($file !="." && $file!=".." && $file!="index.php" && $file!="done")
		{
			if ( ereg(".pap$", $file) )
			{
				echo "<tr>";
				echo "<td><a href=./$file>$file</a></td>";
				echo "<td>alignment or sequences in the PAP format</td>"; 
				echo "</tr>";	
			}
		}
	}
	$dir->rewind();
  	while ($file = $dir->read())
  	{
		if ($file !="." && $file!=".." && $file!="index.php" && $file!="done")
		{
			if ( ereg(".aln$", $file) )
			{
				echo "<tr>";
				echo "<td><a href=./$file>$file</a></td>";
				echo "<td>alignment or sequences in the QUANTA or INSIGHT-II format</td>"; 
				echo "</tr>";	
			}
		}
	}
	$dir->rewind();
  	while ($file = $dir->read())
  	{
		if ($file !="." && $file!=".." && $file!="index.php" && $file!="done")
		{
			if ( ereg(".seq$", $file) || ereg(".chn$", $file) )
			{
				echo "<tr>";
				echo "<td><a href=./$file>$file</a></td>";
				echo "<td>sequence(s) in the PIR alignment format</td>"; 
				echo "</tr>";	
			}
		}
	}
	$dir->rewind();
  	while ($file = $dir->read())
  	{
		if ($file !="." && $file!=".." && $file!="index.php" && $file!="done")
		{
			if ( ereg(".cod$", $file) )
			{
				echo "<tr>";
				echo "<td><a href=./$file>$file</a></td>";
				echo "<td>list of sequence codes</td>"; 
				echo "</tr>";	
			}
		}
	}
	$dir->rewind();
  	while ($file = $dir->read())
  	{
		if ($file !="." && $file!=".." && $file!="index.php" && $file!="done")
		{
			if ( ereg(".top$", $file) )
			{
				echo "<tr>";
				echo "<td><a href=./$file>$file</a></td>";
				echo "<td>TOP script with instructions for a MODELLER job</td>"; 
				echo "</tr>";	
			}
		}
	}
	$dir->rewind();
  	while ($file = $dir->read())
  	{
		if ($file !="." && $file!=".." && $file!="index.php" && $file!="done")
		{
			if ( ereg(".log$", $file) )
			{
				echo "<tr>";
				echo "<td><a href=./$file>$file</a></td>";
				echo "<td>log output produced by a MODELLER run</td>"; 
				echo "</tr>";	
			}
		}
	}
	$dir->rewind();
  	while ($file = $dir->read())
  	{
		if ($file !="." && $file!=".." && $file!="index.php" && $file!="done")
		{
			if ( ereg(".ini$", $file) )
			{
				echo "<tr>";
				echo "<td><a href=./$file>$file</a></td>";
				echo "<td>initial MODELLER model</td>"; 
				echo "</tr>";	
			}
		}
	}
	$dir->rewind();
  	while ($file = $dir->read())
  	{
		if ($file !="." && $file!=".." && $file!="index.php" && $file!="done")
		{
			if ( ereg(".grp$", $file) )
			{
				echo "<tr>";
				echo "<td><a href=./$file>$file</a></td>";
				echo "<td>list of families in PDB</td>"; 
				echo "</tr>";	
			}
		}
	}
	$dir->rewind();
  	while ($file = $dir->read())
  	{
		if ($file !="." && $file!=".." && $file!="index.php" && $file!="done")
		{
			if ( ereg(".atm$", $file) || ereg(".pdb$", $file) || ereg(".ent$", $file))
			{
				echo "<tr>";
				echo "<td><a href=./$file>$file</a></td>";
				echo "<td>atom coordinates in PDB or GRASP format</td>"; 
				echo "</tr>";	
			}
		}
	}
	$dir->rewind();
  	while ($file = $dir->read())
  	{
		if ($file !="." && $file!=".." && $file!="index.php" && $file!="done")
		{
			if ( ereg(".crd$", $file) )
			{
				echo "<tr>";
				echo "<td><a href=./$file>$file</a></td>";
				echo "<td>atom coordinates in the CHARMM format</td>"; 
				echo "</tr>";	
			}
		}
	}
	$dir->rewind();
  	while ($file = $dir->read())
  	{
		if ($file !="." && $file!=".." && $file!="index.php" && $file!="done")
		{
			if ( ereg("_fit.crd$", $file) )
			{
				echo "<tr>";
				echo "<td><a href=./$file>$file</a></td>";
				echo "<td>fitted protein structures in the PDB format</td>"; 
				echo "</tr>";	
			}
		}
	}
	$dir->rewind();
  	while ($file = $dir->read())
  	{
		if ($file !="." && $file!=".." && $file!="index.php" && $file!="done")
		{
			if ( ereg(".B[0-9]+$", $file) )//////////////////////////
			{
				echo "<tr>";
				echo "<td><a href=./$file>$file</a></td>";
				echo "<td>MODELLER model in the PDB format</td>"; 
				echo "</tr>";	
			}
		}
	}
	$dir->rewind();
  	while ($file = $dir->read())
  	{
		if ($file !="." && $file!=".." && $file!="index.php" && $file!="done")
		{
			if ( ereg(".D[0-9]+$", $file) )//////////////////////////
			{
				echo "<tr>";
				echo "<td><a href=./$file>$file</a></td>";
				echo "<td>the progress of optimization</td>"; 
				echo "</tr>";	
			}
		}
	}
	$dir->rewind();
  	while ($file = $dir->read())
  	{
		if ($file !="." && $file!=".." && $file!="index.php" && $file!="done")
		{
			if ( ereg(".IL[0-9]+$", $file) )//////////////////////////
			{
				echo "<tr>";
				echo "<td><a href=./$file>$file</a></td>";
				echo "<td>initial MODELLER model, in loop modeling</td>"; 
				echo "</tr>";	
			}
		}
	}
	$dir->rewind();
  	while ($file = $dir->read())
  	{
		if ($file !="." && $file!=".." && $file!="index.php" && $file!="done")
		{
			if ( ereg(".BL[0-9]+$", $file) )//////////////////////////
			{
				echo "<tr>";
				echo "<td><a href=./$file>$file</a></td>";
				echo "<td>MODELLER model in the PDB format, in loop modeling</td>"; 
				echo "</tr>";	
			}
		}
	}
	$dir->rewind();
  	while ($file = $dir->read())
  	{
		if ($file !="." && $file!=".." && $file!="index.php" && $file!="done")
		{
			if ( ereg(".DL[0-9]+$", $file) )//////////////////////////
			{
				echo "<tr>";
				echo "<td><a href=./$file>$file</a></td>";
				echo "<td>the progress of optimization, in loop modeling</td>"; 
				echo "</tr>";	
			}
		}
	}
	$dir->rewind();
  	while ($file = $dir->read())
  	{
		if ($file !="." && $file!=".." && $file!="index.php" && $file!="done")
		{
			if ( ereg(".V[0-9]+$", $file) )
			{
				echo "<tr>";
				echo "<td><a href=./$file>$file</a></td>";
				echo "<td>violations profile</td>"; 
				echo "</tr>";	
			}
		}
	}
	$dir->rewind();
  	while ($file = $dir->read())
  	{
		if ($file !="." && $file!=".." && $file!="index.php" && $file!="done")
		{
			if ( ereg(".E[0-9]+$", $file) )
			{
				echo "<tr>";
				echo "<td><a href=./$file>$file</a></td>";
				echo "<td>energy profile</td>"; 
				echo "</tr>";	
			}
		}
	}
	$dir->rewind();
  	while ($file = $dir->read())
  	{
		if ($file !="." && $file!=".." && $file!="index.php" && $file!="done")
		{
			if ( ereg(".rsr$", $file) )
			{
				echo "<tr>";
				echo "<td><a href=./$file>$file</a></td>";
				echo "<td>restraints in MODELLER or USER format</td>"; 
				echo "</tr>";	
			}
		}
	}
	$dir->rewind();
  	while ($file = $dir->read())
  	{
		if ($file !="." && $file!=".." && $file!="index.php" && $file!="done")
		{
			if ( ereg(".sch$", $file) )
			{
				echo "<tr>";
				echo "<td><a href=./$file>$file</a></td>";
				echo "<td>schedule file for the variable target function optimization</td>"; 
				echo "</tr>";	
			}
		}
	}
	$dir->rewind();
  	while ($file = $dir->read())
  	{
		if ($file !="." && $file!=".." && $file!="index.php" && $file!="done")
		{
			if ( ereg(".mat$", $file) )
			{
				echo "<tr>";
				echo "<td><a href=./$file>$file</a></td>";
				echo "<td>matrix of pairwise protein distances from an alignment or residue type-residue type distance scores</td>"; 
				echo "</tr>";	
			}
		}
	}
	$dir->rewind();
  	while ($file = $dir->read())
  	{
		if ($file !="." && $file!=".." && $file!="index.php" && $file!="done")
		{
			if ( ereg(".sim.mat$", $file) )
			{
				echo "<tr>";
				echo "<td><a href=./$file>$file</a></td>";
				echo "<td>matrix of pairwise residue type-residue type similarity scores</td>"; 
				echo "</tr>";	
			}
		}
	}
	$dir->rewind();
  	while ($file = $dir->read())
  	{
		if ($file !="." && $file!=".." && $file!="index.php" && $file!="done")
		{
			if ( ereg(".lib$", $file) )
			{
				echo "<tr>";
				echo "<td><a href=./$file>$file</a></td>";
				echo "<td>various MODELLER libraries</td>"; 
				echo "</tr>";	
			}
		}
	}
	$dir->rewind();
  	while ($file = $dir->read())
  	{
		if ($file !="." && $file!=".." && $file!="index.php" && $file!="done")
		{
			if ( ereg(".psa$", $file) )
			{
				echo "<tr>";
				echo "<td><a href=./$file>$file</a></td>";
				echo "<td>residue solvent accessibilities</td>"; 
				echo "</tr>";	
			}
		}
	}
	$dir->rewind();
  	while ($file = $dir->read())
  	{
		if ($file !="." && $file!=".." && $file!="index.php" && $file!="done")
		{
			if ( ereg(".sol$", $file) )
			{
				echo "<tr>";
				echo "<td><a href=./$file>$file</a></td>";
				echo "<td>atomic solvent accessibilities</td>"; 
				echo "</tr>";	
			}
		}
	}
	$dir->rewind();
  	while ($file = $dir->read())
  	{
		if ($file !="." && $file!=".." && $file!="index.php" && $file!="done")
		{
			if ( ereg(".ngh$", $file) )
			{
				echo "<tr>";
				echo "<td><a href=./$file>$file</a></td>";
				echo "<td>residue neighbors</td>"; 
				echo "</tr>";	
			}
		}
	}
	$dir->rewind();
  	while ($file = $dir->read())
  	{
		if ($file !="." && $file!=".." && $file!="index.php" && $file!="done")
		{
			if ( ereg(".dih$", $file) )
			{
				echo "<tr>";
				echo "<td><a href=./$file>$file</a></td>";
				echo "<td>mainchain and sidechain dihedral angles</td>"; 
				echo "</tr>";	
			}
		}
	}
	$dir->rewind();
  	while ($file = $dir->read())
  	{
		if ($file !="." && $file!=".." && $file!="index.php" && $file!="done")
		{
			if ( ereg(".ssm$", $file) )
			{
				echo "<tr>";
				echo "<td><a href=./$file>$file</a></td>";
				echo "<td>secondary structure assignment</td>"; 
				echo "</tr>";	
			}
		}
	}
	$dir->rewind();
  	while ($file = $dir->read())
  	{
		if ($file !="." && $file!=".." && $file!="index.php" && $file!="done")
		{
			if ( ereg(".var$", $file) )
			{
				echo "<tr>";
				echo "<td><a href=./$file>$file</a></td>";
				echo "<td>sequence variability profile from multiple alignment</td>"; 
				echo "</tr>";	
			}
		}
	}
	$dir->rewind();
  	while ($file = $dir->read())
  	{
		if ($file !="." && $file!=".." && $file!="index.php" && $file!="done")
		{
			if ( ereg(".asgl$", $file) )
			{
				echo "<tr>";
				echo "<td><a href=./$file>$file</a></td>";
				echo "<td>data for plotting by ASGL</td>"; 
				echo "</tr>";	
			}
		}
  	}
	echo "</table></center>";
  	echo "<hr><br>";
  	$dir->close();

}else
{
	echo "<CENTER><p><H2>YaMI. Modeller results.</H2><p><br><br>";
	echo "<H3>Bookmark this page to access it later</H3>";
	echo "<TABLE border=2>";
	echo "<tr>";
	echo "<td VALIGN=\"top\" ALIGN=\"center\" COLSPAN=\"6\"><b>Your Modeller Job is runnig.</b></td>";
	echo "</tr>";
	echo "<tr>";

	echo "<td BGCOLOR=\"#cccccc\" VALIGN=\"top\" ALIGN=\"center\"><b>Modeller Job number: </b></td>";
	echo "<td  VALIGN=\"top\" ALIGN=\"center\"><b> $job </b></td>";
	
	echo "<td BGCOLOR=\"#cccccc\" VALIGN=\"top\" ALIGN=\"center\"><b>Status: </b></td>";
	echo "<td  VALIGN=\"top\" ALIGN=\"center\"><b> $status </b></td>";

	echo "<td BGCOLOR=\"#cccccc\" VALIGN=\"top\" ALIGN=\"center\"><b>Start Date: </b></td>";
	echo "<td  VALIGN=\"top\" ALIGN=\"center\"><b> $date </b></td>";

	echo "</tr>";	
	echo "</CENTER></TABLE>";
}

echo "</BODY></HTML>";

?>
