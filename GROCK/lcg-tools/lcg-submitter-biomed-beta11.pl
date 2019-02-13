#!/usr/bin/perl 
########################################################################
# Interface to submit 6000 jobs from grid.php using 8 different RBs
#######################################################################

use File::Basename;

$instdir = dirname($0);

####  ENVIRONMENT ####

$ENV{APEL_HOME} = "/opt/glite";
$ENV{CLASSADJ_INSTALL_PATH} = "/usr";
$ENV{COG_INSTALL_PATH} = "/usr";
$ENV{EDG_LOCATION} = "/opt/edg";
$ENV{EDG_LOCATION_VAR} = "/opt/edg/var";
$ENV{EDG_TMP} = "/tmp";
$ENV{EDG_WL_LIBRARY_PATH} = "/opt/globus/lib:/opt/edg/lib";
$ENV{EDG_WL_LOCATION} = "/opt/edg";
$ENV{EDG_WL_PATH} = "/opt/edg/bin:/opt/edg/sbin";
$ENV{G_BROKEN_FILENAMES} = "1";
$ENV{GLITE_LOCATION_LOG} = "/opt/glite/log";
$ENV{GLITE_LOCATION} = "/opt/glite";
$ENV{GLITE_LOCATION_TMP} = "/opt/glite/tmp";
$ENV{GLITE_LOCATION_VAR} = "/opt/glite/var";
$ENV{GLOBUS_LOCATION} = "/opt/globus";
$ENV{GLOBUS_PATH} = "/opt/globus";
$ENV{GLOBUS_TCP_PORT_RANGE} = "20000 25000";
$ENV{GPT_LOCATION} = "/opt/gpt";
$ENV{JAVA_INSTALL_PATH} = "/usr/java/j2sdk1.4.2_04";
$ENV{LCG_GFAL_INFOSYS} = "lcg-bdii.cern.ch:2170";
$ENV{LCG_LOCATION} = "/opt/lcg";
$ENV{LCG_LOCATION_VAR} = "/opt/lcg/var";
$ENV{LCG_TMP} = "/tmp";
$ENV{LD_LIBRARY_PATH} = "/opt/glite/lib:/opt/lcg/lib:/opt/globus/lib:/opt/edg/lib:/usr/local/lib:/opt/glite/lib:/opt/glite/externals/lib:/opt/d-cache/dcap/lib";
$ENV{LIBPATH} = "/opt/globus/lib:/usr/lib:/lib";
$ENV{LOG4J_INSTALL_PATH} = "/usr";
$ENV{MYPROXY_SERVER} = "adc0024.cern.ch";
$ENV{PATH} = "/opt/glite/bin:/usr/java/j2sdk1.4.2_09/bin:/opt/lcg/bin:/usr/kerberos/bin:/opt/globus/bin:/opt/globus/sbin:/opt/edg/bin:/usr/local/bin:/bin:/usr/bin:/usr/local/bin:/usr/bin/X11:/opt/glite/bin:/opt/glite/externals/bin:/opt/gpt/sbin:/opt/d-cache/srm/bin:/opt/d-cache/dcap/bin:/opt/edg/sbin:/usr/X11R6/bin";
$ENV{PERLLIB} = "/opt/glite/lib/perl5:/opt/lcg/lib/perl";
$ENV{PYTHONPATH} = "/opt/edg/lib:/opt/edg/lib/python:/opt/lcg/lib/python:/opt/glite/lib/python";
$ENV{RGMA_HOME} = "/opt/glite";
$ENV{SASL_PATH} = "/opt/globus/lib/sasl";
$ENV{SHLIB_PATH} = "/opt/globus/lib";
$ENV{SRM_PATH} = "/opt/d-cache/srm";

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
    
    # Load balance betwenn our RB, PIC and other RBs. Known RB limitations.
    
    if ($roll == 1)
    {
    	$RBs = "-c $instdir/RBs/bioinfo02.pcm.uam.es.conf  --config-vo $instdir/RBs/bioinfo02.pcm.uam.es.vo.conf";
	print "$v_dirs[$i] submitted using the RB: egee-rb-01.cnaf.infn.it\n";
    }
    
    if ($roll == 2)
    {
	#$RBs = "-c $instdir/RBs/rb01.pic.es.conf  --config-vo $instdir/RBs/rb01.pic.es.vo.conf";
	#print "$v_dirs[$i] submitted using the RB: rb01.pic.es\n";
	$RBs = "-c $instdir/RBs/egee-rb-07.cnaf.infn.it.conf  --config-vo $instdir/RBs/egee-rb-07.cnaf.infn.it.vo.conf";
        print "$v_dirs[$i] submitted using the RB: egee-rb-01.cnaf.infn.it\n";
    }
    
    if ($roll == 3)
    {
    	$RBs = "-c $instdir/RBs/bioinfo02.pcm.uam.es.conf  --config-vo $instdir/RBs/bioinfo02.pcm.uam.es.vo.conf";
	print "$v_dirs[$i] submitted using the RB: egee-rb-01.cnaf.infn.it\n";
    }
    
     if ($roll == 4)
    {
	#$RBs = "-c $instdir/RBs/rb01.pic.es.conf  --config-vo $instdir/RBs/rb01.pic.es.vo.conf";
	#print "$v_dirs[$i] submitted using the RB: rb01.pic.es\n";
	$RBs = "-c $instdir/RBs/egee-rb-07.cnaf.infn.it.conf  --config-vo $instdir/RBs/egee-rb-07.cnaf.infn.it.vo.conf";
        print "$v_dirs[$i] submitted using the RB: egee-rb-01.cnaf.infn.it\n";
    }
    
    if ($roll == 5)
    {
    	$RBs = "-c $instdir/RBs/bioinfo02.pcm.uam.es.conf  --config-vo $instdir/RBs/bioinfo02.pcm.uam.es.vo.conf";
	print "$v_dirs[$i] submitted using the RB: egee-rb-01.cnaf.infn.it\n";
    }

    if ($roll == 6)
    {
    	#$RBs = "-c $instdir/RBs/rb01.pic.es.conf  --config-vo $instdir/RBs/rb01.pic.es.vo.conf";
	#print "$v_dirs[$i] submitted using the RB: rb01.pic.es\n";
	$RBs = "-c $instdir/RBs/egee-rb-07.cnaf.infn.it.conf  --config-vo $instdir/RBs/egee-rb-07.cnaf.infn.it.vo.conf";
        print "$v_dirs[$i] submitted using the RB: egee-rb-01.cnaf.infn.it\n";
    }
    
     if ($roll == 7)
    {
    	$RBs = "-c $instdir/RBs/bioinfo02.pcm.uam.es.conf  --config-vo $instdir/RBs/bioinfo02.pcm.uam.es.vo.conf";
	print "$v_dirs[$i] submitted using the RB: egee-rb-01.cnaf.infn.it\n";
    }

   if ($roll == 8)
    {
    	$RBs = "-c $instdir/RBs/egee-rb-07.cnaf.infn.it.conf  --config-vo $instdir/RBs/egee-rb-07.cnaf.infn.it.vo.conf";
	print "$v_dirs[$i] submitted using the RB: egee-rb-01.cnaf.infn.it\n";
    }
    
    if ($roll == 9)
    {
    	$RBs = "-c $instdir/RBs/bioinfo02.pcm.uam.es.conf  --config-vo $instdir/RBs/bioinfo02.pcm.uam.es.vo.conf";
	print "$v_dirs[$i] submitted using the RB: egee-rb-01.cnaf.infn.it\n";
    }

    if ($roll == 10)
    {
        #$RBs = "-c $instdir/RBs/grid09.lal.in2p3.fr.conf  --config-vo $instdir/RBs/grid09.lal.in2p3.fr.vo.conf";
        #print "$v_dirs[$i] submitted using the RB: grid09.lal.in2p3.fr\n";
        $RBs = "-c $instdir/RBs/bioinfo02.pcm.uam.es.conf  --config-vo $instdir/RBs/bioinfo02.pcm.uam.es.vo.conf";
        print "$v_dirs[$i] submitted using the RB: egee-rb-01.cnaf.infn.it\n";

    }

    if ($roll == 11)
    {
	$RBs = "-c $instdir/RBs/node04.datagrid.cea.fr.conf  --config-vo $instdir/RBs/node04.datagrid.cea.fr.vo.conf";
	print "$v_dirs[$i] submitted using the RB: node04.datagrid.cea.fr\n";
    }

    #if ($roll == 8)
    #{
	#$RBs = "-c $instdir/RBs/rb.isabella.grnet.gr.conf  --config-vo $instdir/RBs/rb.isabella.grnet.gr.vo.conf";
	#print "$v_dirs[$i] submitted using the RB: rb.isabella.grnet.gr\n";
    #}



    $receptor_path = "$session/$v_dirs[$i]";
    #$command = "(sleep 300; pkill -P \$\$) & glite-job-submit $RBs -o $receptor_path/identifier.txt $receptor_path/job.jdl > $receptor_path/submit.txt ; kill \$!";
    $command = "(sleep 300; pkill -P \$\$) & edg-job-submit $RBs -o $receptor_path/identifier.txt $receptor_path/job.jdl > $receptor_path/submit.txt ; kill \$!";

    system("cd $session/$v_dirs[$i]; $command");
}

