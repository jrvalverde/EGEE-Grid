=head1 NAME

EMBOSS::ACD - parse EMBOSS ACD (AJAX Command Definition) files

=head1 AUTHOR

Luke McCarthy <lukem@gene.pbi.nrc.ca>

=head1 SYNOPSIS

  use EMBOSS::ACD;
  
  $acd = EMBOSS::ACD->new($acdfile);
  
  $application = $acd->name;
  
  $description = $acd->documentation;

  @groups = $acd->groups;
  
  foreach $parameter ($acd->param) {
  	while (($attribute, $value) = each %$parameter) {
  		...
  	}
  }

=head1 DESCRIPTION

EMBOSS::ACD parses EMBOSS Ajax Command Definition files and provides
object-oriented access to the data contained therein.

For a complete specification of the ACD format, see
http://emboss.sourceforge.net/developers/acd

Note that no checks are performed to ensure that the ACD file is semantically
valid.  Specifically, datatypes and attributes that aren't defined in the
specification can occur in the file and will be parsed as normal.  This is a
good thing, as the module remains useful even if new datatypes are added by
local developers or the EMBOSS crew.

Public methods are described below:

=over 4

=cut

package EMBOSS::ACD;

use strict;
use warnings;

use Carp;
use Parse::RecDescent;
use Text::Abbrev;

our $VERSION = 2.00;

our @DATATYPES = qw(
	array boolean float integer range string toggle
	codon cpdb datafile directory dirlist discretestates distances features
		filelist frequencies infile matrix matrixf properties regexp scop
		sequence seqall seqset seqsetall tree
	list selection
	align featout outfile report seqout seqoutall seqoutset outcodon
	graph xygraph
	variable
);
our $DATATYPE_ABBREV = abbrev @DATATYPES;

our $GRAMMAR = q(
	# terminal definitions...
	#
	colonequals : "=" | ":"

	token : /".*?"/s {
		$item[1] =~ s/\n\s+/ /g;
			# an undocumented feature of the ACD specification is that leading
			# whitespace is collapsed and newlines are discounted inside quoted
			# strings, which are supposed to be treated as single tokens; this
			# rule duplicates that behaviour...
		$item[1] =~ s/^"//;
		$item[1] =~ s/"$//;
		$item[1]
	}
	token : /\S+/ {
		$item[1]
	}

	tokencolonequals : /".*?"[:=]/s {
		$item[1] =~ s/[:=]$//;
		$item[1] =~ s/\n\s+/ /g;
			# as above...
		$item[1] =~ s/^"//;
		$item[1] =~ s/"$//;
		$item[1]
	}
	tokencolonequals : /\S+[:=]/ {
		$item[1] =~ s/[:=]$//;
		$item[1]
	}

	eof : /^\Z/

	# terminal aliases, to make the hash keys below more readable (this also
	# lets us check both token and tokencolonequals at once...)
	#
	key : tokencolonequals {
		$item{tokencolonequals}
	}
	key : token colonequals {
		$item{token}
	}
	value : token {
		$item{token}
	}
	
	# attribute definitions...
	#
	attribute_block : "[" attribute(s?) "]" {
		[ map @$_, @{$item{'attribute(s?)'}} ]
	}
	attribute : key value {
		[ $item{key} => $item{value} ]
	}
	
	# parameter definition with attributes...
	#
	parameter_block : key value attribute_block {
		my $datatype = $EMBOSS::ACD::DATATYPE_ABBREV{$item{key}} || $item{key};
		{ name => $item{value}, datatype => $datatype,
			@{$item{attribute_block}} }
	}

	# an undocumented parameter definition form used in emma.acd...
	#
	parameter_block : /variable[:=]/ token token {
		{
			name => $item[2],
			datatype => "variable",
			value => $item[3]
		}
	}
	parameter_block : "variable" colonequals token token {
		{
			name => $item[3],
			datatype => "variable",
			value => $item[4]
		}
	}
	
	# parameter definition without attributes...
	#
	parameter_block : key value {
		my $datatype = $EMBOSS::ACD::DATATYPE_ABBREV{$item{key}} || $item{key};
		{ name => $item{value}, datatype => $datatype }
	}
	
	# application definition with attributes (requires at least 'groups' and
	# 'documentation', neither of which can be abbreviated...)
	#
	application_block : /application[:=]/ token attribute_block {
		{ name => $item{token},	@{$item{attribute_block}} }
	}
	application_block :	"application" colonequals token	attribute_block {
		{ name => $item{token},	@{$item{attribute_block}} }
	}

	# the definition of an ACD file (note that we're skipping comments that
	# start anywhere on a line...)
	#
	acd : <skip:'(\s|#.*\n)*'> application_block parameter_block(s?) eof {
		$item{application_block}->{param} = $item{'parameter_block(s?)'};
		$item{application_block};
	}
);

