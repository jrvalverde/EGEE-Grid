<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
</head>
<body>

<script type="text/javascript">
var xmlHttp

function showHint(str)
{

if (str.length > 0)
   {            
   var url="gethint.asp?sid=" + Math.random() + "&q=" + str
   xmlHttp=GetXmlHttpObject(stateChanged)
   xmlHttp.open("GET", url , true)
   xmlHttp.send(null)
   } 
   else
   { 
   document.getElementById("txtHint").innerHTML=""
   } 
} 

function stateChanged() 
{ 
if (xmlHttp.readyState==4 || xmlHttp.readyState=="complete")
   { 
   document.getElementById("txtHint").innerHTML=xmlHttp.responseText 
   } 
} 

function GetXmlHttpObject(handler)
{ 
var objXmlHttp=null
if (navigator.userAgent.indexOf("Opera")>=0)
   {
    alert("This example doesn't work in Opera") 
    return  
   }
if (navigator.userAgent.indexOf("MSIE")>=0)
   { 
   var strName="Msxml2.XMLHTTP"
   if (navigator.appVersion.indexOf("MSIE 5.5")>=0)
      {
      strName="Microsoft.XMLHTTP"
      } 
   try
      { 
      objXmlHttp=new ActiveXObject(strName)
      objXmlHttp.onreadystatechange=handler 
      return objXmlHttp
      } 
   catch(e)
      { 
      alert("Error. Scripting for ActiveX might be disabled") 
      return 
      } 
    } 
    
if (navigator.userAgent.indexOf("Mozilla")>=0)
   {
   objXmlHttp=new XMLHttpRequest()
   objXmlHttp.onload=handler
   objXmlHttp.onerror=handler 
   return objXmlHttp
   }
} 
</script>

<form> 
First Name:
<input type="text" id="txt1" onkeyup="showHint(this.value)">
</form>

<p>Suggestions: <span id="txtHint"></span></p> 

</body>
</html>
