=head1 NAME

EMBOSS::GUI - provide web-based access to EMBOSS

=head1 AUTHOR

Luke McCarthy <lukem@gene.pbi.nrc.ca>

=head1 SYNOPSIS

  use EMBOSS::GUI;

  $emboss = EMBOSS::GUI->new();
  
  $emboss->intro_page;
  $emboss->about_page;
  $emboss->menu_page;
  $emboss->app_page;
  $emboss->help_page;
  $emboss->default_page;

=head1 DESCRIPTION

EMBOSS::GUI provides a simple web-based interface to the EMBOSS suite of
bioinformatics tools.  The distribution should have included a sample CGI
script that wraps the module appropriately.

Alternatively, EMBOSS::GUI can be used to gather information about the local
EMBOSS installation.  Public methods for that purpose are described below:

=over 4

=cut

package EMBOSS::GUI;

use strict;
use warnings;

use Carp;
use CGI;
use File::Basename;
use IPC::Open3;
use Mail::Send;
use Storable;

use EMBOSS::ACD;
use EMBOSS::GUI::Conf;
use EMBOSS::GUI::XHTML;

our $VERSION = 2.10;

our $RELEASE_VERSION = "2.2.0";

=item new(%args)

Returns a new EMBOSS::GUI object.

%args is a hash of optional named arguments.  The following arguments are
%recognized:

=over 4

=item html => $object

Specifies an alternative HTML renderer object to use when generating the web
interface.  See EMBOSS::GUI::XHTML for the methods the replacement object must
implement.

=back

=cut

sub new {
	my $invocant = shift;
	my $class = ref $invocant || $invocant;
	my %args = @_;
	my $self = {
		cgi => CGI->new(),	# CGI query object
		conf => EMBOSS::GUI::Conf->new(),	# site-specific configuration
	};
	$self->{html} = $args{html} || EMBOSS::GUI::XHTML->new(
		style_url => $self->{conf}->{STYLE_URL},
		image_url => $self->{conf}->{IMAGE_URL},
		manual_url => $self->{conf}->{MANUAL_URL}
	);
	bless $self, $class;
}

=item go()

Process CGI arguments and display the corresponding page.

=cut

sub go {
	my ($self) = @_;

	if (defined $ENV{PATH_INFO}) {
		my ($null, $target, $arg) = split /\//, $ENV{PATH_INFO};
		if (not defined $target ) {
			$self->default_page;
		} elsif ($target eq 'about') {
			$self->about_page;
		} elsif ($target eq 'menu') {
			$self->menu_page;
		} elsif ($target eq 'intro') {
			$self->intro_page;
		} elsif ($target eq 'help') {
			$self->help_page($arg);
		} elsif ($target eq 'output') {
			$self->output_page($arg);
		} elsif (length $target) {
			$self->app_page($target);
		} else {
			$self->default_page;
		}
	} else {
		$self->default_page;
	}
}

=item intro_page()

Generates an introductory page describing EMBOSS and the GUI.

=cut

sub intro_page {
	my ($self) = @_;

	print $self->_header, $self->{html}->intro_page;
}

=item about_page()

Generates a page describing the local EMBOSS configuration, including the
version and filesystem location of each perl module required by the GUI.

=cut

sub about_page {
	my ($self) = @_;

	# dump a plain text page listing some information about the installation,
	# useful for troubleshooting purposes...
	#
	my $divider = "\n";
	print $self->_header(-type => 'text/plain');
	print "EMBOSS Explorer v$RELEASE_VERSION\n";
	print $divider;
	foreach my $module ( qw( EMBOSS::ACD EMBOSS::GUI EMBOSS::GUI::Conf 
		EMBOSS::GUI::XHTML ) ) {
		print join("\t", $self->_module_info($module)), "\n";
	}
	print $divider;
	print `embossversion -full`;
	print $divider;
	print join("\t", "perl", sprintf('%vd', $^V), $^X), "\n";
	foreach my $module ( qw( Carp CGI File::Basename IPC::Open3 Storable
		Mail::Send Parse::RecDescent Text::Abbrev ) ) {
		print join("\t", $self->_module_info($module)), "\n";
	}
	print $divider;
	print $ENV{HTTP_USER_AGENT}, "\n";
}

=item menu_page()

