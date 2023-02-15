=head1 NAME

EMBOSS::GUI::XHTML - generate HTML for EMBOSS::GUI

=head1 AUTHOR

Luke McCarthy <lukem@gene.pbi.nrc.ca>

=head1 SYNOPSIS

Not for public consumption.  Use EMBOSS::GUI instead.

=head1 DESCRIPTION

EMBOSS::GUI::XHTML generates the HTML required by EMBOSS::GUI.  The appearance
of EMBOSS::GUI can be customized by editing or replacing the default style
sheet.  There is very little that cannot be accomplished in this way.  If new
HTML is absolutely required, simply create a new module that provides
the methods described below and specify the new module in the EMBOSS::GUI
constructor.

Public methods are described below:

=over 4

=cut

package EMBOSS::GUI::XHTML;

use strict;
use warnings;

use Carp;
use CGI;
use File::Basename;

our $VERSION = 1.10;

=item new(%args)

Returns a new EMBOSS::GUI::XHTML object.

%args is a hash of optional named arguments.  The following arguments are
%recognized:

=over 4

=item static => $boolean

If $boolean is true, the generated HTML will assume that, where possible, the
pages of the interface have been generated statically and will be linked 
appropriately.  See also the mkstatic script in the EMBOSS::GUI distribution.

=item frames => $boolean

If $boolean is true, the generated HTML will assume that the main menu is in
its own frame and doesn't have to be added to each page.

=item script_url => $url

Specifies the URL of the main EMBOSS::GUI CGI script.  This parameter is
required if static pages are generated, otherwise the URL will be determined
from the CGI environment.

=item style_url => $url

Specifies the URL of the style sheet to use.

=item image_url => $url

Specifies a URL prefix to place before image links.

=item manual_url => $url

Specifies a URL prefix to place before manual links.  This prefix is only used
if static pages are generated.

=back

=cut

sub new {
	my $invocant = shift;
	my $class = ref $invocant || $invocant;
	my %args = @_;
	my $self = {
	};
	bless $self, $class;

	# if the HTML pages are to be dumped en masse and used statically, the
	# user must define a URL for the controlling CGI script; if the HTML pages
	# are to be generated dynamically, the user _may_ define a URL for the
	# controlling CGI script, or we will try to determine it from the
	# environment...
	#
	if ($args{static}) {
		$self->{static} = 1;
		$self->{script_url} = $args{script_url}
			or croak "created in static mode with no script URL defined";
	} else {
		$self->{script_url} = $args{script_url} || $ENV{SCRIPT_NAME}
			or die "failed to determine script URL";
	}

	# normalize other arguments...
	#
	$self->{frames} = defined $args{frames} ? $args{frames} : 1;
	$self->{style_url} = $args{style_url} || "";
	$self->{image_url} = $args{image_url} || "";
	$self->{manual_url} = $args{manual_url} || "";

	return $self;
}

=item intro_page()

Generates an introductory page describing EMBOSS and the GUI.

=cut

sub intro_page {
	my ($self) = @_;

	return $self->_header_html("EMBOSS") .
		$self->_banner_html .
		$self->_intro_html .
		$self->_footer_html;
}

=item menu_page(@entries)

Generates the main menu page.

@entries is either a list of applications as returned by EMBOSS::GUI::apps(),
or a list of groups as returned by EMBOSS::GUI::groups().

=cut

sub menu_page {
	my ($self, @entries) = @_;

	return $self->_header_html("EMBOSS: application menu") .
		$self->_menu_html(@entries) .
		$self->_footer_html;
}

=item input_page($acd, $hide_optional)

Generates the application-specific input page.

$acd is an EMBOSS::ACD object that describes the application.

$hide_optional is a boolean value that determines whether optional parameters
(also called additional parameters in the EMBOSS documenation) will appear in
the input page.

=cut

sub input_page {
	my ($self, $acd, $hide_optional) = @_;

	return $self->_header_html("EMBOSS: " . $acd->name) .
		$self->_banner_html .
		$self->_input_html($acd, $hide_optional) .
		$self->_footer_html;
}

=item input_page($output_dir)

Generates an output page based on the contents of the specified directory.

$output_dir is a directory containing the output of an EMBOSS application.

=cut

sub output_page {
	my ($self, $output_dir) = @_;

	return $self->_header_html("EMBOSS: output") .
		$self->_banner_html .
		$self->_output_html($output_dir) .
		$self->_footer_html;
}

sub help_page {
	my ($self, $app) = @_;

	my $title = defined $app ? "EMBOSS: $app help" : "EMBOSS: help";
	return $self->_header_html($title) .
		$self->_banner_html .
		$self->_help_html($app) .
		$self->_footer_html;
}

=item manual_page($app, $manual)

Generates the application-specific manual page.

$app is the name of the application.

$manual_html is the full text of the HTML application manual.

=cut

sub manual_page {
	my ($self, $app, $manual_html) = @_;

	return $self->_header_html("EMBOSS: $app manual") .
		$self->_manual_html($manual_html) .
		$self->_footer_html;
}

