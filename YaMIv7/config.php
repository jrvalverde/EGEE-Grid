<?
/**
 * Variables and constants used in YaMI
 *
 * @author Jose R. Valverde <jrvalverde@acm.org>
 * @version 1.0
 * @copyright José R. Valverde <jrvalverde@acm.org>
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

$debug = TRUE;

/**
 * which version of PHP are we running
 *
 * @global integer $php_version
 */
$php_version=4;

//-------------------
//   YaMI DEFAULTS
//-------------------

// Maximum number of models we allow users to compute
define('MAXMODELS', 6);

// Temporary directory within WWW server hierarchy as seen by the system
define('SYS_TMP_ROOT', '/data/www/EMBnet/tmp');

// Temporary directory within WWW server hierarchy as seen by the WWW server
define('WWW_TMP_ROOT', '/tmp');

// Location of raw PDB database
define('PDB_DIR', '/data/gen/pdb/');

// Locations of coordinate data files
//  (cwd and PDB_DIR)
define('ATOM_FILES_PATH', './:'.PDB_DIR);

//	The user refers to PDB entries by their code, but they
//	are actually stored as independent files. To build the
//	filename corresponding to an entry we need to know if
//	the filename has anything before the entry code and anything
//	afterwards, e.g.
//		if entry 1ENG is in file "pdb1eng.ent", then it is
//		preceded by prefix "pdb" and folowed by ".ent"
define('PDB_PREFIX', 'pdb');
define('PDB_SUFFIX', '.ent');

// Alignment format
define('ALIGNMENT_FORMAT', 'PIR');

// Result update periodicity (in seconds)
define('UPD_TIME', 15);
define('NO_RELOAD', 0);

//-------------------
//   GRID DEFAULTS
//-------------------

/**
 * Remote work directory in the Grid UI server
 *
 *  We will use this to separate different services into alternate
 *  hierarchies in the grid server, as well as to avoid cluttering
 *  the remote user's dir with too many directories.
 *
 *  This should be a path relative to the user home OR an actual
 *  correct full path. For the sake of versatility it is better to
 *  use a relative path.
 *
 *  @global string $grid_wd_path
 */
$grid_wd_path = 'services/egee';

/**
 * where the error log of the grid connection should be stored locally
 *
 * @global string $grid_error_log
 */
$grid_error_log = './grid_error.txt';


/**
 * user name to use to connect to the Grid UI host
 * @global string $grid_user
 */
$grid_user = 'USE GRID::SET_USER';

/**
 * name of the Grid UI host
 * @global string $grid_host
 */
$grid_host = 'gridui.example.com';

/**
 * Who acts as the grid server for us
 * @global string $grid_server
 */
$grid_server="$grid_user@$grid_host";

/**
 * Default password to login on the UI machine
 *
 * @global string $grid_password
 */
$grid_password="USE GRID::SET_PASSWORD";

/**
 * Default passphrase to unlock certificate of remote user at the UI
 */
$grid_passphrase="USE GRID::SET_PASSPHRASE";

?>
