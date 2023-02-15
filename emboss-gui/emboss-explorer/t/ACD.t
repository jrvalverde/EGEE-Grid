#!/usr/bin/perl

use strict;
use warnings;

use Test::More;

use EMBOSS::ACD;
use EMBOSS::GUI::Conf;
use EMBOSS::GUI::XHTML;

our @ACDFILES;
our $XHTML;

BEGIN {
	# locate the EMBOSS ACD files in order to validate against them...
	#
	@ACDFILES = glob "/usr/local/share/EMBOSS/acd/*.acd";

	$XHTML = EMBOSS::GUI::XHTML->new(
		script_url => 'dummy'
	);
	
	plan tests => 2 * scalar(@ACDFILES);
}

# test against each ACD file...
#
for (my $i=0; $i<@ACDFILES; ++$i) {
	my $acd;
	ok(eval { $acd = EMBOSS::ACD->new($ACDFILES[$i]) }, "parse $ACDFILES[$i]");
	ok(eval { test_input_page($acd) }, "generate input page $ACDFILES[$i]");
}

sub test_input_page {
	my ($acd) = shift;

	my $html = $XHTML->input_page($acd);
	return $html =~ /unknown datatype/ ? undef : "ok";
}