=item default_output_page($refresh_delay)

Generates the default output page to be used as a placeholder until the
application has finished running and the actual output is available.

$refresh_delay is the number of seconds to wait between page reloads.

=cut

sub default_output_page {
	my ($self, $delay) = @_;

	my $date = localtime;
	$date =~ s/ /&nbsp;/g;
	my $html = <<EOF;
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en' lang='en'>
  <head>
    <title>EMBOSS: running...</title>
    <link rel='stylesheet' type='text/css' href='$self->{style_url}' />
    <meta http-equiv='Cache-Control' content='no-cache' />
    <meta http-equiv='Pragma' content='no-cache' />
    <meta http-equiv='refresh' content='$delay' />
    <meta http-equiv='expires' content='-1' />
  </head>
  <body>
    <div id='horizon'>
      <div id='running'>
        <img src='$self->{image_url}/running.gif' width='420' height='20'
         alt='EMBOSS is running' />
        <p>EMBOSS has been running since $date.</p>
EOF
	$html .= <<EOF unless $self->{frames};
        <p>This page will refresh every few seconds until the application has
         finished and your output is displayed.  If you are impatient, you can
         manually refresh the page by clicking the Reload button in your
         browser.</p>
EOF
	$html .= <<EOF;
      </div>
    </div>
  </body>
</html>
EOF
	return $html;
}

=item error_page(@error)

Generates an error page.

@error is the text of the error message.  All elements of the list are joined
into a single string, so this method has the same syntax as print, warn, die,
etc.

=cut

sub error_page {
	my ($self, @error) = @_;

	return $self->_header_html("EMBOSS: error") .
		$self->_banner_html .
		$self->_error_html(@error) .
		$self->_footer_html;
}

=item frameset_page()

Generates a page that sets up the menu and main content frames.

=cut

sub frameset_page {
	my ($self) = @_;

	my $menu_url = $self->{static} ?
		"groupmenu.html" : join('/', $self->{script_url}, 'menu');
	my $main_url = $self->{static} ?
		"intro.html" : join('/', $self->{script_url}, 'intro');
	return <<EOF;
<?xml version='1.0' encoding='UTF-8'?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en' lang='en'>
  <head>
    <title>EMBOSS explorer</title>
  </head>
  <frameset cols="170, *">
    <frame src="$menu_url" name="menu" />
    <frame src="$main_url" name="main" />
  </frameset>
</html>
EOF
}

# # # # # # # # # # # # # # # PRIVATE METHODS # # # # # # # # # # # # # # #

sub _menu_html {
	my ($self, @entries) = @_;

	my $html = <<EOF;
    <div id='menu'>
EOF
	if (@{$entries[0]} > 2) {	# entries are groups...
		my $menu_url = $self->{static} ?
			"alphamenu.html" : "$self->{script_url}/menu?sort=alpha";
		$html .= <<EOF;
      <span class='sort'>[ <a href='$menu_url'>sort alphabetically</a> ]</span>
      <ul id='groups'>
EOF
		foreach my $group (@entries) {
			my $group_name = CGI::escapeHTML(shift @$group);
			$html .= <<EOF;
        <li class='group'><span class='group'>$group_name</span>
          <ul class='group'>
EOF
			foreach my $app (@$group) {
				$html .= $self->_app_menu_item_html($app);
			}
			$html .= <<EOF;
          </ul>
        </li>
EOF
		}
		$html .= <<EOF;
      </ul>
      <span class='sort'>[ <a href='$self->{script_url}/menu?sort=alpha'>sort alphabetically</a> ]</span>
EOF
	} else {	# entries are individual applications...
		my $menu_url = $self->{static} ?
			"groupmenu.html" : "$self->{script_url}/menu";
		$html .= <<EOF;
      <span class='sort'>[ <a href='$menu_url'>sort by group</a> ]</span>
      <ul id='apps'>
EOF
		foreach my $app (@entries) {
			$html .= $self->_app_menu_item_html($app);
		}
		$html .= <<EOF;
      </ul>
      <span class='sort'>[ <a href='$self->{script_url}/menu'>sort by group</a> ]</span>
EOF
	}
	$html .= <<EOF;
    </div>
EOF
	return $html;
}

