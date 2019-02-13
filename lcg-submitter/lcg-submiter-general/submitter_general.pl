#!/usr/bin/perl 

#------------------------------------------------------------------
# Patricia's script for the automatic split of jobs 
# USAGE:
# ./submitter_general.pl [-jobfile] [-jdl] [-data] [-CEName]
# -jobfile: contains the jobids of all jobs. Not mandatory
# -jdl: Mandatory. Contaings the jdl example to look for possible sites
# -data: In case data are needed it contains the corresponding lfns
# -CEName: allows to specify a certain CE(or CEs) where to send the job 
#------------------------------------------------------------------

use File::Copy;    # a Perl package to copy files.
 
for ($i = 0; $i<=$#ARGV; $i++){    # Loop over the input argument

    if ($ARGV[$i] =~ /^-jobfile$/) {
	for($j = $i + 1; $j <= $#ARGV; $j++) {
	    last if ($ARGV[$j] =~ /^-/);
	    push (@jobid,$ARGV[$j]);
	    $i = $j-1;
	}
	$jobfile = "@jobid";
    }

    if ($ARGV[$i] =~ /^-vo$/) {
	for($j = $i + 1; $j <= $#ARGV; $j++) {
	    last if ($ARGV[$j] =~ /^-/);
	    push (@vos,$ARGV[$j]);
	    $i = $j-1;
	}
	$vo = "@vos";
    }

    if ($ARGV[$i] =~ /^-data$/) {
	for($j = $i + 1; $j <= $#ARGV; $j++) {
	    last if ($ARGV[$j] =~ /^-/);
	    push (@datos,$ARGV[$j]);
	    $i = $j-1;
	}
	$data = "@datos";
    }

    if ($ARGV[$i] =~ /^-CEName$/) {
	for($j = $i + 1; $j <= $#ARGV; $j++) {
	    last if ($ARGV[$j] =~ /^-/);
	    push (@name1,$ARGV[$j]);
	    $i = $j-1;
	}
	$CEName = "@name1";
    }

    if ($ARGV[$i] =~ /^-jdl$/) {
	for($j = $i + 1; $j <= $#ARGV; $j++) {
	    last if ($ARGV[$j] =~ /^-/);
	    push (@name2,$ARGV[$j]);
	    $i = $j-1;
	}
	$jdl = "@name2";
    }
}



#Some checks 
if (!$jobfile){
    die "Hey, the jobfile name!\n";
}
if (!$jdl){
    die "I need a jdl example!\n";
}
if (!$vo){
    die "The vo is missed!\n";
}

if ($data){

    @the_lfn = split /\s+/, $data;
    $numberofdata = @the_lfn;

    for ($i=0;$i<$numberofdata;$i++){
	print "$the_lfn[$i]\n";
	my @replica = ("lcg-lr","--vo","$vo","lfn:$the_lfn[$i]");
	push @allreplicas, [@replica];
    }
    
    for $i(0 ..$#allreplicas){
	system "lcg-gt `@{$allreplicas[$i]}` gsiftp >> turl_number_files.txt";	
    }

}

# Get all the candidate nodes. $$ adds a number which is different
# in each execution of the program.

system "edg-job-list-match --vo $vo $jdl >> /tmp/geant4sites$$";

open FILE, "</tmp/geant4sites$$";
    
# Get rid of the first part of the result of the previous command
# (edg-job-list-match) and take only those lines which have a node
# address, i.e. in which appears the character "/".
while (<FILE>){
    chomp;
    $one_line = $_;

    if ($one_line =~ /\//){
	$one_CE = $one_line;
	push (@all_CEs, $one_CE);
    }
    $counter = @all_CEs;
}

close FILE;

@all_CEs = sort @all_CEs;

# Of the above selected list, keep only those nodes which have
# the chosen queue

%the_hash;

for ($j=0;$j<$counter;$j++){
 
    @nombresCEs = split/:/,$all_CEs[$j];
    $elementonuevoCE[$j] = $nombresCEs[0];
    $the_hash{$elementonuevoCE[$j]} = $all_CEs[$j];
    @valores = values %the_hash;
    @llaves = keys %the_hash;
    $counter2 = @valores;
}

system "rm /tmp/geant4sites$$";

# Make a further selection of the selected list: eliminar espacios en blanco
for ($j=0;$j<$counter2;$j++){
    $all_CEs3[$j] = "$valores[$j]";
    $all_CEs3[$j] =~ s/\s//g;
    push (@cesfinales,$all_CEs3[$j]);
    $counter3 = @cesfinales;
}

# In the case the user specifies the CE(s) check whether they
# are allowed or not

