/**
** Simple dynamic HTML popup handler
** (c) Gareth Watts, August 2003
*/

gwActivePopup=null; // global
gwTimeoutId=0;
function gwPopup(e,layerid) {
    gwCloseActive();
    if (e.pageX||e.pageY) {
        x=e.pageX; y=e.pageY;
    } else if (e.clientX||e.clientY) {
        if (document.documentElement && document.documentElement.scrollTop) {
            x=e.clientX+document.documentElement.scrollLeft; y=e.clientY+document.documentElement.scrollTop;
        } else {
            x=e.clientX+document.body.scrollLeft; y=e.clientY+document.body.scrollTop;
        }
    } else {
        return; 
    }
    layer=document.getElementById(layerid);
    layer.style.left=x;
    layer.style.top=y;
    layer.style.visibility='Visible';
    gwActivePopup=layer;
    clearTimeout(gwTimeoutId); gwTimeoutId=0;
    gwTimeoutId=setTimeout("gwCloseActive()", 5000);
    layer.onmouseout=function() { clearTimeout(gwTimeoutId); gwTimeoutId=setTimeout("gwCloseActive()", 350); } 
    layer.onmouseover=function() { clearTimeout(gwTimeoutId); gwTimeoutId=0;}
}

function gwCloseActive() {
    if (gwActivePopup) {
        gwActivePopup.style.visibility='Hidden';
        gwActivePopup=null;
    }
}

function funcPopup(e, encfuncname) {
    gwCloseActive();
    title=document.getElementById('func-title');
    body=document.getElementById('func-body');
    desc=document.getElementById('func-desc');

    funcdata=FUNC_DATA[encfuncname];
    title.innerHTML=funcdata[0]+'()';
    desc.innerHTML=funcdata[1];
    bodyhtml='';
    deflist=funcdata[2];
    defcount=deflist.length;
    funcurl=relbase+'_functions/'+encfuncname+'.html';
    //bodyhtml='Referenced <a href="'+funcurl+'">'+funcdata[3]+' times</a><br>\n';
    if (defcount>0) {
        pl=defcount==1 ? '' : 's';
        bodyhtml+=defcount+' definition'+pl+':<br>\n';
        for(i=0;i<defcount;i++) {
             dir=deflist[i][0];
             if (dir!='') { dir+='/'; }
             bodyhtml+='&nbsp;&nbsp;<a href="'+relbase+deflist[i][0]+'/'+deflist[i][1]+'.source'+ext+'#l'+deflist[i][2]+'">'+dir+deflist[i][1]+'</a><br>\n';
        }       
    } else {
        bodyhtml+='No definitions<br>\n';
    }
    body.innerHTML=bodyhtml;

    gwPopup(e, 'func-popup');
}


function classPopup(e, encclassname) {
    gwCloseActive();
    title=document.getElementById('class-title');
    body=document.getElementById('class-body');
    desc=document.getElementById('class-desc');

    classdata=CLASS_DATA[encclassname];
    title.innerHTML=classdata[0]+'::';
    desc.innerHTML=classdata[1];
    bodyhtml='';
    deflist=classdata[2];
    defcount=deflist.length;
    classurl=relbase+'_classes/'+encclassname+'.html';
    //bodyhtml='Referenced <a href="'+classurl+'">'+classdata[3]+' times</a><br>\n';
    if (defcount>0) {
        pl=defcount==1 ? '' : 's';
        bodyhtml+=defcount+' definition'+pl+':<br>\n';
        for(i=0;i<defcount;i++) {
             dir=deflist[i][0];
             if (dir!='') { dir+='/'; }
             bodyhtml+='&nbsp;&nbsp;<a href="'+relbase+deflist[i][0]+'/'+deflist[i][1]+'.source'+ext+'#l'+deflist[i][2]+'">'+dir+deflist[i][1]+'</a><br>\n';
        }       
    } else {
        bodyhtml+='No definitions<br>\n';
    }
    body.innerHTML=bodyhtml;

    gwPopup(e, 'class-popup');
}

function reqPopup(e, name, baseurl) {
    gwCloseActive();
    title=document.getElementById('req-title');
    body=document.getElementById('req-body');

    title.innerHTML=name;
    body.innerHTML='<a href="'+baseurl+'.source'+ext+'">Source</a>&nbsp;|&nbsp;'
        +'<a href="'+baseurl+ext+'">Summary</a>';
    gwPopup(e, 'req-popup');
}


function handleNavFrame(relbase, subdir, filename) {
    navstatus=gwGetCookie('xrefnav');
    if (navstatus!='off' && (parent.name!='phpxref' || parent==self)) {
        if (subdir!='') { subdir+='/'; }
        parent.location=relbase+'nav'+ext+'?'+subdir+filename;
    } else if (parent.nav && parent.nav.open_branch) {
        parent.nav.open_branch(subdir);
    }
}

function gwGetCookie(name) {
    var cookies=document.cookie;
    if ((offset=cookies.indexOf(name))==-1)
        return null;
    if ((endpoint=cookies.indexOf(';', offset))==-1)
        endpoint=cookies.length;
    value=unescape(cookies.substring(offset+name.length+1, endpoint));
    return value;
}

function gwSetCookie(name, value) {
    document.cookie=name+'='+escape(value)+';path=/';
}

function navOn() {
    gwSetCookie('xrefnav','on');
    self.location.reload();
}

function navOff() {
    gwSetCookie('xrefnav','off');
    parent.location.href=self.location.href;
}

