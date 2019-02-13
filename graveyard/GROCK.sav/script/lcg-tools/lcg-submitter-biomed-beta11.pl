#!/usr/bin/perl 
########################################################################
# Interface to submit 6000 jobs from grid.php using 8 different RBs
#######################################################################

####  ENVIRONMENT ####
$ENV{PATH} = "$ENV{PATH}:/opt/lcg/bin:/opt/globus/bin:/opt/globus/sbin:/opt/edg/bin:/opt/gpt/sbin:/usr/java/j2sdk1.4.2_07/bin:/opt/d-cache/dcap/bin:/opt/d-cache/srm/bin:/opt/edg/bin:/opt/edg/sbin:/opt/edg/bin:/opt/edg/sbin";
$ENV{EDG_WL_LOCATION} = "/opt/edg";
$ENV{LCG_LOCATION} = "/opt/lcg";
$ENV{LCG_GFAL_INFOSYS} = "lxn1189.cern.ch:2170";
$ENV{EDG_LOCATION} = "/opt/edg";
$ENV{RGMA_PROPS} = "/opt/edg/etc/rgma";
$ENV{GLOBUS_PATH} = "/opt/globus";
$ENV{EDG_TMP} = "/tmp";
$ENV{EDG_WL_TMP} = "/var/edgwl";
$ENV{MYPROXY_SERVER} = "lxn1179.cern.ch";
$ENV{LCG_TMP} = "/tmp";
$ENV{EDG_WL_USER} = "edguser";
$ENV{LCG_LOCATION_VAR} = "/opt/lcg/var";
$ENV{GPT_LOCATION} = "/opt/gpt";
$ENV{LCG_CATALOG_TYPE} = "edg";
$ENV{GLOBUS_LOCATION} = "/opt/globus";
$ENV{EDG_WL_LOCATION_VAR} = "/opt/edg/var";
$ENV{LD_LIBRARY_PATH} = "/opt/lcg/lib:/opt/globus/lib:/opt/edg/lib:/usr/local/lib:/u
sr/local/lib:/usr/local/lib:/opt/d-cache/dcap/lib:/opt/d-cache/dcap/lib:/opt/d-cache
/dcap/lib:/opt/globus/lib:/opt/edg/lib:/opt/globus/lib:/opt/edg/lib";
$ENV{COG_INSTALL_PATH} = "/usr";
$ENV{RGMA_HOME} = "/opt/edg";
$ENV{EDG_LOCATION_VAR} = "/opt/edg/var";
$ENV{SASL_PATH} = "/opt/globus/lib/sasl";
$ENV{SHLIB_PATH} = "/opt/globus/lib";
$ENV{GLOBUS_TCP_PORT_RANGE} = "20000 25000";
#### ENVIRONMENT ####


for ($i = 0; $i<=$#ARGV; $i++){    # Loop over the input argument
    
    if ($ARGV[$i] =~ /^-session$/) {
        for($j = $i + 1; $j <= $#ARGV; $j++) {
            last if ($ARGV[$j] =~ /^-/);
            push (@var4,$ARGV[$j]);
            $i = $j-1;
        }
        $session = "@var4";
    }
}

unless ($session){
    die "You have not introduced the session option";
}

opendir(THISDIR,"$session") or die "no se abre: $!";
while (my $dirs = readdir THISDIR){
    next if $dirs =~ /^\./;
    push(@v_dirs, $dirs);
}

$n_recep = @v_dirs;