sub _input_html {
	my ($self, $acd, $hide_optional) = @_;

	my $app = $acd->name;
	my $app_html = CGI::escapeHTML($app);
	my $app_url = CGI::escape($app);
	my $doc_html = CGI::escapeHTML($acd->documentation);
	my $url = join "/", $self->{script_url}, $app_url;
	my $manual_url = $self->{static} ?
		"$self->{manual_url}/$app_url.html" :
		"$self->{script_url}/help/$app_url";

	my $html = <<EOF;
    <div id='input'>
      <h2>$app_html</h2>
      <p class='documentation'>
        $doc_html
        (<a href='$manual_url'>read the manual</a>)
      </p>
EOF
	unless ($self->{static}) {
		$html .= $hide_optional ? <<EOF
      <div id='legend' class='required'>
        <p>
          Only required fields are visible.
          (<a href='$url?_pref_hide_optional=0'>show optional fields</a>)
        </p>
      </div>
EOF
	                            : <<EOF;
      <div id='legend' class='additional'>
        <p>
          Unshaded fields are optional and can safely be ignored.
          (<a href='$url?_pref_hide_optional=1'>hide optional fields</a>)
        </p>
      </div>
EOF
	}
	$html .= <<EOF;
      <form id='input-form' action='$url' method='post'
       enctype='multipart/form-data'>
EOF

	# application specific parameter munging to pretty it all up a bit...
	#

	# step through the application parameters and create the input fields...
	#
	foreach my $param ($acd->param) {
		next if $param->{_ignore};

		# skip optional parameters if the user has chosen to hide them...
		#
		my $required = 0;
		if (defined $param->{parameter} && $param->{parameter} =~ /^y/i) {
			$required = 1;
		} elsif (defined $param->{standard} && $param->{standard} =~ /^y/i) {
			$required = 1;
		}
		if (defined $param->{needed} && $param->{needed} =~ /^n/i) {
			$required = 0;
		}
		next if (!$required && $hide_optional)
			and $param->{datatype} !~ /^(end)?section$/;
			# for now always display sections; TODO the ACD parser should
			# handle these better so we can look ahead to tell if a section
			# is empty or not...
		$param->{_class} = $required ? 'required' : 'additional';
			# using EMBOSS parlance here...
		$param->{_hide_optional} = $hide_optional;
		
		# call the method corresponding to the parameter's datatype, appending
		# an error message if no such method exists...
		#
		my $param_html = eval "\$self->_acd_$param->{datatype}(\$param)";
		if (defined $param_html) {
			$html .= $param_html;
		} else {
			carp "unknown datatype $param->{datatype} in $app";
			$html .= $self->_error_html("unknown datatype $param->{datatype}");
		}
	}

	$html .= <<EOF;
        <fieldset title='Run section'>
          <legend>Run section</legend>
          <div class='additional' id='email'>
            <span class='label'>Email address:</span>
            <span class='field'><input type='text' name='_email' /></span>
            <span class='description'>
              If you are submitting a long job and would like to be informed
              by email when it finishes, enter your email address here.
            </span>
          </div>
          <div class='required' id='submit'>
            <input type='submit' name='_run' value='Run $app_html' />
			<input type='reset' />
          </div>
        </fieldset>
      </form>
    </div>
EOF
	return $html;
}

sub _output_html {
	my ($self, $output_dir) = @_;

	my $html = <<EOF;
    <div id='output'>
      <dl>
EOF

	# deal with any errors first...
	#
	my $error_file = "$output_dir/.stderr";
	if (-s $error_file) {
		# TODO clean up error messages and output
		$html .= $self->_output_error_html($error_file)
			or return $self->_error_html("failed to read $error_file: $!");
	}

	# deal with console output next...
	#
	my $output_file = "$output_dir/.stdout";
	if (-s $output_file) {
		$html .= $self->_output_file_inline_html($output_file)
			or return $self->_error_html("failed to read $output_file: $!");
	}

	# deal with remaining output files in order...
	#
	opendir DIR, $output_dir
		or return $self->_error_html("failed to open $output_dir: $!");
	foreach my $file (readdir DIR) {
		next if $file =~ /^\./;	# skip dot files...
		next if $file eq "index.html";	# skip our index file...
		my $output_file = "$output_dir/$file";
		next if -z $output_file;	# skip empty files...
		if ($output_file =~ /\.png$/) {
			$html .= $self->_output_image_html($output_file);
		} elsif ($output_file =~ /\.ps$/) {
			$html .= $self->_output_file_link_html($output_file);
		} else {
			$html .= $self->_output_file_inline_html($output_file);
		}
	}
	closedir DIR;

	$html .= <<EOF;
      </dl>
    </div>
EOF

	return $html;
}

sub _help_html {
	my ($self, $app) = @_;

	my $html = <<EOF;
EOF
	return $html;
}

sub _manual_html {
#	my ($self, $manual) = @_;
	my ($self, $manual_html) = @_;

#	# read the manual from the specified file...
#	#
#	my $manual_html = $self->_read_from_file($manual)
#		or return $self->_error_html("failed to read $manual: $!");

	# we've already got a document going, so delete the body tags and
	# anything outside them...
	#
	$manual_html =~ s/.*?<body.*?>//is;
	$manual_html =~ s/<\/body>.*?//is;

	# update the images in the manual...
	#
	$manual_html =~ s/src="file:\/\/.*?(emboss_icon\.jpg)"/src="$self->{manual_url}\/$1"/;
	$manual_html =~ s/src="file:\/\/.*?([^\/]*\.gif)"/src="$self->{manual_url}\/$1"/g;

	# if the pages are not being generated statically, we need to remove
	# the .html extension from the relative links within the manual...
	#
	if (not $self->{static}) {
		$manual_html =~ s/index\.html/$self->{script_url}\/help/g;
		$manual_html =~ s/<a href="(\S+)\.html">/<a href="$1">/g;
	}

	my $html = <<EOF;
    <div id='manual'>
      <!-- tfm output starts here -->
      $manual_html
      <!-- tfm output ends here -->
    </div>
EOF
	return $html;
}

