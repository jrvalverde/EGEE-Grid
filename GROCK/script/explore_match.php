<?
/**
 * Explore the results of a docking match between a probe and a target
 * molecules.
 *
 *  This page allows users to navigate through the listing of docking
 * conformations generated for a given probe-target pair, exploring them.
 *
 *  Since analysis of results is dependent on the method used, the work
 * is actually relayed to a docker-dependent method invoked through the
 * method-independent docker interface.
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
 * @license 	../c/gpl.txt
 * @version 	$Id$
 * @see     	config.php
 * @see     	util.php
 * @see     	dock.php
 * @link	http://savannah.cern.ch/projects/GridGRAMM
 * @since   	File available since release 1.0
 */

require_once('config.php');
require_once('util.php');
require_once('dock.php');

define('DISPLAY_SIZE', 50); 	// this is only an initial value, may (and will
    	    	    	    	// probably) be overriden by docker routines

$id=$_GET['id'];
$probe=$_GET['probe'];
$pt=$_GET['pt'];
$target=$_GET['target'];
$docker=$_GET['docker'];
if (! isset($_GET['from']))
    $from=1;
else
    $from=$_GET['from'];
if (isset($_GET['to']))
    $to=$_GET['to'];
else
    $to=DISPLAY_SIZE;

if (!is_dir("$local_tmp_path/$id/$target/job_output")) {
    error('Explore results', 'No results to explore');
    exit;
}
chdir("$local_tmp_path/$id/");

// Navigate.
dock_show_dock($docker, $id, $probe, $pt, $target, $from, $to);


?>
