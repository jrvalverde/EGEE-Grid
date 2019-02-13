<?php
print("pid= " . posix_getpid() . "\n");
declare(ticks=1);
$arrsignals = array();

function handler($nsig)
{
    global $arrsignals;
    $arrsignals[] = (int)$nsig;
    print("Signal caught and registered.\n");
    var_dump($arrsignals);
}

pcntl_signal(SIGTERM, 'handler');

// Wait for signals from the command-line (just a simple 'kill (pid)').
$n = 15;
while($n)
{
    sleep(1);
    $n--;
}

print("terminated.\n\n");
var_dump($arrsignals);
?>