Generates the main menu page.

=cut

sub menu_page {
	my ($self) = @_;

	my $menu;
	my $sort = $self->{cgi}->param('sort') || "";
	if ($sort eq 'alpha') {
		$menu = $self->{html}->menu_page($self->apps);
	} else {
		$menu = $self->{html}->menu_page($self->groups);
	}
	print $self->_header, $menu;
}

=item app_page()

Generates the application-specific input page or runs an EMBOSS application
and generates the output page.

=cut

sub app_page {
	my ($self, $app) = @_;
	
	my $acd = eval { EMBOSS::ACD->new($self->_find_acd($app)) }
		or $self->_fatal_error("$app is not a valid EMBOSS application");
	$self->is_excluded($app)
		and $self->_fatal_error("$app has been excluded from public access");

	if ($self->{cgi}->param('_run')) {
		$self->_run_application($acd);
	} else {
		# make application-specific changes to pretty things up...
		#
		if ($app eq 'showseq') {
			$acd->param('things')->{information} .=
				" (only used if you chose to enter your own list above)";
		} elsif ($app eq 'digest') {
			$acd->param('menu')->{information} =
				$acd->param('menu')->{header};
		} elsif ($app eq 'pasteseq') {
			$acd->param('pos')->{expected} = "at the end of the sequence";
		} elsif ($app eq 'lindna' or $app eq 'cirdna') {
			$acd->param('intercolour')->{datatype} = 'selection';
			$acd->param('intercolour')->{values} = 'Black;Red;Yellow;Green;Aquamarine;Pink;Wheat;Grey;Brown;Blue;Blueviolet;Cyan;Turquoise;Magenta;Salmon;White';
			$acd->param('intercolour')->{information} =~
				s/\(enter a colour number\)$//;
		} elsif ($app eq 'showdb' or $app eq 'infoalign') {
			$acd->param('only')->{_ignore} = 1;
		}
		
		print $self->_header, $self->{html}->input_page($acd,
			$self->{preferences}->{hide_optional});
	}
}

=item help_page()

Generates the application-specific manual page.

=cut

sub help_page {
	my ($self, $app) = @_;

	if (defined $app) {
#		my $manual = $self->_find_manual($app)
#			or $self->_fatal_error(
#				"This release of EMBOSS is missing the $app user manual."
#			);
		open TFM, '-|', 'tfm', '-auto', '-html', $app
			or $self->_fatal_error("Error reading $app user manual from tfm.");
		my $manual = join '', <TFM>;
		close TFM;
		print $self->_header, $self->{html}->manual_page($app, $manual);
	} else {
		print $self->_header, $self->{html}->help_page;
	}
}

=item output_page()

Generates the application output page, or a placeholder page if the application is still running.

=cut

sub output_page {
	my ($self, $id) = @_;

	my $temp = sprintf '%s/%06d',
			$self->{conf}->{OUTPUT_PATH}, $id;
	my $index = "$temp/index.html";
	if (-s $index) {
		my $url = $self->{conf}->{OUTPUT_URL} =~ /^http:/ ?
			sprintf "%s/%s", $self->{conf}->{OUTPUT_URL}, basename $temp :
			sprintf "http://%s%s/%s",
				$ENV{HTTP_HOST}, $self->{conf}->{OUTPUT_URL}, basename $temp;
		print $self->{cgi}->redirect($url);
	} else {
		print $self->_header,
			$self->{html}->default_output_page($self->{conf}->{REFRESH_DELAY});
	}
}

=item default_page()

Generates a default page according to the current configuration.

=cut

sub default_page {
	my ($self) = @_;

	$self->{conf}->{FRAMES} ? &frameset_page : &intro_page;
}

=item frameset_page()

Generates a page that sets up the menu and main content frames.

=cut

sub frameset_page {
	my ($self) = @_;

	print $self->_header, $self->{html}->frameset_page;
}

=item apps()

Returns a list of available EMBOSS applications.  Each element of the list is
a reference to an array containing the name and description of an application.

=cut

sub apps {
	my ($self) = @_;

	return $self->{conf}->apps();
}

=item groups()

Returns a list of application groups.  Each element of the list is a reference
to an array containing the name of the group and a list of applications
belonging to that group (each application is in turn a reference to an array
as described in apps() above.)  Note that an individual application can appear
in multiple groups.

