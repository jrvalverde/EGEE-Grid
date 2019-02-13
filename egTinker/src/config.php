<?
/**
 * Variables used to connect with the user interface host
 * 
 * @package TINKER
 * @author Jose R. Valverde <jrvalverde@acm.org>
 * @copyright CSIC - GPL
 * @version 1.0
 */

$php_version=4;

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
$app_dir="/Services/MolBio/egTINKER/";

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
$httptmp="/tmp/egTINKER";

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

// XXX - JR - XXX -- REMOVE THIS DEPENDENCY
//  	I.E. COPY DATA TO USER HOME USING SCP 
//  	This was not done formerly for we failed to call SSH directly.
//  	We are close to getting it done...
//
// User Interface
// Remember that the User Interface has mounted /data/... directory
//  	We are assuming that what we store locally will be placed
//  	in the same place remotely automagically by way of NFS mount.

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
$grid_wd_path="services/egTINKER";

/**
 * where the error log of the grid connection should be stored locally
 *
 * @global string $grid_error_log
 */
$grid_error_log="./error.txt";

/**
 * Path to BABEL in the local machine
 *
 *  We'll use BABEL to convert input files in any acceptable format to
 *  PDB format
 *
 * @global string $babel
 */
$babel="/opt/structure/bin/babel";

/**
 * BABEL_DIR directory holding babel files
 *
 * BABEL requires some additional files to run. These must be placed in
 * a directory pointed to by environment variable BABEL_DIR
 *
 * @global string babel_dir
 */
$babel_dir="/opt/structure/babel";

/**
 * Directory where tinker is installed
 *
 * global string $tinker_dir
 */
$tinker_dir="/opt/structure/tinker";

/**
 * Application binaries are stored here
 *
 * global string $tinker_bin
 */
$tinker_bin="$tinker_dir/bin";

/**
 * Tinker params files are stored here
 *
 * global string $tinker_params
 */
$tinker_params="$tinker_dir/params";

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
$grid_user="jr";

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
$grid_password="BETTER USE GRID::SET_PASSWORD";

/**
 * Default passphrase to unlock certificate of remote user at the UI
 */
$grid_passphrase="USE GRID::SET_PASSPHRASE";

// L O O K    A N D    F E E L

/**
 * Title to use for displaying in all the page headers
 *
 * @global string $pkg_name
 */
$pkg_name="egTINKER";

$debug=FALSE;
?>
