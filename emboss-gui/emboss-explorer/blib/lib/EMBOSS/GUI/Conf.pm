=head1 NAME

EMBOSS::GUI::Conf - repository for EMBOSS::GUI site-specific configuration

=head1 AUTHOR

Luke McCarthy <lukem@gene.pbi.nrc.ca>

=head1 SYNOPSIS

  use EMBOSS::GUI::Conf;
  
  $conf = EMBOSS::GUI::Conf->new();

  foreach $app ($conf->apps) {
  	($name, $doc) = @$app;
	if (!$conf->is_excluded($name)) {
  		...
  	}
  }

  foreach $group ($conf->groups) {
  	$group_name = shift @$group;
  	if (!conf->is_excluded($group_name) {
  		foreach $app (@$group) {
  			($name, $doc) = @$app;
  			...
  		}
  	}
  }

=head1 DESCRIPTION

EMBOSS::GUI::Conf contains site-specific configuration information for
EMBOSS::GUI.  Consult the source for a description of the variables that can
be set.

Public methods are described below:

=over 4

=cut

package EMBOSS::GUI::Conf;

use strict;
use warnings;

use Carp;

our $VERSION = 1.10;

# path to the EMBOSS::GUI HTML files
our $HTML_PATH = "/var/www/emboss/html";

# URL corresponding to $HTML_PATH above
our $HTML_URL = "/emboss";

# URL specifying the style sheet to use
our $STYLE_URL = "$HTML_URL/style/emboss.css";

# URL prefix to place before image links
our $IMAGE_URL = "$HTML_URL/images";

# URL prefix to place before manual links (only used in static pages)
our $MANUAL_URL = "$HTML_URL/manual";

# path to the EMBOSS::GUI temporary output directory
our $OUTPUT_PATH = "$HTML_PATH/output";

# URL corresponding to $OUTPUT_PATH above
our $OUTPUT_URL = "$HTML_URL/output";

# prefix under which EMBOSS was installed
our $EMBOSS_PREFIX = "/usr/local";

# path to EMBOSS binaries
our $EMBOSS_BIN = "$EMBOSS_PREFIX/bin";

# path to EMBOSS installation
our $EMBOSS_HOME = "$EMBOSS_PREFIX/share/EMBOSS";

# path to EMBOSS ACD files
our $EMBOSS_ACDROOT = "$EMBOSS_HOME/acd";

# path to EMBOSS data
our $EMBOSS_DATA = "$EMBOSS_HOME/data";

# path to EMBOSS application manuals
our $EMBOSS_MANUAL = "$EMBOSS_HOME/doc/html";

# list of groups and applications to exclude from the main menu
our @EXCLUDED = (
	"ACD",
	"acdc",
	"acdpretty",
	"acdtable",
	"acdtrace",
	"acdvalid",
	"UTILS DATABASE CREATION",
	"aaindexextract",
	"cutgextract",
	"printsextract",
	"prosextract",
	"rebaseextract",
	"tfextract",
	"UTILS DATABASE INDEXING",
	"dbiblast",
	"dbifasta",
	"dbiflat",
	"dbigcg",
);

# number of seconds to delay between placeholder page refreshes
our $REFRESH_DELAY = 1;

# whether or not to display using frames
our $FRAMES = 1;

=item new()

Returns a new EMBOSS::GUI::Conf object.  This method stores the
EMBOSS::GUI::Conf package variables in the object hash, ensures that the
specified output path is writeable and adds the EMBOSS binaries to the path.

=cut

sub new {
	my $invocant = shift;
	my $class = ref $invocant || $invocant;
	my $self = {
		HTML_URL => $HTML_URL,
		STYLE_URL => $STYLE_URL,
		IMAGE_URL => $IMAGE_URL,
		MANUAL_URL => $MANUAL_URL,
		OUTPUT_PATH => $OUTPUT_PATH,
		OUTPUT_URL => $OUTPUT_URL,
		EMBOSS_ACDROOT => $ENV{EMBOSS_ACDROOT} || $EMBOSS_ACDROOT,
		EMBOSS_DATA => $EMBOSS_DATA,
		EMBOSS_MANUAL => $EMBOSS_MANUAL,
		EXCLUDED => \@EXCLUDED,
		REFRESH_DELAY => $REFRESH_DELAY,
		FRAMES => $FRAMES
	};
	bless $self, $class;

	# check to make sure the output directory is writeable...
	#
	-d $OUTPUT_PATH && -w $OUTPUT_PATH
		or die "output directory $OUTPUT_PATH is not writeable";

	# add the EMBOSS binary directory to the path...
	#
	$ENV{PATH} = "$EMBOSS_BIN:$ENV{PATH}";

	$self->{excluded} = {};
	foreach my $item (@{$self->{EXCLUDED}}) {
		++$self->{excluded}->{$item};
	}

	return $self;
}

=item apps()

Returns a list of available EMBOSS applications.  Each element of the list is
a reference to an array containing the name and description of an application.

=cut

sub apps {
	my ($self) = @_;

	$self->_cache_appinfo unless $self->{apps};
	return @{$self->{apps}};
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

	$self->_cache_appinfo unless $self->{groups};
	return @{$self->{groups}};
}

=item is_excluded($subject)

Returns true if the subject is being excluded from public display, false
otherwise.

$subject is the name of an application or application group as it appears in
the output from wossname.

=cut

sub is_excluded {
	my ($self, $subject) = @_;

	return $self->{excluded}->{$subject};
}

=item databases()

Returns a list of available databases.  Each element of the list is the name
of a database, suitable for use in a USA.

=cut

sub databases {
	my ($self) = @_;

	$self->_cache_databases unless $self->{databases};
	return @{$self->{databases}};
}

=item matrices()

Returns a list of available alignment scoring matrices.  Each element of the
list is a reference to an array containing the filename of the scoring matrix,
suitable for use as the value of a matrix or matrixf argument, and a
description of the matrix.

=cut

sub matrices {
	my ($self) = @_;

	$self->_cache_matrices unless $self->{matrices};
	return @{$self->{matrices}};
}

=item codon_usage_tables()

Returns a list of available codon usage tables.  Each element of the list is a
reference to an array containing the filename of the codon usage table,
suitable for use as the value of a codon argument, and the name of the species
from which it is derived.

=cut

sub codon_usage_tables {
	my ($self) = @_;

	$self->_cache_codon_usage_tables unless $self->{codon_usage_tables};
	return @{$self->{codon_usage_tables}};
}

# # # # # # # # # # # # # # # PRIVATE METHODS # # # # # # # # # # # # # # #

sub _cache_appinfo {
	my ($self) = @_;

	# run wossname to get a list of application groups and the applications
	# therein...
	#
	my (@groups, @current_group);
	open WOSSNAME, '-|', 'wossname', '-auto', '-gui'
	# comment the line above and uncomment the line below to cope with a bug
	# in perl 5.6...
	#open WOSSNAME, 'wossname -auto -gui |'
		or $self->_fatal_error("couldn't run wossname: $!");
	while (<WOSSNAME>) {
		chomp;
		if (/^$/) {	# blank lines separate groups...
			# if the current group is not empty and not in the exclude list,
			# add it to the list of avaialable groups...
			#
			push @groups, [ @current_group ]
				if @current_group && !$self->is_excluded($current_group[0]);
			@current_group = ();
		} elsif (@current_group) {	# we've already read the group name...
			my ($app, $doc) = split /\s+/, $_, 2;
			push @current_group, [ $app, $doc ];
		} else {	# read the group name...
			push @current_group, $_;
		}
	}
	close WOSSNAME;
	$self->{groups} = \@groups;

	# step through the list of groups and create a master list of applications
	# in alphabetical order...
	#
	my %apps;
	foreach my $group (@groups) {
		my $group_name = shift @$group;
		foreach my $aref (@$group) {
			my ($app, $doc) = @$aref;
			$apps{$app} = $aref
				unless $self->is_excluded($app);
		}
		unshift @$group, $group_name;	# stick the group back on...
	}
	$self->{apps} = [ sort {$a->[0] cmp $b->[0]} values %apps ];
}

sub _cache_databases {
	my ($self) = @_;

	# run showb to get a list of databases...
	#
	my @databases;
	open SHOWDB, '-|', 'showdb', '-auto', '-noheading'
	# comment the line above and uncomment the line below to cope with a bug
	# in perl 5.6...
	#open SHOWDB, 'showdb -auto -noheading |'
		or $self->_fatal_error("couldn't run showdb: $!");
	while (<SHOWDB>) {
		my ($name, $type, $id, $query, $all, $comment) = split;
		push @databases, $name;
	}
	$self->{databases} = \@databases;
}

sub _cache_matrices {
	my ($self) = @_;

	my @matrices;
	foreach my $file (glob "$self->{conf}->{EMBOSS_DATA}/Matrices.*") {
		open INDEX, '<', $file
			or $self->_fatal_error("couldn't open matrix index '$file': $!");
		while (<INDEX>) {
			chomp;
			next if /^#/;	# skip comments...
			next unless /\S/;	# skip blank lines...
			my ($filename, $description) = split /\s+/, $_, 2;
			push @matrices, [ $filename => $description ];
		}
	}
	$self->{matrices} = \@matrices;
}

sub _cache_codon_usage_tables {
	my ($self) = @_;

	my @codon_usage_tables;
	open INDEX, '<', "$self->{conf}->{EMBOSS_DATA}/CODONS/Cut.index"
		or $self->_fatal_error("couldn't open codon usage table index: $!");
	while (<INDEX>) {
		chomp;
		next if /^#/;	# skip comments...
		next unless /\S/;	# skip blank lines...
		#my ($filename, $description) = split /\s+/, $_, 2;
		my ($filename, $species) = split;
		push @codon_usage_tables, [ $filename => $species ];
	}
	$self->{codon_usage_tables} = \@codon_usage_tables;
}



1;

=back

=head1 BUGS

None that I know of.

=head1 COPYRIGHT

Copyright (c) 2004 Luke McCarthy.  All rights reserved.  This program is free
software.  You may copy or redistribute it under the same terms as Perl itself.