=cut

sub groups {
	my ($self) = @_;

	return $self->{conf}->groups();
}

=item is_excluded($subject)

Returns true if the subject is being excluded from public display, false
otherwise.

$subject is the name of an application or application group as it appears in
the output from wossname.

=cut

sub is_excluded {
	my ($self, $subject) = @_;

	return $self->{conf}->is_excluded($subject);
}

=item databases()

Returns a list of available databases.  Each element of the list is the name
of a database, suitable for use in a USA.

=cut

#sub databases {
#	my ($self) = @_;
#
#	return $self->{conf}->databases();
#}
#
#=item matrices()
#
#Returns a list of available alignment scoring matrices.  Each element of the
#list is a reference to an array containing the filename of the scoring matrix,
#suitable for use as the value of a matrix or matrixf argument, and a
#description of the matrix.
#
#=cut
#
#sub matrices {
#	my ($self) = @_;
#
#	return $self->{conf}->matrices();
#}
#
#=item codon_usage_tables()
#
#Returns a list of available codon usage tables.  Each element of the list is a
#reference to an array containing the filename of the codon usage table,
#suitable for use as the value of a codon argument, and the name of the species
#from which it is derived.
#
#=cut
#
#sub codon_usage_tables {
#	my ($self) = @_;
#
#	return $self->{conf}->codon_usage_tables();
#}

# # # # # # # # # # # # # # # PRIVATE METHODS # # # # # # # # # # # # # # #

sub _header {
	my ($self, @args) = @_;

	$self->{header_sent} = 1;
	$self->{preferences} = eval {
		Storable::thaw($self->{cgi}->cookie('preferences'));
	};
	foreach my $param ($self->{cgi}->param) {
		if ($param =~ /_pref_(.*)/) {
			$self->{preferences}->{$1} = $self->{cgi}->param($param);
		}
	}
	if (defined $self->{preferences}) {
		eval {
			my $cookie = $self->{cgi}->cookie(
				-name => 'preferences',
				-value => Storable::freeze($self->{preferences})
			);
			push @args, ( -cookie => $cookie );
		};
		warn "exception in Storable::freeze: $@" if $@;
	}
	return $self->{cgi}->header(@args);
}

sub _fatal_error {
	my ($self, @error) = @_;
	
	if (my $file = defined $self->{fatals_to}) {
		open FILE, '>', $self->{fatals_to}
			or warn "failed to write error to $self->{fatals_to}: $!"
			and die @error;
		print FILE $self->{html}->error_page(@error);
		close FILE;
	} else {
		print $self->_header
			unless $self->{header_sent};
		print $self->{html}->error_page(@error);
	}
	die @error;
}

sub _find_acd {
	my ($self, $app) = @_;

	my $acdfile = "$self->{conf}->{EMBOSS_ACDROOT}/$app.acd";
	return -r $acdfile ? $acdfile : undef;
}

sub _find_manual {
	my ($self, $app) = @_;

	my $manual = "$self->{conf}->{EMBOSS_MANUAL}/$app.html";
	return -r $manual ? $manual : undef;
}