if ($CEName){

    my %requestedCE;

    @cesinputs = split /\s+/, $CEName;
    $numberofceinputs = @cesinputs;

    for ($j=0;$j<$counter3;$j++){
	$requestedCE{"$cesfinales[$j]"} = 1;
    }

    for ($i=0;$i<$numberofceinputs;$i++){
	if (!exists $requestedCE{$cesinputs[$i]}){
	    print "The CE: $cesinputs[$i] does not matched with your jdl requirements\n";
	} else{
	    push(@finalCEs,$cesinputs[$i]);
	    $counter_finalCEs = @finalCEs;
	}
    }

    for ($i=0;$i<$counter_finalCEs;$i++){
	@finalCEs2 = split/:/,$finalCEs[$i];
	push(@finalCE3,$finalCEs2[0]);
    }    
}

# Putting the list of final queues in a file

if (!$CEName){
    mkdir "$jobfile", 0755 or warn "$jobfile directory cannot be made: $!";
    if ($data){
	system "mv turl_number_files.txt $jobfile";	
    }
    open FILE,">>$jobfile/CEslist.dat" or die "File cannot be opened\n";
    for ($i=0;$i<$counter3;$i++){
	print FILE "$cesfinales[$i]\n";
    }
    close FILE;
}

if ($CEName){
    mkdir "$jobfile", 0755 or warn "$jobfile directory cannot be made: $!";
    if ($data){
	system "mv turl_number_files.txt $jobfile";	
    }
    open FILE,">>$jobfile/CEslist.dat" or die "File cannot be opened\n";
    for ($i=0;$i<$counter_finalCEs;$i++){
	print FILE "$finalCEs[$i]\n";
    }
    close FILE;
}

# Now we have the final list of candidate nodes, and for each
# of them build a .jdl  in which such node is required. To do
# so the template geant4_jdl.template is used.

if (!$CEName){

    for ($j=0;$j<$counter3;$j++){

	@args = '';

	copy("$jdl", "$jobfile/test_x_$j.jdl");

	open VO, "<$jobfile/test_x_$j.jdl" or die "File cannot be opened2\n";
	while (<VO>){
	    chomp;
	    $line = $_;
	    if ($line=~ /Requirements/){
		$line = "Requirements = other.GlueCEUniqueID == \"$cesfinales[$j]\"; ";
	    }
	    if ($data){

		if ($line=~ /^InputSandbox/){
		    @this_line = split/\};/,$line;
		    $line = "$this_line[0],\"$jobfile/turl_number_data.txt\"};";
		}
	    }
	    push(@args,$line);
	    $tmp = @args;
	}
	
	close VO;
	
	unlink "$jobfile/test_x_$j.jdl";
	
	open VO2, ">$jobfile/test_x_$j.jdl";
	for($i=0;$i<$tmp;$i++){
	    print VO2 "$args[$i]\n";
	}
	
	close VO2;
    }
}

if ($CEName){

    for ($j=0;$j<$counter_finalCEs;$j++){

	@args = '';

	copy("$jdl", "$jobfile/test_x_$j.jdl");
	open VO, "<$jobfile/test_x_$j.jdl" or die "File cannot be opened2\n";
	while (<VO>){
	    chomp;
	    $line = $_;
	    if ($line=~ /Requirements/){
		$line = "Requirements = other.GlueCEUniqueID == \"$finalCEs[$j]\"; "; 	
	    }
	    if ($data){

		if ($line=~ /^InputSandbox/){
		    @this_line = split/\};/,$line;
		    $line = "$this_line[0],\"$jobfile/turl_number_data.txt\"};";
		}
	    }
	    push(@args,$line);
	    $tmp = @args;
	}
	
	close VO;
	
	unlink "$jobfile/test_x_$j.jdl";
	
	open VO2, ">$jobfile/test_x_$j.jdl";
	for($i=0;$i<$tmp;$i++){
	    print VO2 "$args[$i]\n";
	}
	close VO2;
    }
}

# Submit all the jobs: the idea is to send a job for each of the candidate
# nodes we have found so far, the only difference between their .jdl is 
# only in the CE name requirement.
if(!$CEName){
    for ($j=0;$j<$counter2;$j++){
    system "edg-job-submit --vo $vo -o $jobfile/job_$j.jobid $jobfile/test_x_$j.jdl";
    }
}

if($CEName){
    for ($j=0;$j<$counter_finalCEs;$j++){
	system "edg-job-submit --vo $vo -o $jobfile/job_$j.jobid $jobfile/test_x_$j.jdl";
    }
}
