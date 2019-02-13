<?
$a = 1;
$b = 2;

Sum();
echo $b;

function Sum()
{
    $GLOBALS['b'] = $GLOBALS['a'] + $GLOBALS['b'];
}
?> 