sub _intro_html {
	my ($self) = @_;

	my $html = <<EOF;
    <div id='intro'>
      <p>Welcome to EMBOSS explorer, a graphical user interface to the
      <a href='http://emboss.sourceforge.net/' title='EMBOSS'>EMBOSS</a>
       suite of bioinformatics tools.</p>
      <p>To continue, select an application from the menu to the left.  Move
	   the mouse pointer over the name of an application in the menu to display
	   a short description.  To search for a particular application, use
      <a href='wossname' title='wossname'>wossname</a>.</p>
      <p>For more information about EMBOSS explorer, including how to
	   download and install it locally, visit the
      <a href='http://embossgui.sourceforge.net/'
       title='EMBOSS explorer'>EMBOSS explorer</a> website.</p>
      <p>Development of EMBOSS explorer has been supported by the
      <a href='http://www.nrc-cnrc.gc.ca/'
	   title='National Research Council Canada'>National Research Council
       of Canada</a> and <a href='http://www.genomeprairie.ca/'
       title='Genome Prairie'>Genome Prairie</a>.</p>
    </div>
EOF
	return $html;
}

sub _default_output_html {
	my ($self) = @_;

	my $date = localtime;
	$date =~ s/ /&nbsp;/g;
	my $html = <<EOF;
    <div id='horizon'>
      <div id='running'>
        <img src='$self->{image_url}/running.gif' width='420' height='20'
         alt='EMBOSS is running' />
        <p>EMBOSS has been running since $date.</p>
EOF
	$html .= <<EOF unless $self->{frames};
        <p>This page will refresh every few seconds until the application has
         finished and your output is displayed.  If you are impatient, you can
         manually refresh the page by clicking the Reload button in your
         browser.</p>
EOF
	$html .= <<EOF;
      </div>
    </div>
EOF
	return $html;
}

sub _error_html {
	my ($self, @error) = @_;

	my $error_html = CGI::escapeHTML(join '', @error);
	my $html = <<EOF;
    <div class='error'>
      <p>$error_html</p>
    </div>
EOF
	return $html;
}

sub _header_html {
	my ($self, $title, @meta) = @_;

	# frames or no, we have to open the HTML...
	#
	my $title_html = CGI::escapeHTML($title);
	my $meta_html = join "\n", @meta;
	my $html = <<EOF;
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en' lang='en'>
  <head>
    <title>$title_html</title>
    <link rel='stylesheet' type='text/css' href='$self->{style_url}' />
    $meta_html
  </head>
  <body>
EOF
	return $html;
}

sub _banner_html {
	my ($self) = @_;

	my $html = <<EOF;
    <h1>EMBOSS explorer</h1>
EOF
	return $html;
}

sub _footer_html {
	my ($self) = @_;

	# frames or no, we have to close the HTML...
	#
	my $html = <<EOF;
  </body>
</html>
EOF
	return $html;
}

sub _app_menu_item_html {
	my ($self, $aref) = @_;
	
	my ($app, $doc) = @$aref;
	my $app_html = CGI::escapeHTML($app);
	my $app_url = CGI::escape($app);
	my $doc_html = CGI::escapeHTML($doc);
	my $url = $self->{static} ?
		"$app_url.html" : join("/", $self->{script_url}, $app_url);
	my $target = $self->{frames} ? " target='main'" : "";
	
	return "<li><a href='$url' title=\"$doc\"$target>$app</a></li>\n";
}

sub _output_error_html {
	my ($self, $file) = @_;

	my $basename = basename $file;
	my $file_html = CGI::escapeHTML($basename);
	my $file_url = CGI::escape($basename);
	my $content = $self->_read_from_file($file)
		or return undef;
	my $content_html = CGI::escapeHTML($content);
	return <<EOF;
      <span class='item'>
        <dt class='error'><span class='type'>Error</span> &nbsp;
         application terminated</dt>
          <dd class='error'><pre>$content_html</pre></dd>
      </span>
EOF
}

sub _output_file_inline_html {
	my ($self, $file) = @_;

	my $basename = basename $file;
	my $file_html = CGI::escapeHTML($basename);
	my $file_url = CGI::escape($basename);
	my $content = $self->_read_from_file($file)
		or return undef;
	my $content_html = CGI::escapeHTML($content);
	return <<EOF;
      <span class='item'>
        <dt class='inline'><span class='type'>Output file</span> &nbsp;
        <a href='$file_url' target='_parent'
         title='right-click to save'>$file_html</a></dt>
          <dd class='inline'><pre>$content_html</pre></dd>
      </span>
EOF
}

