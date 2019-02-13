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
// To test the class from the command line we need a shell with no control
// terminal to avoid SSH from prompting interactively for the password:
// run this with ' ssh -x -T localhost "(cd `pwd` ; php ssh_test.php)" '

// To test on the web, just invoke this script.

$debug = TRUE;

require 'ssh.php';

#$remote = "jruser@example.com";
#$password = "PASSWORD";

$rmt = new SExec;

echo "<pre>\n";

$debug_passthru = TRUE;
if (isset($debug_passthru)) {
    echo "Using passthru to send ls...\n\n";
    $rmt->ssh_passthru($remote, $password, "ls", $status);
    echo "\nExit status was $status";
}

//$debug_exec = TRUE;
if (isset($debug_exec)) {
    $rmt->ssh_exec($remote, $password, "ls", $out);
    print_r($out);
    foreach ($out as $line)
    	echo $line . "\n";
}

//$debug_copy = TRUE;
if (isset($debug_copy))
    echo "scp -pqrC SSH $remote:k/\n";
    $rmt->ssh_copy("SSH", "$remote:k/", $password);

//$debug_persistent_cmd = TRUE;
if (isset($debug_persistent_cmd))
{
    $p = $rmt->ssh_open_command($remote, $password, "ls");
    
    echo "after open I got this\n";
    print_r($p);

    sleep(1);
    echo "flushing files\nstdout\n";
    flush();
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

//$debug_persistent_shell = TRUE;
if (isset($debug_persistent_shell))
{
    $p = $rmt->ssh_open_shell($remote, $password);
    
    echo "after open I got this\n";
    print_r($p);

    sleep(1);
    echo "[1] flushing stdout after login\n";
    do {
	$line = fgets($p["std_out"], 1024);
	echo "> ".$line;
    } while ((! feof($p["std_out"]) ) && (! ereg("cnb\.uam\.es", $line)));
    $last = ftell($p["std_out"]);

    echo "\n[2]sending hello\n";
    fwrite($p['std_in'], "k/hello\n"); fflush($p['std_in']);
    sleep(1); flush();
    fseek($p["std_out"], $last); 
    echo "< ";
    print_r(fgets($p["std_out"], 1024));
    echo "< ";
    print_r(fgets($p["std_out"], 1024));
    echo "\n";
    $last = ftell($p["std_out"]);
    fwrite($p['std_in'], "yes I do\n"); fflush($p['std_in']);
    sleep(2);

    echo "flushing files\n\tstdout\n";
    flush();
    fseek($p["std_out"], $last);
    do {
	$line = fgets($p["std_out"], 1024);
	echo ">> ".$line;
    } while (! feof($p["std_out"]) );
    #    echo "stderr\n";
    #    do {
    #	$line = fgets($p["std_err"], 1024);
    #	echo ">>> ".$line."\n";
    #    } while (! feof($p['std_err']) && (strlen($line) != 0));;

    $rmt->ssh_close($p);
}
if (isset($debug_popen)) {
    $cout = $rmt->ssh_popen($remote, $password, "ls -C", "r");
    echo fread($cout, 8192);
    $rmt->ssh_pclose($cout);
    echo "\n";
    $cin = $rmt->ssh_popen($remote, $password, "/usr/sbin/Mail -s test jr", "w");
       fputs($cin, "\nTest");
       fputs($cin, ".\n");
    $rmt->ssh_pclose($cin);
}

echo "</pre>";
exit;

?>
