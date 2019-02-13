#!/usr/bin/perl

for ($i = 0; $i<=$#ARGV; $i++){    # Loop over the input argument
    if ($ARGV[$i] =~ /^-jobfile$/) {
	for($j = $i + 1; $j <= $#ARGV; $j++) {
	    last if $ARGV[$j] =~ /^-/;
	    push (@name1,$ARGV[$j]);
	    $i = $j-1;
	}
	$jobfile = "@name1";
    }

    if ($ARGV[$i] =~ /^-dest$/) {
	for($j = $i + 1; $j <= $#ARGV; $j++) {
	    last if $ARGV[$j] =~ /^-/;
	    push (@name2,$ARGV[$j]);
	    $i = $j-1;
	}
	$dest = "@name2";
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
if (!$dest){die "Give the destination of the outputs\n";}

mkdir "$dest", 0755;

opendir(THISDIR,"$jobfile") or die "no se abre: $!";
@alljobs = grep { $_ =~/\.jobid/ } readdir THISDIR;
$counter = @alljobs;
closedir THISDIR;

for ($i=0;$i<$counter;$i++){
    open FILE, "<$jobfile/$alljobs[$i]" or die "file cannot be opened\n";
    while (<FILE>){
	chomp;
	$one_line = $_;
	if ($one_line =~ /https/){
	    $jobid = $one_line;
	}
    }
    close FILE;
    push (@v_https,$jobid);
    $counter2 = @v_https;
}

for ($i=0;$i<$counter2;$i++){
    if (-e "$jobfile/test_x_$i.tmp3"){
	system "rm $jobfile/test_x_$i.tmp3";
    }
    system "edg-job-status $v_https[$i] >> $jobfile/test_x_$i.tmp3"; 
}

opendir(THISDIR,"$jobfile") or die "no se abre: $!";
@allstatus = grep { $_ =~/\.tmp3/ } readdir THISDIR;
$counter3 = @allstatus;
closedir THISDIR;

for ($i=0;$i<$counter3;$i++){
    open FILE, "<$jobfile/$allstatus[$i]" or die "file cannot be opened\n";
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
    push (@v_status,$status);
    push (@v_job,$job);
    push (@v_destino,$destino);
    $counter4 = @v_status;
}

for ($i=0;$i<$counter4;$i++){
    if ("$v_status[$i]" eq "Done"){
	print "The Job ran in $v_destino[$i] is over. \n"
	    .    "Retrieving it's output to $dest/test_x_$i.tmp2\n";

	system"edg-job-get-output -dir $dest $v_job[$i] >> $jobfile/test_x_$i.tmp2";
    }
    else{
	print "The Job ran in $v_destino[$i] is in status: $v_status[$i]\n";
    }
}

opendir(THISDIR,"$dest") or die "no se abre: $!";
@alloutputs = readdir THISDIR;
$counter5 = @alloutputs;
closedir THISDIR;

 theloop:for ($i=0;$i<$counter5;$i++){
     if ($alloutputs[$i] =~/^\./) {next theloop;}
     else{
	 push (@finalfiles,$alloutputs[$i]);
	 $counter6 = @finalfiles;
     }
 }

chdir $dest;

for ($i=0;$i<$counter6;$i++){
    print "$finalfiles[$i]\n";
    system "mv $finalfiles[$i] test_x_$i.outputdir > /dev/null 2> /dev/null";
}

chdir $currentdir;

if ($html){
    my @fileinputs = split /\s+/, $html;
    system "./make_html_report.py -l $jobfile/CEslist.dat -d $jobfile -o $dest -f \'@fileinputs\'";
}
