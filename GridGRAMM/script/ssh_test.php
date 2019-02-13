<?php
/*
 * @package SSH
 * @author José R. Valverde <jrvalverde@acm.org>
 * @version 1.0
 * @copyright José R. Valverde <jrvalverde@es.embnet.org>
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
// run with ssh -x -T localhost "(cd `pwd` ; php ssh_test.php)"

$debug = TRUE;

require 'ssh.php';

$remote = "user@example.com";
$password = "password";

$rmt = new SExec;

if (isset($debug_passthru))
    $rmt->ssh_passthru($remote, $password, "ls");

if (isset($debug_exec)) {
    $rmt->ssh_exec($remote, $password, "ls", $out);
    print_r($out);
    foreach ($out as $line)
    	echo $line . "\n";
}

if (isset($debug_copy))
    $rmt->ssh_copy("/data/www/EMBnet/cgi-src/Grid/egTinker/src/SSH", $remote, $password);

if (isset($debug_persistent))
{
    if (isset($debug_shell))
	$p = $rmt->ssh_open_shell($remote, $password);
    else {
	$p = $rmt->ssh_open_command($remote, $password, "ls");
    }
    echo "after open I got this\n";
    print_r($p);

    #echo "sending touch beenhere\n";
    #fwrite($p['std_in'], "touch beenhere\n");

	echo "flushing pipes\nstdout\n";
	rewind($p["std_out"]);
	do {
	    $line = fgets($p["std_out"], 1024);
	    echo "> ".$line."\n";
	} while (! feof($p["std_out"]) );;
    #    echo "stderr\n";
    #    do {
    #	$line = fgets($p["std_err"], 1024);
    #	echo ">> ".$line."\n";
    #    } while (! feof($p['std_err']) && (strlen($line) != 0));;


    $rmt->ssh_close($p);
}

$cout = $rmt->ssh_popen($remote, $password, "ls -C", "r");
echo fread($cout, 8192);
$rmt->ssh_pclose($cout);
echo "\n";
$cin = $rmt->ssh_popen($remote, $password, "/usr/sbin/Mail -s test jr", "w");
   fputs($cin, "\nTest");
   fputs($cin, ".\n");
$rmt->ssh_pclose($cin);

exit;

?>
