#!/usr/bin/perl

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
$ENV{LD_LIBRARY_PATH} = "/opt/lcg/lib:/opt/globus/lib:/opt/edg/lib:/usr/local/lib:/usr/local/lib:/usr/local/lib:/opt/d-cache/dcap/lib:/opt/d-cache/dcap/lib:/opt/d-cache/dcap/lib:/opt/globus/lib:/opt/edg/lib:/opt/globus/lib:/opt/edg/lib";
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
	    last if $ARGV[$j] =~ /^-/;
	    push (@name1,$ARGV[$j]);
	    $i = $j-1;
	}
	$jobfile = "@name1";
    }


    if ($ARGV[$i] =~ /^-html$/) {
	for($j = $i + 1; $j <= $#ARGV; $j++) {
	    last if $ARGV[$j] =~ /^-/;
	    push (@name3,$ARGV[$j]);
	    $i = $j-1;
	}
	$html = "@name3";
    }
}

$currentdir = $ENV{PWD};

if (!$jobfile){die "Give the subdirectory name\n";}

my @v_dirs;

opendir(THISDIR,"$jobfile") or die "no se abre: $!";
if (-e "$jobfile/report.html"){
    print "removing old version of $jobfile/report.html\n";
system "rm $jobfile/report.html";}
if (-e "$jobfile/test_wn_capabilities"){system "rm $jobfile/test_wn_capabilities";}
while (my $dirs = readdir THISDIR){
    next if $dirs =~ /^\./;
    push(@v_dirs, $dirs);
}

$n_recep = @v_dirs;

my $jobid;
my $jobid2;

for ($i=0;$i<$n_recep;$i++){    
    mkdir "$jobfile/$v_dirs[$i]/OUTPUT", 0755;
}

for ($i=0;$i<$n_recep;$i++){

    opendir(THISDIR,"$jobfile/$v_dirs[$i]") or die "no se abre $jobfile/$v_dirs[$i]: $!";
    @alljobs = grep { $_ =~/identifier.txt/ } readdir THISDIR;
    closedir THISDIR;

    open FILE, "<$jobfile/$v_dirs[$i]/@alljobs" or die "file cannot be opened\n";
    while (<FILE>){
	chomp;
	$one_line = $_;
	if ($one_line =~ /https/){
	    $jobid = $one_line;
	}
    }
    close FILE;

    if (-e "$jobfile/$v_dirs[$i]/test_x.tmp3"){
	system "rm $jobfile/$v_dirs[$i]/test_x.tmp3";
    }
    system "edg-job-status $jobid >> $jobfile/$v_dirs[$i]/test_x.tmp3"; 
}
for ($i=0;$i<$n_recep;$i++){

    opendir(THISDIR,"$jobfile/$v_dirs[$i]") or die "no se abre: $!";
    @allstatus = grep { $_ =~/\.tmp3/ } readdir THISDIR;
    closedir THISDIR;

    open FILE, "<$jobfile/$v_dirs[$i]/@allstatus" or die "file cannot be opened\n";
    while (<FILE>){
	chomp;
	$one_line = $_;
	if ($one_line =~ /Current\sStatus:\s+(\S+)/){
	    $status = $1;
	}
	if ($one_line =~ /Status\sinfo\sfor\sthe\sJob\s:\s+(\S+)/){
	    $job = $1;
	}
	if ($one_line =~ /Destination:\s+(\S+)/){
	    $destino = $1;
	}
    }
    close FILE;
    
    if ("$status" eq "Done"){
	print "The Job ran in $destino is over. \n"
	    .    "Retrieving it's output to $jobfile/$v_dirs[$i]/test_x.tmp2\n";
	    
	system"edg-job-get-output -dir $jobfile/$v_dirs[$i]/OUTPUT $job >> $jobfile/$v_dirs[$i]/test_x.tmp2";
    }
    else{
	print "The Job ran in $destino is in status: $status\n";
    }
}

for ($i=0;$i<$n_recep;$i++){    

    opendir(THISDIR,"$jobfile/$v_dirs[$i]/OUTPUT") or die "no se abre el output dir: $!";
    @alloutputs = readdir THISDIR;
    $counter5 = @alloutputs;
    closedir THISDIR;
    
    
    @finalfiles=();
      theloop:for ($j=0;$j<$counter5;$j++){
	  if ($alloutputs[$j] =~/^\./) {next theloop;} 
	  else{
	      push (@finalfiles,$alloutputs[$j]);
	  }
      }
    
    chdir "$jobfile/$v_dirs[$i]/OUTPUT";
    system "pwd";
    print "Los files a mover de nombre: @finalfiles\n";
    
    system "mv @finalfiles test_x.outputdir";
    
    chdir $currentdir;
    
}

if ($html){

    print "I am using the option html\n";
    $a = $n_recep-1;


    for ($i=0;$i<$n_recep;$i++){

	opendir(THISDIR,"$jobfile/$v_dirs[$i]") or die "no se abre: $!";
	@alljobs2 = grep { $_ =~/identifier.txt/ } readdir THISDIR;
	closedir THISDIR;

	open TMPCESE, ">>$jobfile/CEslist.dat" or die "CEslist no se puede abrir";
	open FILE, "<$jobfile/$v_dirs[$i]/@alljobs2" or die "file cannot be opened\n";
	while (<FILE>){
	    chomp;
	    $one_line2 = $_;
	    if ($one_line2 =~ /https/){
		$jobid2 = $one_line2;
		print TMPCESE "$jobid2\n";
	    }
	}
	close TMPCESE;
	close FILE;
	

	my @fileinputs = split /\s+/, $html;

	print "The curentdir is: $currentdir\n";

	system "./make_html_report.py -l $jobfile/CEslist.dat -d $jobfile/$v_dirs[$i] -o $jobfile/$v_dirs[$i]/OUTPUT -f \'@fileinputs\'";

	if ("$i" eq "$a"){
	    print "I am here\n";
	    system "mv $jobfile/$v_dirs[$i]/report.html $jobfile";
	}

    }

}
    
if ($html){   
    system "rm $jobfile/CEslist.dat";
}


