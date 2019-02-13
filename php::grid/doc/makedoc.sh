#!/bin/bash
# $Id: makedoc.sh,v 1.2 2005/05/31 14:00:25 netadmin Exp $ 

#/**
#  * makedoc - PHPDocumentor script to save your settings
#  * 
#  * Put this file inside your PHP project homedir, edit its variables and run whenever you wants to
#  * re/make your project documentation.
#  * 
#  * The version of this file is the version of PHPDocumentor it is compatible.
#  * 
#  * It simples run phpdoc with the parameters you set in this file.
#  * NOTE: Do not add spaces after bash variables.
#  *
#  * @copyright         makedoc.sh is part of PHPDocumentor project {@link http://freshmeat.net/projects/phpdocu/} and its LGPL
#  * @author            Roberto Berto <darkelder (inside) users (dot) sourceforge (dot) net>
#  * @version           Release-1.1.0
#  */


##############################
# should be edited
##############################

#/**
#  * title of generated documentation, default is 'Generated Documentation'
#  * 
#  * @var               string TITLE
#  */
TITLE="php::Grid Documentation"

#/** 
#  * name to use for the default package. If not specified, uses 'default'
#  *
#  * @var               string PACKAGES
#  */
PACKAGES="Grid"

#/** 
#  * name of a directory(s) to parse directory1,directory2
#  * $PWD is the directory where makedoc.sh 
#  *
#  * @var               string PATH_PROJECT
#  */
#PATH_PROJECT=$PWD
PATH_PROJECT=$PWD/../src

#/**
#  * path of PHPDoc executable
#  *
#  * @var               string PATH_PHPDOC
#  */
PATH_PHPDOC=/opt/tools/phpDocumentor/phpdoc

#/**
#  * where documentation will be put
#  *
#  * @var               string PATH_DOCS
#  */
PATH_DOCS=$PWD/pDoc

#/**
#  * what outputformat to use (html/pdf)
#  *
#  * @var               string OUTPUTFORMAT
#  */
OUTPUTFORMAT=HTML

#/** 
#  * converter to be used
#  *
#  * @var               string CONVERTER
#  */
CONVERTER=Smarty

#/**
#  * template to use
#  *
#  * @var               string TEMPLATE
#  */
TEMPLATE=default

#/**
#  * parse elements marked as private
#  *
#  * @var               bool (on/off)           PRIVATE
#  */
PRIVATE=on

#/**
#  * custom tags to accept
#  *
#  * @var   	    string CUSTOM_TAGS
#  */
CUSTOM_TAGS="todo,note,pre"

# make documentation

echo "Making phpDocumentor documentation..."

$PATH_PHPDOC -d $PATH_PROJECT -t $PATH_DOCS -ti "$TITLE" -dn $PACKAGES \
-ct $CUSTOM_TAGS \
-o $OUTPUTFORMAT:$CONVERTER:$TEMPLATE -pp $PRIVATE > logs/phpdoc.log 2>&1

# add doxygen and phpXref docs as well
 
echo "Making Doxygen documentation..."

doxygen doxygen.cfg > logs/doxygen.log 2>&1
 
echo "Making phpXref documentation..."

/opt/tools/phpxref-0.4.1/phpxref.pl -c phpxref.cfg > logs/phpxref.log 2>&1

# generate new distribution file

echo "Generating grid-sources.tgz..."

rm php-grid-src.tgz
cd ../..
tar -zcf /tmp/php-grid-src.tgz php::grid
mv /tmp/php-grid-src.tgz php::grid/doc/.
cd -

# vim: set expandtab : 
