<?php
/**
 * Explore a molecular structure using Jmol.
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

require_once('config.php');

/**
 * Path to PDB file relative to Document Root
 */
$relpdb=$_GET['relpdb'];
/**
 * Molecule name to display
 */
$name=$_GET['name'];

/**
 * Actual absolute pathname
 */
$pdbfile = $local_tmp_path.$relpdb;

chdir('../Jmol');

echo <<< ENDPAGE
<html>
  <head>
    <title>Structure of $name</title>
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
		<h2>$name</h2>
	      </th></tr>
	      <tr><td>
	        Navigate the structure of $name
	      </td></tr>
	      <tr><td>
		<script>
		jmolApplet(400, "load $relpdb; cpk on; wireframe off;" +
		"cartoon off; color cartoon structure");
		</script>
	      </td></tr>
	    </table>
	  </td>
	  <td>
	    <script>
	      jmolHtml("<b>Space fill</b><br />");
	      jmolRadioGroup([
	        ["spacefill off", "off "],
		["spacefill 25%", "25% "],
		["spacefill 50%", "50% "],
		["spacefill on",  "100%", "checked"]
		]);
	    </script>
	    <script>
	      jmolHtml("<hr /><b>Wireframe</b><br />");
	      jmolCheckbox("wireframe on", "wireframe off", "wireframe on/off");
	    </script>
	    <script>
	      jmolHtml("<hr /><b>Cartoon display</b><br />");
	      jmolCheckbox("cartoon on", "cartoon off", "cartoon on/off");
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
