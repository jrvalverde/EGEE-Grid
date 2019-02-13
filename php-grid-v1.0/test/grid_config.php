<?
/**
 * Variables used to connect with the user interface host
 *
 *  Note that you may set them directly using class methods, so there
 * should not be any need for this file (unless you want to have a
 * global, external site to state defaults). Actually the class GRID
 * uses this file to set up its defaults.
 *
 * @category Net
 * @package Grid
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
 */

/**
 * which version of PHP are we running
 *
 * @global integer $php_version
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
$maintainer="someone@example.org";

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
$grid_wd_path="services/egee";

/**
 * where the error log of the grid connection should be stored locally
 *
 * @global string $grid_error_log
 */
$grid_error_log="./error.txt";


/**
 * user name to use to connect to the Grid UI host
 * @global string $grid_user
 */
$grid_user="user";

/**
 * name of the Grid UI host
 * @global string $grid_host
 */
$grid_host="gridui.example.com";

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
