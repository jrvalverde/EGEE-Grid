<?
/**
 * Variables used to connect with the user interface host
 * 
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
 * @license 	../c/lgpl.txt
 * @version 	$Id$
 * @see     	grid_config.php
 * @link	http://savannah.cern.ch/projects/GridGRAMM
 * @since   	File available since release 0.0
 */

/**
 * Location of PHP CLI (Command Line Interface) in the system
 *
 * We need to know because grock is an interpreted PHP program and will
 * be run as an argument to the system installed PHP CLI. It also means
 * that you need PHP CLI to run GROCK.
 * 
 * @global string $php_cli
 */
$php_cli='/usr/bin/php';

/**
 * email address of the service maintainer
 *
 * Used to point users to some help desk reference if anything goes wrong
 * i.e. YOU
 *
 * @global string $maintainer
 */
$maintainer='netadmin@sci.cnb.uam.es';	    // $_SERVER['SERVER_ADMIN'];

/**
 * The name of the web server used to access GROCK
 *
 * The grock web interface needs to build URLs pointing to the results
 * of running the program. As the results will be stored in dynamically
 * generated session directories, we can't use relative paths, hence
 * the need to include the name of the www server on the URL.
 *
 * @global string $www_server
 */
$www_server='sci.cnb.uam.es';	    	    // $_SERVER['SERVER_NAME'];

// L O C A L    P A T H S

/**
 * Path to server Document Root
 *
 * @global string $serverpath
 */
$document_root='/data/www/EMBnet';     	// $_SERVER['DOCUMENT_ROOT'];

/**
 * Path to a temporary working directory
 *
 *  This directory needs to be inside the server path, so we
 *  just specify here its *relative path to DocumentRoot*. We
 *  can always reconstruct the full absolute path by concatenating
 *  with $document_root
 *
 * This must be a directory within the Web server accessible area.
 * Typically a directory below the 'htdocs' or $DocumentRoot
 *
 * We'll use this path to store user data files below into separate
 * directories with random names (to make it more difficult to guess)
 *
 * This directory must be non-browseable (e.g. with a suitable index.html).
 *
 * global string $www_tmp
 */
$www_tmp='/tst/grock/';

$tmp_path="$document_root/$www_tmp";

/**
 * Program used to send e-mail
 *
 * global string $mailprog
 */
$mailprog='/usr/lib/sendmail';

/**
 * Program used to send wap messages
 *
 * global string $wapprog
 */
$wapprog='/usr/sbin/Mail';

// A P P L I C A T I O N    S P E C I F I C    P A T H S

/**
 * Title to use for displaying in all the page headers
 *
 * @global string $pkg_name
 */
$pkg_name='GROCK';

/**
 * Path to where this program has been installed
 *
 * We will use this path to avoid duplicating common files. In order
 * to do so, we need to know the path relative to Document Root, since
 * that is how it will be accessed over the Web. Should we need a full
 * path we can always rebuild it by concatenating this with $document_root
 *
 * @global string $www_app_dir	
 */

$www_app_dir = '/cgi-src/Grid/GROCK';
// $www_tmp=dirname($_SERVER['PHP_SELF']);

$app_dir="$document_root/cgi-src/Grid/GROCK";
// $app_dir = dirname($_SERVER['SCRIPT_FILENAME'];

/**
 * Path to the working directories in the local machine
 *
 * This must be a directory within the Web server accessible area.
 * Typically a directory below the 'htdocs' or $DocumentRoot
 *
 *  In principle this is the same as $tmp_path, but we want a
 * variable name that makes it evident whether we are talking
 * of a temporary path in the local system or the remote back
 * end.
 *
 * @global string $local_tmp_path
 */
$local_tmp_path=$tmp_path;

/**
 * Path to the working directories in the remote machine
 *
 * GROCK works against a remote back-end, hence we need to make
 * a distinction between local and remote working directories.
 *
 * global string $gridUI_tmp_path
 */
$gridUI_tmp_path='/tmp/grock/data/test';

/**
 * Directory where the database listings are located 
 *
 *  This directory contains listings of entries from the
 * actual databases: the databases themselves may be much
 * more comprehensive, but we are interested in searching
 * only against the subset of entries specified in the 
 * listing files, which are located here.
 *
 *  This allows us to have a single master, fully complete
 * database and various listings of different subsets that
 * take less space (e.g. pdb40, pdb50, pdb90...).
 *
 * @global string $db_dir
 */
$db_dir="$app_dir/databases/";

/**
 * Path to actual PDB database files
 *
 * @global string $pdb_path
 */
$pdb_path='/data/gen/pdb';

/**
 * Path to actual PDBchem database files
 *
 * @global string $pdbchem_path
 */
$msdchem_path='/data/gen/msdchem';

/**
 * Path to actual HIC-Up database files
 *
 * @global string $hicup_path
 */
$hicup_path='/data/gen/hic-up';

/**
 * Path to actual ZINC database files
 *
 * @global string $zinc_path
 */
$zinc_path='/data/gen/zinc';

/**
 * Path to MMTSB tools used by PDB database manipulation routines
 *
 *  @global string $mmtsb_tools
 */
$mmtsb_tools='/opt/structure/mmtsb_tools/perl/';

// URLs (absolute or relative) to Servlets that may generate
// various output images from structure data

/**
 *  URL (absolute or relative) to a servlet that may generate a
 * VRML1 file from a PDB structure
 *
 *  global string $pdb2vrml1
 */
$pdb2vrml1="http://anarchy.cnb.uam.es/cgi-src/pdb_servlets/pdb2vrml1.php";
// TO CHANGE???
//$pdb2vrml1="http://sci.cnb.uam.es/cgi-src/pdb_servlets/pdb2vrml1.php";
/**
 *  URL (absolute or relative) to a servlet that may generate a
 * VRML2 file from a PDB structure
 *
 *  global string $pdb2vrml2
 */
$pdb2vrml2="http://anarchy.cnb.uam.es/cgi-src/pdb_servlets/pdb2vrml2.php";
// TO CHANGE???
//$pdb2vrml2="http://sci.cnb.uam.es/cgi-src/pdb_servlets/pdb2vrml2.php";

/**
 *  URL (absolute or relative) to a servlet that may generate a
 * PostScript image file from a PDB structure
 *
 *  global string $pdb2ps
 */
$pdb2ps="http://anarchy.cnb.uam.es/cgi-src/pdb_servlets/pdb2ps.php";
// TO CHANGE???
// $pdb2ps="http://sci.cnb.uam.es/cgi-src/pdb_servlets/pdb2ps.php";

/**
 *  URL (absolute or relative) to a servlet that may generate a
 * PNG image file from a PDB structure
 *
 *  global string $pdb2ps
 */
$pdb2png="http://anarchy.cnb.uam.es/cgi-src/pdb_servlets/pdb2png.php";
// TO CHANGE???
//$pdb2png='http://sci.cnb.uam.es/cgi-src/pdb_servlets/pdb2png.php';

// C O N N E C T I N G    T O    T H E    G R I D

require_once('grid_config.php');

?>