sub _output_file_link_html {
	my ($self, $file) = @_;

	my $basename = basename $file;
	my $file_html = CGI::escapeHTML($basename);
	my $file_url = CGI::escape($basename);
	return <<EOF;
      <span class='item'>
        <dt class='link'><span class='type'>Output file</span> &nbsp;
        <a href='$file_url' target='_parent'
         title='right-click to save'>$file_html</a></dt>
          <dd class='link'><a href='$file_url'>click to view</a></dd>
      </span>
EOF
}

sub _output_image_html {
	my ($self, $file) = @_;

	my $basename = basename $file;
	my $file_html = CGI::escapeHTML($basename);
	my $file_url = CGI::escape($basename);
	return <<EOF;
      <span class='item'>
        <dt class='image'><span class='type'>Image file</span> &nbsp;
        <a href='$file_url' target='_parent'
         title='right-click to save'>$file_html</a></dt>
          <dd class='image'><a href='$file_url' target='_parent'
           title='right-click to save'><img src='$file_url' /></a></dd>
      </span>
EOF
}

sub _read_from_file {
	my ($self, $file) = @_;

	open FILE, '<', $file
		or return undef;
	my $content = join '', <FILE>;
	close FILE;
	return $content;
}

sub _get_info {
	my ($self, $param) = @_;

	return CGI::escapeHTML($param->{information} || "");
}

sub _get_default {
	my ($self, $param) = @_;
	
	my $default = $param->{default} || "";
	if ($default =~ /\$/) {
		$default = "";
	}
	my $expect = $param->{expected} || "";
	if (length $expect) {
		if ($expect =~ /^if/i) {
			$expect = "($expect)";
		} else {
			$expect = "(default is $expect)";
		}
	} 
	my $default_html = CGI::escapeHTML($default);
	my $expect_html = CGI::escapeHTML($expect);
	return ($default_html, $expect_html);
}

# # # # # # # # # # # # # # # DATATYPE METHODS # # # # # # # # # # # # # # #

# # # # # # # # # # # # # # # # SIMPLE TYPES # # # # # # # # # # # # # # # #

sub _acd_array {
	&_text_field_html;
}

sub _acd_boolean {
	my ($self, $param) = @_;

	$param->{information} .= "?" unless $param->{information} =~ /\?$/;
	$param->{_options} = [
		[ Yes => 1 ],
		[ No => 0 ]
	];
	if (defined $param->{default} && $param->{default} =~ /^y/i) {
		$param->{default} = 1;
	} else {
		$param->{default} = 0;
	}
	$param->{maximum} = 1;

	$self->_selection_list_html($param);
}

sub _acd_float {
	&_text_field_html;
}

sub _acd_integer {
	&_text_field_html;
}

sub _acd_range {
	&_text_field_html;
}

sub _acd_string {
	&_text_field_html;
}

sub _acd_toggle {
	&_acd_boolean;
}

# # # # # # # # # # # # # # # # INPUT TYPES # # # # # # # # # # # # # # # #

sub _acd_codon {
	my ($self, $param) = @_;

	$param->{information} = "Codon usage table";
	$param->{default} = "Ehum.cut";

	$self->_acd_datafile($param);
}

sub _acd_cpdb {
	&_acd_infile;
}

sub _acd_datafile {
	my ($self, $param) = @_;

	my $info = $self->_get_info($param);
	if (length $info) {
		$info .= "." unless $info =~ /\.$/;
	} else {
		$info = "Select a data file.";
	}
	my ($default, $expect) = $self->_get_default($param);
	my $html = <<EOF;
            <div class='$param->{_class}'>
              <p class='label'>$info Use one of the following two fields:</p>
              <ol class='datafile'>
                <li>
                  <span class='label'>To access a standard EMBOSS data file, enter the name here:</span>
                  <span class='field'><input type='text' name='$param->{name}.db' value='$default'/></span>
                  <span class='description'>$expect</span>
                </li>
                <li>
                  <span class='label'>To upload a data file from your local computer, select it here:</span>
                  <span class='field'><input type='file' name='$param->{name}.file' /></span>
                </li>
              </ol>
            </div>
EOF
	return $html
}

sub _acd_directory {
	my ($self, $param) = @_;

	return $self->_error_html("inappropriate datatype $param->{datatype}");
}

sub _acd_dirlist {
	my ($self, $param) = @_;

	return $self->_error_html("inappropriate datatype $param->{datatype}");
}

sub _acd_discretestates {
	&_acd_infile;
}

sub _acd_distances {
	&_acd_infile;
}

sub _acd_features {
	&_acd_infile;
}

sub _acd_filelist {
	&_acd_infile;
		# TODO how to upload multiple files on a web form?  perhaps by
		# allowing a compressed file to be uploaded, then exploding it and
		# building the command line accordingly...
}

sub _acd_frequencies {
	&_acd_infile;
}

