<?php
require_once './grid_config.php';
require_once './ssh.php';
require_once './grid.php';

$ru = "user";
$rh = "gridUI.example.net";
$password = "gridUI password";
$psp = "grid passphrase";

echo "<pre>\n";

//$debug_sexec = TRUE;
//$debug_grid = TRUE;

$eg = new Grid;
if ($eg == FALSE) {
    echo "Cannot get a new Grid!\n";
    exit;
}
$eg->set_host($rh);
$eg->set_user($ru);
$eg->set_password($password);
$eg->set_passphrase($psp);
$eg->set_work_dir("./tmp");
$eg->set_error_log("/tmp/grid/debug/connection.err");
$eg->connect();
$eg->initialize();
echo $eg->get_init_status();
$eg->destroy();
$eg->destruct();

exit;

$rmt = new SExec($remote, $password);
if ($rmt == FALSE) 
{
    echo "Couldn't open the connection\n";
    exit;
}

$debug_ssh_open_cmd = TRUE;
if (isset($debug_ssh_open_cmd))
{
    $p = $rmt->ssh_open_command("ls");
    
    sleep(1);
#    fwrite($p["std_in"], "HOOOOOOLA\n\004");
    
    echo "flushing files\nstdout\n";
    flush();
    
    rewind($p["std_out"]);
    do {
        $line = fgets($p["std_out"], 1024);
        echo "> ".$line;
    } while (! feof($p["std_out"]) );
    
    $rmt->ssh_close($p);
}

/*
$debug_copy_to = TRUE;
if (isset($debug_copy_to)) {
    $rmt->ssh_copy_to("./TOCOPY", "k", $out);
}

$debug_copy_from = TRUE;
if (isset($debug_copy_from)) {
    $rmt->ssh_copy_from("./k/TOGET", "/tmp/TOGET", $out);
}

$debug_passthru = TRUE;
if (isset($debug_passthru)) 
{
    $rmt->ssh_passthru("ls k", $status=0);
}
*/
$rmt->destruct();
echo "</pre>\n";

 
?>