sub _run_application {
	my ($self, $acd) = @_;

	# make sure the user is running a valid EMBOSS application that hasn't
	# been excluded from public access...
	#
	my $app = $acd->name;

	# create the working directory; this is necessary because EMBOSS doesn't
	# allow the user to specify a name for some output files and we don't want
	# to overwrite those of other users...
	#
	my $temp;
	do {
		# this will loop forever if the output directory isn't writeable, but
		# that condition is enforced in EMBOSS::GUI::Conf...
		#
		$temp = sprintf '%s/%06d',
			$self->{conf}->{OUTPUT_PATH}, int rand 1000000;
	} until mkdir $temp, 0755;
	chdir $temp
		or $self->_fatal_error("failed to change directory to $temp: $!");
	my $index = "$temp/index.html";
	
	# redirect to the script with arguments that will cause it to loop until
	# the output is in place...
	#
	my $url = sprintf "http://%s%s/output/%s",
		$ENV{HTTP_HOST}, $ENV{SCRIPT_NAME},	basename $temp;
	print $self->{cgi}->redirect($url);

	# this way doesn't work because of caching issues with Internet
	# Explorer...
	## dump a placeholder index file that will refresh every few seconds until
	## the application has finished (and replaced it with the actual output...)
	## and redirect the user there...
	##
	#my $url = sprintf 'http://%s%s/%s',
	#	$ENV{HTTP_HOST}, $self->{conf}->{OUTPUT_URL}, basename $temp;
	#my $content = 
	#	$self->{html}->default_output_page($self->{conf}->{REFRESH_DELAY});
	#$self->_write_to_file($index, $content)
	#	or $self->_fatal_error("failed to create default index file: $!");
	#print $self->{cgi}->redirect($url);

	# fork a child process to run the actual job, then exit from the parent
	# to sever the connection with the web server...
	#
	my $pid = fork;
	if (not defined $pid) {
		$self->_fatal_error("failed to fork");
	} elsif ($pid) {
		exit;
	} else {
		# close default file handles and redirect fatal errors to the index
		# file from now on (so the user will still get them...)
		#
		close STDIN;
		close STDOUT;
		close STDERR;
		close Parse::RecDescent::ERROR;
		close Parse::RecDescent::TRACE;
		close Parse::RecDescent::TRACECONTEXT;
		$self->{fatals_to} = $index;
			# main action occurs below...
	}

	# construct the command line...
	#
	my @args = ($app, '-auto');
	foreach my $param ($self->{cgi}->param) {
		next if $param =~ /^_/;	# ignore our internal parameters...
		my @values = $self->{cgi}->param($param);
		my $subtype = "";;
		if ($param =~ /(.*)\.(.*)/) {	# parameters with multiple fields...
			$param = $1;
			$subtype = $2;
		}
		my $param_info = $acd->param($param);
		$param_info = { datatype => 'qualifier' }
			if $self->_is_qualifier($param);	# allow qualifiers...
		next unless $param_info;	# ignore unknown parameters...
		if (@values > 1) {
			push @args, "-$param", join(",", @values);
		} else {
			my $value = defined $values[0] ? $values[0] : "";
			if ($subtype eq 'text' && length $value) {
				my $file = "$temp/.$param";
				$self->_write_to_file($file, $value)
					or $self->_fatal_error("failed to create $file: $!");
				push @args, "-$param", $file;
			} elsif (ref $value eq 'Fh') {
				my $file = "$temp/.$param";
				$self->_write_to_file($file, join('', <$value>))
					or $self->_fatal_error("failed to create $file: $!");
				push @args, "-$param", $file;
			} elsif ($param_info->{datatype} eq 'boolean' or
				$param_info->{datatype} eq 'toggle') {
				push @args, $value ? "-$param" : "-no$param";
			} else {
				push @args, "-$param", $value
					if length $value;
			}
		}
		# TODO...
	}

	# grab the email address...
	#
	my $email = $self->{cgi}->param('_email');

	# echo process id, remote address, email address if defined, and command
	# line to disk for provenance...
	#
	my @info = ($$, "@args", $ENV{'REMOTE_ADDR'}, $email);
	my $info = "$temp/.info";
	$self->_write_to_file($info, join("\n", @info))
		or $self->_fatal_error("failed to create $info: $!");

	### JR ### START

	# create job script
	open JOB_SH, '>', "$temp/job.sh"
		or $self->_fatal_error("failed to create $temp/job.sh: $!");
	print JOB_SH << EOF_JOB_SH_HEADER;
#!/bin/sh
# job.* have been generated and submitted by the grid-runner script
tar -zxvf job.tgz               # extract contents of job with appropriate perms
rm -f job.tgz                   # and remove job input package
rm -f job.sh job.jdl            # and ourselves to clean up output

# set up the environment to use installed package
export EMBOSS_ACDROOT=\$VO_BIOMED_SW_DIR/GrEMBOSS-4.0/share/EMBOSS/acd
export PLPLOT_LIB=\$VO_BIOMED_SW_DIR/GrEMBOSS-4.0/share/EMBOSS
export LD_LIBRARY_PATH=/lib:/usr/lib:\$VO_BIOMED_SW_DIR/GrEMBOSS-4.0/lib:\$LD_LIBRARY_PATH
export PATH=\$VO_BIOMED_SW_DIR/GrEMBOSS-4.0/bin:\$PATH

# do the work
EOF_JOB_SH_HEADER
	print JOB_SH, @args;
	print JOB_SH << EOF_JOB_SH_FOOTER;
# pack all directory contents
#	output.tgz is expected by the grid-runner script
tar -zcvf output.tgz *
EOF_JOB_SH_FOOTER

	# make a link of master job.jdl here
	#
	#	I don't know if there is a better way specify this
	link "self->{conf}->{HTML_PATH}/job.jdl", "$temp/job.jdl"
		or  $self->_fatal_error("failed to create $temp/job.jdl: $!");;

	# and now run the job on the grid using 'egee.sh'
	#	egee.sh needs to be run from the job directory ($temp, we should already
	#		be there)
	#	egee.sh will create the job.tgz file with the contents of this directory
	#		submit the job and wait for it to finish. Once done, it will
	#		retrieve results and place its stdout and stderr in the 
	#		appropriate files.
	open NULL, '<', "/dev/null";
	open GRID_OUT, '>', "$temp/.grid_output"
		or  $self->_fatal_error("failed to create $temp/.grid_output: $!");
	open GRID_ERR, '>', "$temp/.grid_error"
		or  $self->_fatal_error("failed to create $temp/.grid_error: $!");
	my $cpid = open3("<&NULL", ">&OUTPUT", ">&ERROR", 
			"sh $self->{conf}->{HTML_PATH}/egee.sh");
	waitpid $cpid, 0;
	close NULL;
	close GRID_ERR;
	close GRID_OUT;

#	# run the command, capturing stdout and stderr...
#	#
#	open NULL, '<', "/dev/null";
#	open OUTPUT, '>', "$temp/.stdout"
#		or $self->_fatal_error("failed to create $temp/.stdout: $!");
#	open ERROR, '>', "$temp/.stderr"
#		or $self->_fatal_error("failed to create $temp/.stderr: $!");
#	my $cpid = open3("<&NULL", ">&OUTPUT", ">&ERROR", @args);
#	waitpid $cpid, 0;
#	close NULL;	# in order to avoid "used only once" warning...
#	close ERROR;	# in order to avoid "used only once" warning...
#	close OUTPUT;	# in order to avoid "used only once" warning...

	### JR ### END

	## construct the output page in a separate file, then move it over the
	## placeholder index file; this should prevent the webserver from trying
	## to load an incomplete page...
	##
	#my $buffer = "$temp/.buffer";
	#$self->_write_to_file($buffer, $self->{html}->output_page($temp))
	#	or $self->_fatal_error("failed to create $buffer: $!");
	#rename $buffer, $index
	#	or $self->_fatal_error("failed to overwrite $index: $!");

	# write the index file in place...
	#
	$self->_write_to_file($index, $self->{html}->output_page($temp))
		or $self->_fatal_error("failed to create $index: $!");
	
	# if an email address was specified, send a message indicating the output
	# is ready...
	#
	if (length $email) {
		my $started = localtime( (stat($info))[9] );
		my $msg = Mail::Send->new(
			To => "EMBOSS user <$email>",
			Subject => "EMBOSS: $app has finished"
		);
		my $fh = $msg->open;
		print $fh <<EOF;
The job you submitted to $app on $started has finished.

You can view the output at $url
EOF
		$fh->close;
	}
	
	# never return from this method...
	#
	exit;
}

sub _is_qualifier {
	my ($self, $param) = @_;

	return $param =~ /^g(sub|x|y)?title$/ ||
		$param =~ /^(a|o|of|r)format$/;
}

sub _write_to_file {
	my ($self, $file, $content) = @_;

	open FILE, '>', $file
		or return undef;
	print FILE $content;
	close FILE;
}

sub _module_info {
	my ($self, $module) = @_;

	my $module_inc = "$module.pm";
	$module_inc =~ s/::/\//g;
	my $module_path = $INC{$module_inc};
	my $module_version = "";
	$module_version ||= eval "\$$module\::VERSION";

	return ($module, $module_version, $module_path);
}

1;

=back

=head1 BUGS

None that I know of...

=head1 COPYRIGHT

Copyright (c) 2004 Luke McCarthy.  All rights reserved.  This program is free
software.  You may copy or redistribute it under the same terms as Perl itself.
