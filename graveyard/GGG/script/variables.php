<?
/**
 * Variables used to connect with the user interface host
 * 
 * @package GridGRAMM
 * @author David Garcia Aristegui <david@cnb.uam.es>
 * @copyright CSIC - GPL
 * @version 1.0
 */

/**
 * user name to use to connect to the Grid UI host
 * @global string $server_user
 */
$server_user="david";

/**
 * name of the Grid UI host
 * @global string $server_host
 */
$server_host="villon";

/**
 * Who acts as the grid server for us
 * @global string $server
 */
$server="$server_user@$server_host";

/**
 * Grid passphrase
 * @global string $grid_pass
 */
$grid_pass="kndlaria";

/**
 * Path to the working directories in the local machine
 *
 * This must be a directory within the Web server accessible area.
 * Typically a directory below the 'htdocs' or $DocumentRoot
 *
 *	At EMBnet/CNB we host many servers, each has its own DocumentRoot
 *	hanging from /data/www/.
 * @global string $grid_path
 */
$grid_path="/data/www/EMBnet/tmp/grid";

// User Interface
// Remember that the User Interface has mounted /data/... directory

?>