sub _acd_infile {
	my ($self, $param) = @_;

	my $info = $self->_get_info($param);
	if (length $info) {
		$info =~ s/\.$//;
	} else {
		warn "no information for $param->{name}";
		$info = "Select an input file:";
	}
	my ($default, $expect) = $self->_get_default($param);
	my $html = <<EOF;
            <div class='$param->{_class}'>
              <span class='label'>$info:</span>
              <span class='field'><input type='file' name='$param->{name}' /></span>
            </div>
EOF
	return $html
}

sub _acd_matrix {
	my ($self, $param) = @_;

	$param->{expected} = "EBLOSUM62 for protein, EDNAFULL for nucleic";

	$self->_acd_datafile($param);
}

sub _acd_matrixf {
	&_acd_matrix;
}

sub _acd_pattern {
	&_text_field_html;
}

sub _acd_properties {
	&_acd_infile;
}

sub _acd_regexp {
	&_text_field_html;
}

sub _acd_scop {
	&_acd_infile;
}

sub _acd_sequence {
	my ($self, $param) = @_;

	my $info = $self->_get_info($param);
	if (length $info) {
		$info .= "." unless $info =~ /\.$/;
	} else {
		$info = "Select an input sequence.";
	}
	my $html = <<EOF;
            <div class='$param->{_class}'>
              <p class='label'>$info Use one of the following three fields:</p>
              <ol class='sequence'>
                <li>
                  <span class='label'>To access a sequence from a database, enter the USA here:</span>
                  <span class='field'><input type='text' name='$param->{name}.db' /></span>
                </li>
                <li>
                  <span class='label'>To upload a sequence from your local computer, select it here:</span>
                  <span class='field'><input type='file' name='$param->{name}.file' /></span>
                </li>
                <li>
                  <span class='label'>To enter the sequence data manually, type here:</span>
                  <span class='field'><textarea name='$param->{name}.text' rows='8' cols='50'></textarea></span>
                </li>
              </ol>
            </div>
EOF
	return $html
}

sub _acd_seqall {
	&_acd_sequence;
}

sub _acd_seqset {
	&_acd_sequence;
}

sub _acd_seqsetall {
	&_acd_sequence;
}

sub _acd_tree {
	&_acd_infile;
}

# # # # # # # # # # # # # # SELECTION LIST TYPES # # # # # # # # # # # # # #

sub _acd_list {
	my ($self, $param) = @_;

	my @options;
	my $delimiter = sprintf '\s*%s\s*', $param->{delimiter} || ";";
	my $codedelimiter = $param->{codedelimiter} || ":";
	foreach my $option (split /$delimiter/, $param->{values}) {
		my ($value, $name) = split /$codedelimiter/, $option;
		push @options, [ $name => $value ];
	}
	$param->{_options} = \@options;
	$param->{maximum} = 1
		unless defined $param->{maximum};

	$self->_selection_list_html($param);
}

sub _acd_selection {
	my ($self, $param) = @_;

	my @options;
	my $delimiter = sprintf '\s*%s\s*', $param->{delimiter} || ";";
	my $index = 0;
	foreach my $option (split /$delimiter/, $param->{values}) {
		my $value = ++$index;
		push @options, [ $option => $value ];
	}
	$param->{_options} = \@options;
	$param->{maximum} = 1
		unless defined $param->{maximum};
	
	$self->_selection_list_html($param);
}

# # # # # # # # # # # # # # # # OUTPUT TYPES # # # # # # # # # # # # # # # #

sub _acd_align {
	my ($self, $param) = @_;

	my $html = <<EOF;
            <input type='hidden' name='$param->{name}' value='$param->{name}' />
EOF

	return $html if $param->{_hide_optional};

	$param->{_class} = "additional";
	$param->{name} = "aformat";
	$param->{information} = "Output alignment format";
	$param->{_options} = [
		[ "EMBOSS multiple" => "simple" ],
		[ "FASTA multiple" => "fasta" ],
		[ "MSF multiple" => "msf" ],
		[ "SRS multiple" => "srs" ],
		[ "EMBOSS pairwise" => "pair" ],
		[ "FASTA pairwise" => "markx0" ],
		[ "FASTA pairwise marking differences" => "markx1" ],
		[ "FASTA pairwise differences only" => "markx2" ],
		[ "FASTA pairwise simple" => "markx3" ],
		[ "FASTA pairwise simple with comments" => "markx10" ],
		[ "SRS pairwise" => "srspair" ],
		[ "Pairwise scores only" => "score" ]
	];
	$param->{default} = $param->{aformat};
	$param->{maximum} = 1;
	$html .= $self->_selection_list_html($param);

	return $html;
}

sub _acd_featout {
	my ($self, $param) = @_;

	my $html = <<EOF;
            <input type='hidden' name='$param->{name}' value='$param->{name}' />
EOF

	return $html if $param->{_hide_optional};

	$param->{_class} = "additional";
	$param->{name} = "offormat";
	$param->{information} = "Output feature format";
	$param->{_options} = [
		[ "EMBL" => "embl" ],
		[ "GFF" => "gff" ],
		[ "SwissProt" => "swiss" ],
		[ "PIR" => "pir" ]
	];
	$param->{default} = $param->{offormat};
	$param->{maximum} = 1;
	$html .= $self->_selection_list_html($param);

	my $ofsingle = {
		_class => 'additional',
		name => 'ofsingle',
		information => 'Separate files for each entry?'
	};
	$html .= $self->_acd_boolean($ofsingle);
	
	return $html;
}

