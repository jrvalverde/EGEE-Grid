<?php
/**
 * Explore a probe-target complex structure using Jmol.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 * 
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @package 	grock
 * @author  	David Garcia <david@cnb.uam.es>
 * @author  	Jose R. Valverde <david@cnb.uam.es>
 * @copyright 	CSIC
 * @license 	../c/gpl.txt
 * @version 	$Id$
 * @see     	config.php
 * @see     	../Jmol/index.html
 * @link	http://savannah.cern.ch/projects/GridGRAMM
 * @since   	File available since release 1.0
 */

// This is a quick and dirty to get away with.
// Ideally we would have it as a servlet taking a listing, probe and target
// and running GRAMM to do the work.
//
//  But that is an altogether whole new project of its own :-)

require_once('config.php');

$probe=$_GET['probe'];
$probe_type=$_GET['probe_type'];
$target=$_GET['target'];

$relpdb=$_GET['relpdb'];
$reldir=dirname($relpdb);
$pdbfile = $local_tmp_path.$relpdb;

chdir('../Jmol');

echo <<< ENDPAGE
<html>
  <head>
    <title>$probe - $target complex</title>
    <script src="$www_app_dir/Jmol/Jmol.js"></script>
  </head>
  <body bgcolor="#ccccff">
    <script>
      jmolInitialize("../Jmol/");
      jmolCheckBrowser("popup", "../browsercheck", "onClick");
    </script>
    <form>

      <table border="1">
	<tr>
	  <td>
	    <table>
	      <tr><th>
		<h2>$probe-$target complex</h2>
	      </th></tr>
	      <tr><td>
	        $probe
		complexed with $target
	      </td></tr><tr><td>
		<script>
		jmolApplet(400, "load $relpdb; cpk off; wireframe off;" +
		"cartoon; color cartoon structure");
		</script>
	      </td></tr>
	    </table>
	  </td>
	  <td>
	    <script>
	     jmolHtml("<b>Switch model</b> ");
	     jmolMenu([
                      ["load $reldir/receptor-ligand_1.pdb; cpk off;cartoon; color cartoon structure; wireframe off;", "Complex #1", "selected"],
                      ["load $reldir/receptor-ligand_2.pdb; cpk off;cartoon; color cartoon structure; wireframe off;", "Complex #2"],
                      ["load $reldir/receptor-ligand_3.pdb; cpk off;cartoon; color cartoon structure; wireframe off;", "Complex #3"],
                      ["load $reldir/receptor-ligand_4.pdb; cpk off;cartoon; color cartoon structure; wireframe off;", "Complex #4"],
                      ["load $reldir/receptor-ligand_5.pdb; cpk off;cartoon; color cartoon structure; wireframe off;", "Complex #5"],
                      ["load $reldir/receptor-ligand_6.pdb; cpk off;cartoon; color cartoon structure; wireframe off;", "Complex #6"],
                      ["load $reldir/receptor-ligand_7.pdb; cpk off;cartoon; color cartoon structure; wireframe off;", "Complex #7"],
                      ["load $reldir/receptor-ligand_8.pdb; cpk off;cartoon; color cartoon structure; wireframe off;", "Complex #8"],
                      ["load $reldir/receptor-ligand_9.pdb; cpk off;cartoon; color cartoon structure; wireframe off;", "Complex #9"],
                      ["load $reldir/receptor-ligand_10.pdb; cpk off;cartoon; color cartoon structure; wireframe off;", "Complex #10"],
                      ]);
	      jmolHtml("<hr /><b>Space fill</b><br />");
	      jmolRadioGroup([
	        ["spacefill off", "off ", "checked"],
		["spacefill 25%", "25% "],
		["spacefill 50%", "50% "],
		["spacefill on",  "100%"]
		]);
	    </script>
	    <script>
	      jmolHtml("<hr /><b>Wireframe</b><br />");
	      jmolCheckbox("wireframe on", "wireframe off", "wireframe on/off");
	    </script>
	    <script>
	      jmolHtml("<hr /><b>Cartoon display</b><br />");
	      jmolCheckbox("cartoon on", "cartoon off", "cartoon on/off", "checked");
	      jmolHtml("<br />");
	    </script>
	    <script>
	      jmolHtml("<hr /><b>Rocket display</b><br />");
	      jmolCheckbox("rocket on", "rocket off", "rocket on/off");
	      jmolHtml("<br />");
	    </script>
	    <script>	      
	      jmolHtml("<hr /><b>Zoom in and take a closer look</b><br />");
	      jmolButton("zoom 300");
	      jmolBr();
	      jmolButton("zoom 200");
	      jmolBr();
	      jmolButton("zoom 100");
	      jmolBr();
	    </script>
	    <script>
	      jmolHtml("<hr /><b>Take it for a spin</b><br />");
	      jmolCheckbox("spin on", "spin off", "spin");
	      jmolBr();
	    </script>
	    <script>
	      jmolHtml("<hr><b>Ligand</b><br />");
	      jmolCheckbox("select *:B; cpk on; select *",
	      		   "select *:B; cpk off; select *",
			   "CPK on/off"
	      );
	    </script>
	  </td>
	  <td>
	    <script>
	      jmolHtml("<b>Color atoms</b><br />");
	      jmolRadioGroup([
	        "color atoms none",
	        ["color atoms structure", null, "checked"],
		"color atoms amino",
		"color atoms shapely",
		"color atoms temperature",
		], "<br />");
	    </script>
	    <script>
	      jmolHtml("<hr><b>Color cartoons</b><br />");
	      jmolRadioGroup([
	        "color cartoon none",
	        ["color cartoon structure", null, "checked"],
		"color cartoon amino",
		"color cartoon shapely",
		"color cartoon temperature",
		], "<br />");
	    </script>
	    <script>
	      jmolHtml("<hr><b>Color rockets</b><br />");
	      jmolRadioGroup([
	        ["color rocket none", null, "checked"],
	        "color rocket structure",
		"color rocket amino",
		"color rocket shapely",
		"color rocket temperature",
		], "<br />");
	    </script>
	  </td>
	</tr>
      </table>
    </form>
  </body>
</html>
ENDPAGE;

?>
