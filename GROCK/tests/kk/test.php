<?
echo "aaaa<br>";

//foo();
$var=foo();
echo $var[1];

function foo()
{
   echo "SIIIIIIIII<br>";
   return preg_grep("/^https:/", file("./identifier.txt"));
   //return $var;
}

/*
function foo()
if (file_exists("./identifier.txt"))
{   
    	echo "SIIIIIIIII<br>";
   	return preg_grep("/^https:/", file("./identifier.txt")) );
	
} else
{
    echo "NOOOOOOO";
}
*/
?>