for ($i=0;$i<$n_recep;$i++){
    $roll = int(rand 11) + 1;
	
    print "cd $session/$v_dirs[$i]; $command\n";
    if ($roll == 1)
    {
    	$RBs = "-c /home/david/newgrock/RBs/egee-rb-01.cnaf.infn.it.conf  --config-vo /home/david/newgrock/RBs/egee-rb-01.cnaf.infn.it.vo.conf";
	#$RBs = "-c /home/david/newgrock/RBs/lxb0728.conf";
	print "$v_dirs[$i] submitted using the RB: egee-rb-01.cnaf.infn.it\n";
    }

    if ($roll == 2)
    {
    	$RBs = "-c /home/david/newgrock/RBs/grid09.lal.in2p3.fr.conf  --config-vo /home/david/newgrock/RBs/grid09.lal.in2p3.fr.vo.conf";
	#$RBs = "-c /home/david/newgrock/RBs/lxb0729.conf";
	print "$v_dirs[$i] submitted using the RB: grid09.lal.in2p3.fr\n";
    }

    if ($roll == 3)
    {
	$RBs = "-c /home/david/newgrock/RBs/gridit-rb-01.cnaf.infn.it.conf  --config-vo /home/david/newgrock/RBs/gridit-rb-01.cnaf.infn.it.vo.conf";
	#$RBs = "-c /home/david/newgrock/RBs/lxb2010.conf";
	print "$v_dirs[$i] submitted using the RB: gridit-rb-01.cnaf.infn.it\n";
    }

    if ($roll == 4)
    {
	$RBs = "-c /home/david/newgrock/RBs/lappgrid07.in2p3.fr.conf  --config-vo /home/david/newgrock/RBs/lappgrid07.in2p3.fr.vo.conf";
	#$RBs = "-c /home/david/newgrock/RBs/lxn1177.conf";
	print "$v_dirs[$i] submitted using the RB: lappgrid07.in2p3.fr\n";
    }

    if ($roll == 5)
    {
	$RBs = "-c /home/david/newgrock/RBs/lcg00124.grid.sinica.edu.tw.conf  --config-vo /home/david/newgrock/RBs/lcg00124.grid.sinica.edu.tw.vo.conf";
	#$RBs = "-c /home/david/newgrock/RBs/lxn1182.conf";
	print "$v_dirs[$i] submitted using the RB: lcg00124.grid.sinica.edu.tw\n";
    }

    if ($roll == 6)
    {
    	$RBs = "-c /home/david/newgrock/RBs/lcg00124.grid.sinica.edu.tw.conf  --config-vo /home/david/newgrock/RBs/lcg00124.grid.sinica.edu.tw.vo.conf";
	#$RBs = "-c /home/david/newgrock/RBs/lxn1182.conf";
	print "$v_dirs[$i] submitted using the RB: lcg00124.grid.sinica.edu.tw\n";
    #TO CHANGE, THE RB IS DOWN
	#$RBs = "-c /home/david/newgrock/RBs/lcgrb02.ifae.es.conf  --config-vo /home/david/newgrock/RBs/lcgrb02.ifae.es.vo.conf";
	#$RBs = "-c /home/david/newgrock/RBs/lxn1185.conf";
	#print "$v_dirs[$i] submitted using the RB: lcgrb02.ifae.es\n";
    }

    if ($roll == 7)
    {
	$RBs = "-c /home/david/newgrock/RBs/node04.datagrid.cea.fr.conf  --config-vo /home/david/newgrock/RBs/node04.datagrid.cea.fr.vo.conf";
	#$RBs = "-c /home/david/newgrock/RBs/lxn1186.conf";
	print "$v_dirs[$i] submitted using the RB: node04.datagrid.cea.fr\n";
    }

    if ($roll == 8)
    {
	$RBs = "-c /home/david/newgrock/RBs/rb.isabella.grnet.gr.conf  --config-vo /home/david/newgrock/RBs/rb.isabella.grnet.gr.vo.conf";
	#$RBs = "-c /home/david/newgrock/RBs/lxn1188.conf";
	print "$v_dirs[$i] submitted using the RB: rb.isabella.grnet.gr\n";
    }

    if ($roll == 9)
    {
	$RBs = "-c /home/david/newgrock/RBs/rb01.pic.es.conf  --config-vo /home/david/newgrock/RBs/rb01.pic.es.vo.conf";
	print "$v_dirs[$i] submitted using the RB: rb01.pic.es\n";
    }

    
    if ($roll == 10)
    {
	$RBs = "-c /home/david/newgrock/RBs/rb1.egee.fr.cgg.com.conf  --config-vo /home/david/newgrock/RBs/rb1.egee.fr.cgg.com.vo.conf";
	print "$v_dirs[$i] submitted using the RB: rb1.egee.fr.cgg.com\n";
    }

    if ($roll == 11)
    {
	$RBs = "-c /home/david/newgrock/RBs/rb101.grid.ucy.ac.cy.conf  --config-vo /home/david/newgrock/RBs/rb101.grid.ucy.ac.cy.vo.conf";
	print "$v_dirs[$i] submitted using the RB: rb101.grid.ucy.ac.cy\n";
    }



    $receptor_path = "$session/$v_dirs[$i]";
    $command = "edg-job-submit $RBs -o $receptor_path/identifier.txt $receptor_path/session.jdl > $receptor_path/submit.txt";
    system("cd $session/$v_dirs[$i]; $command");
}