sub _acd_outfile {
	my ($self, $param) = @_;

	# TODO allow the user to specify an output file name?  but why?
	my $outfile = $param->{default} || $param->{name};
	my $html = <<EOF;
            <input type='hidden' name='$param->{name}' value='$outfile' />
EOF
	return $html;
}

sub _acd_outdir {
	&_skip_html;
}

sub _acd_report {
	my ($self, $param) = @_;

	my $html = <<EOF;
            <input type='hidden' name='$param->{name}' value='$param->{name}' />
EOF

	return $html if $param->{_hide_optional};

	$param->{_class} = "additional";
	$param->{name} = "rformat";
	$param->{information} = "Output report format";
	$param->{_options} = [
		[ "EMBL" => "embl" ],
		[ "Genbank" => "genbank" ],
		[ "GFF" => "gff" ],
		[ "PIR" => "pir" ],
		[ "SwissProt" => "swiss" ],
		[ "EMBOSS list file" => "listfile" ],
		[ "DbMotif" => "dbmotif" ],
		[ "EMBOSS diffseq" => "diffseq" ],
		[ "tab-delimited text" => "excel" ],
		[ "EMBOSS FeatTable" => "feattable" ],
		[ "EMBOSS Motif" => "motif" ],
		[ "EMBOSS Regions" => "regions" ],
		[ "EMBOSS SeqTable" => "seqtable" ],
		[ "SRS simple" => "simple" ],
		[ "SRS" => "srs" ],
		[ "EMBOSS Table" => "table" ],
		[ "EMBOSS tagseq" => "tagseq" ]
	];
	$param->{default} = $param->{rformat};
	$param->{maximum} = 1;
	$html .= $self->_selection_list_html($param);

	return $html;
}

sub _acd_seqout {
	my ($self, $param) = @_;

	if ($param->{_hide_optional}) {
		my $html = <<EOF;
            <input type='hidden' name='$param->{name}' value='$param->{name}' />
EOF
		return $html;
	}

	$param->{_class} = "additional";
	$param->{information} = "Output sequence format";
	$param->{_options} = [
		[ "ACeDB" => "acedb::$param->{name}" ],
		[ "ASN.1" => "asn1::$param->{name}" ],
		[ "Clustal .aln" => "clustal::$param->{name}" ],
		[ "CODATA" => "codata::$param->{name}" ],
		[ "EMBL" => "embl::$param->{name}" ],
		[ "Pearson FASTA" => "fasta::$param->{name}" ],
		[ "Fitch" => "fitch::$param->{name}" ],
		[ "GCG 9.x/10.x" => "gcg::$param->{name}" ],
		[ "GCG 8.x" => "gcg8::$param->{name}" ],
		[ "Genbank" => "genbank::$param->{name}" ],
		[ "GFF" => "gff:$param->{name}" ],
		[ "Hennig86" => "hennig86::$param->{name}" ],
		[ "Intelligenetics" => "ig::$param->{name}" ],
		[ "Jackknifer" => "Jackknifer::$param->{name}" ],
		[ "Jackknifernon" => "Jackknifernon::$param->{name}" ],
		[ "Mega" => "mega::$param->{name}" ],
		[ "Meganon" => "meganon::$param->{name}" ],
		[ "GCG MSF" => "msf::$param->{name}" ],
		[ "NBRF (PIR)" => "nrbf::$param->{name}" ],
		[ "NCBI FASTA" => "ncbi::$param->{name}" ],
		[ "Nexus/PAUP" => "nexus::$param->{name}" ],
		[ "Nexusnon/PAUPnon" => "nexusnon::$param->{name}" ],
		[ "PHYLIP interleaved" => "phylip::$param->{name}" ],
		[ "PHYLIP non-interleaved" => "phylip3::$param->{name}" ],
		[ "SELEX" => "selex:$param->{name}" ],
		[ "DNA strider" => "strider::$param->{name}" ],
		[ "SwissProt" => "swiss::$param->{name}" ],
		[ "Staden" => "staden::$param->{name}" ],
		[ "plain text" => "text::$param->{name}" ],
		[ "Treecon" => "treecon::$param->{name}" ],
	];
	$param->{default} = "fasta::$param->{name}";
	$param->{maximum} = 1;
	
	$self->_selection_list_html($param);
}

sub _acd_seqoutall {
	&_acd_seqout;
}

sub _acd_seqoutset {
	&_acd_seqout;
}

