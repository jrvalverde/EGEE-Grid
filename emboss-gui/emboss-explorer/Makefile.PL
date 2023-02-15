use 5.6.0;
use ExtUtils::MakeMaker;

WriteMakefile(
    NAME          => 'EMBOSS::GUI',
    VERSION_FROM  => 'lib/EMBOSS/GUI.pm',
    PREREQ_PM     => {
		Parse::RecDescent => 1.90,
		Mail::Send => 0
	},
	PREREQ_FATAL => 1,
    ABSTRACT_FROM => 'lib/EMBOSS/GUI.pm',
    AUTHOR        => 'Luke McCarthy <lukem@gene.pbi.nrc.ca>'
);