=item new($acdfile)

Parses the specified ACD file.  Returns a new EMBOSS::ACD object on success,
and dies on failure.

$acdfile is the full path to a valid ACD file.

=cut

sub new {
	my $invocant = shift;
	my $class = ref $invocant || $invocant;
	my $self = {
	};
	bless $self, $class;
	
	# slurp up the supplied ACD file...
	#
	$self->{path} = shift
		or croak "no ACD file specified in call to EMBOSS::ACD::new";
	open ACD, '<', $self->{path}
		or croak "error reading ACD file '$self->{path}': $!";
	my @lines;
	while (<ACD>) {
		s/\\$//;	# strip line continuation characters...
		push @lines, $_;
	}
	$self->{acd} = join '', @lines;
	close ACD;

	# parse the ACD file using the grammar above; note that the parse tree we
	# generate can be passed to XML::Simple for output...
	#
	my $parser = Parse::RecDescent->new($GRAMMAR);
	$self->{tree} = $parser->acd($self->{acd})
		or croak "$self->{path} is not a valid ACD file according to \$EMBOSS::ACD::GRAMMAR";

	# TODO deal with sections...

	# store a hash mapping parameter names to the parameter hash in the
	# parse tree...
	#
	$self->{param} = {};
	foreach my $param ($self->param) {
		$self->{param}->{$param->{name}} = $param;
	}

	return $self;
}

=item name()

Returns the name of the application whose ACD file was parsed.

=cut

sub name {
	my ($self) = @_;

	return $self->{tree}->{name} || "";
}

=item documentation()

Returns a short description of the application whose ACD file was parsed.

=cut

sub documentation {
	my ($self) = @_;

	return $self->{tree}->{documentation} || "";
}

=item groups()

Returns a list of functional groups to which the application belongs.  For a
list of possible groups, see
http://emboss.sourceforge.net/developers/acd/syntax.html#sect2214

=cut

sub groups {
	my ($self) = @_;
	
	return split /[,;]\s*/, $self->{tree}->{groups} || "";
}

=item param($param)

Returns a reference to a hash describing the specified parameter.  The hash
contains key-value pairs corresponding to the attributes specified in the ACD
file, plus the keys 'name' and 'datatype'.  The value of the 'datatype' key is
the canonical name of the data type, regardless of any abbreviation in the ACD
file.  For a list of possible data types, see
http://emboss.sourceforge.net/developers/acd/syntax.html#sect23

If no parameter is specified, a list of all parameters is returned.  The
members of the list are hash references as described above.

Note that, in accordance with the ACD specification, attribute names are not
expanded if they are abbreviated in the ACD file.

$param is either undefined (see above) or the name of the desired parameter.

=cut

sub param {
	my ($self, $name) = @_;

	# if called with an argument, return the parameter by that name,
	# otherwise return a list of all parameters...
	#
	if (defined $name) {
		return $self->{param}->{$name};
	} else {
		my $aref = $self->{tree}->{param};
		return defined $aref ? @$aref : ();
	}
}

=item canonical_datatype($datatype)

Returns the canonical name of the specified abbreviated datatype, or undefined
if the abbreviation is ambiguous or not recognized.

=cut

sub canonical_datatype {
	my ($self, $datatype) = @_;

	# returns undef if $datatype isn't a valid abbreviation...
	#
	return $DATATYPE_ABBREV->{$datatype};
}

1;

=back

=head1 BUGS

None that I know of...

=head1 COPYRIGHT

Copyright (c) 2004 Luke McCarthy.  All rights reserved.  This program is free
software.  You may copy or redistribute it under the same terms as Perl itself.
