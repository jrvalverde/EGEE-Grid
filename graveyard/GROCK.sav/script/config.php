<?
/**
 * Variables used to connect with the user interface host
 * 
 * @package GRAMM
 * @copyright CSIC - GPL
 * @version 1.0
 */

/**
 * email address of the service maintainer
 *
 * Used to point users to some help desk reference if anything goes wrong
 * i.e. YOU
 *
 * @global string $maintainer
 */
$maintainer="netadmin@es.embnet.org";

// L O C A L    P A T H S

/**
 * Path to server Document Root
 *
 * @global string $serverpath
 */
$document_root="/data/www/EMBnet";     	// $ENV{"DOCUMENT_ROOT"};

/**
 * Path to where this program has been installed
 *
 * We will use this path to avoid duplicating common files. In order
 * to do so, we need to know the path relative to Document Root, since
 * that is how it will be accessed over the Web. Should we need a full
 * path we can always rebuild it by concatenating this with $documentroot
 *
 * @global string $app_dir	
 */

$app_dir="/data/www/EMBnet/cgi-src/Grid/GROCK";

/**
 * Path to a temporary directory
 *
 *  This directory needs to be inside the server path, so we
 *  just specify here its relative path to DocumentRoot. We
 *  can always reconstruct the full absolute path by concatenating
 *  with $serverpath
 *
 * global string $httptmp
 */
//$httptmp="/tmp/grock";
$httptmp="/tmp/grid/test";

/**
 * Path to the working directories in the local machine starting from /
 *
 * This must be a directory within the Web server accessible area.
 * Typically a directory below the 'htdocs' or $DocumentRoot
 *
 * We'll use this path to store user data files below into separate
 * directories with random names (to make it more difficult to guess)
 *
 * This directory must be non-browseable (e.g. with a suitable index.html).
 *
 * @note At EMBnet/CNB we host many servers, each has its own DocumentRoot
 *	 hanging from /data/www/.
 *
 * @global string $wd_path
 */
$wd_path="$document_root/$httptmp";

/**
 * where the error log of the grid connection should be stored locally
 *
 * @global string $grid_error_log
 */
$grid_error_log="/data/www/EMBnet/tmp/grid/test/error.txt";

/**
 * Directory where GRAMM is installed
 *
 */
$gramm_dir="$app_dir/script/gramm";

/**
 * Directory where the JDL is 
 *
 */
$jdl_dir="$app_dir/jdl/session.jdl";

/**
 * Directory where the PDB subset is 
 *
 */
$pdb_dir="$app_dir/pdb_nr/pdb40.lst";

/**
 * Program used to send e-mail
 *
 * global string $mailprog
 */
$mailprog="/usr/lib/sendmail";

/**
 * Program used to send wap messages
 *
 * global string $wapprog
 */
$wapprog="/usr/sbin/Mail";


// C O N N E C T I N G    T O    T H E    G R I D

/**
 * user name to use to connect to the Grid UI host
 * @global string $server_user
 */
//$server_user="embnet.es";
$grid_user="david";

/**
 * name of the Grid UI host
 * @global string $server_host
 */
$grid_host="villon.cnb.uam.es";

/**
 * Who acts as the grid server for us
 * @global string $server
 */
$grid_server="$grid_user@$grid_host";

/**
 * Default password to login on the UI machine
 *
 * @global string $grid_password
 */
//$grid_password="BETTER USE GRID::SET_PASSWORD";
$grid_password="miseria2004";

/**
 * Default passphrase to unlock certificate of remote user at the UI
 */
$grid_passphrase="kndlaria";

// L O O K    A N D    F E E L

/**
 * Title to use for displaying in all the page headers
 *
 * @global string $pkg_name
 */
$pkg_name="GROCK";

// comments
$debug=TRUE;

/**
 * Path to the working directories in the local machine
 *
 * This must be a directory within the Web server accessible area.
 * Typically a directory below the 'htdocs' or $DocumentRoot
 *
 *	At EMBnet/CNB we host many servers, each has its own DocumentRoot
 *	hanging from /data/www/.
 * @global string $tmp_grid_path
 */
$local_tmp_path="/data/www/EMBnet/tmp/grid/test";

/**
 * Path to the working directories in the remote machine
 *
 * The remote machine is the UI: grid User Interface
 * 
 */
$UI_grid_path="/tmp/grock/data/test";
/**
 * Path to database PDB files
 *
 * @global string $db_path
 */
$db_path="/data/gen/pdb_select";

?>
