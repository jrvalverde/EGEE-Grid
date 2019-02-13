<?php
  require_once('ssh.php');
  $user = $_GET['user'];
  $password = $_GET['password'];

  $rhost = new SExec("$user@example.com", $password);
  if ($rhost == FALSE) echo "ERROR: could not connect";

  echo "<h1>Working directory on remote host is:<br />\n";
  $rhost->ssh_passthru("pwd", $status=0);
  
  $rhost->destruct();
  echo "</h1>\n";

?>