sub _acd_outcodon {
	my ($self, $param) = @_;

	my $html = <<EOF;
            <input type='hidden' name='$param->{name}' value='$param->{name}' />
EOF

	return $html if $param->{_hide_optional};

	$param->{_class} = "additional";
	$param->{name} = "oformat";
	$param->{information} = "Output report format";
	$param->{_options} = [
		[ "EMBOSS codon usage file" => "emboss" ],
		[ "GCG codon usage file" => "gcg" ],
		[ "CUTG codon usage file" => "cutg" ],
		[ "CUTG codon usage file with amino acids" => "cutgaa" ],
		[ "CUTG species summary file" => "spsum" ],
		[ "Mike Cherry codonusage database file" => "cherry" ],
		[ "TransTerm database file" => "transterm" ],
		[ "FHCRC codehop program codon usage file" => "codehop" ],
		[ "Staden package codon usage file with percentages" => "staden" ],
		[ "Staden package codon usage file with numbers" => "numstaden" ]
	];
	$param->{default} = $param->{rformat};
	$param->{maximum} = 1;
	$html .= $self->_selection_list_html($param);

	return $html;
}

# # # # # # # # # # # # # # # # GRAPHICS TYPES # # # # # # # # # # # # # # # #

sub _acd_graph {
	my ($self, $param) = @_;

	# only display one of graph and xygraph...
	#
	return "" if $self->{_acd_graph};
	++$self->{_acd_graph};

	$param->{information} = "Output graphic format"
		unless defined $param->{information};
	$param->{_options} = [
		[ PostScript => 'colourps' ],
		[ PNG => 'png' ]
	];
	$param->{default} = 'png';
	$param->{maximum} = 1;
	my $html = $self->_selection_list_html($param);
	
	return $html if $param->{_hide_optional};

	my $gtitle = {
		_class => 'additional',
		name => 'gtitle',
		information => 'Graphic title'
	};
	$html .= $self->_text_field_html($gtitle);
	my $gsubtitle = {
		_class => 'additional',
		name => 'gsubtitle',
		information => 'Graphic subtitle'
	};
	$html .= $self->_text_field_html($gsubtitle);
	my $gxtitle = {
		_class => 'additional',
		name => 'gxtitle',
		information => 'X axis title'
	};
	$html .= $self->_text_field_html($gxtitle);
	my $gytitle = {
		_class => 'additional',
		name => 'gytitle',
		information => 'Y axis title'
	};
	$html .= $self->_text_field_html($gytitle);

	return $html;
}

sub _acd_xygraph {
	&_acd_graph;
}

# # # # # # # # # # # # # # # # SECTION TYPES # # # # # # # # # # # # # # # #

sub _acd_section {
	my ($self, $param) = @_;
	
	my $title = $param->{information} || $param->{name};
	my $title_html = CGI::escapeHTML($title);
	my $html = <<EOF;
        <fieldset title='$title_html'>
          <legend>$title_html</legend>
EOF
	return $html;
}

sub _acd_endsection {
	my ($self, $param) = @_;

	my $html = <<EOF;
        </fieldset>
EOF
	return $html;
}

# # # # # # # # # # # # # # # # VARIABLE TYPES # # # # # # # # # # # # # # # #

sub _acd_variable {
	my ($self, $param) = @_;
	
	return "";
}

# # # # # # # # # # # # # DATATYPE HELPER METHODS # # # # # # # # # # # # #

sub _selection_list_html {
	my ($self, $param) = @_;

	my $info = $self->_get_info($param);
	warn "no information for $param->{name}" unless $info;
	my $multiple = $param->{maximum} > 1 ? " multiple='multiple'" : "";
	defined $param->{default}
		or $param->{default} = "";	# prevent uninitialized warning...
	my $html = <<EOF;
            <div class='$param->{_class}'>
              <span class='label'>$info</span>
              <span class='field'>
                <select name='$param->{name}'$multiple>
EOF
	foreach my $option (@{$param->{_options}}) {
		my ($name, $value) = @$option;
		my $selected = 
			$value eq $param->{default} ? " selected='selected'" : "";
		$html .= <<EOF
                  <option value='$value'$selected>$name</option>
EOF
	}
	$html .= <<EOF;
                </select>
              </span>
            </div>
EOF
	return $html;
}

sub _text_field_html {
	my ($self, $param) = @_;

	my $info = $self->_get_info($param);
	warn "no information for $param->{name}" unless $info;
	my ($default, $expect) = $self->_get_default($param);
	my $html = <<EOF;
            <div class='$param->{_class}'>
              <span class='label'>$info</span>
              <span class='field'><input type='text' name='$param->{name}'
               value='$default' /></span>
              <span class='description'>$expect</span>
            </div>
EOF
	return $html
}

sub _skip_html {
	my ($self, $param) = @_;

	my $html = <<EOF;
<!-- parameter $param->{name} with datatype $param>{datatype} skipped -->
EOF
	return $html;
}

1;

=back

=head1 BUGS

If the user has asked to see only required fields, sections containing only
optional fields will still be visible, even though the fields they contain are
hidden.  Fixing this requires better section handling in EMBOSS::ACD.

=head1 COPYRIGHT

Copyright (c) 2004 Luke McCarthy.  All rights reserved.  This program is free
software.  You may copy or redistribute it under the same terms as Perl itself.
