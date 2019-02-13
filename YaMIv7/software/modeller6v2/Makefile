############################################################################
#
# Top Makefile for some useful packing stuff:
#
# make dist        ... to prepare a gzipped tar file for distribution of MODELLER without source
# make distsrc     ... to prepare a gzipped tar file with dist plus sources
# make distsrc-uue ... to prepare a uuencoded file for distribution of MODELLER with sources (uuencoded distsrc)
#

SHELL=/bin/sh

.PHONY: default clean distclean depend install check lintfor opt deb
	man tape dist-mail distsrc dist floppy
	makefiles all cleaner

# handle it via -, where necessary
.IGNORE:

default: dist

# clean examples:
clean_examples:
	(cd examples; ./Clean_all)

# clean garbage in the root directory:
clean_root: 
	-rm *.tar.Z *.tar.gz *.uue *.log

# clean doc preparation directory:
clean_doc:
	(cd doc_INT ; make distclean)

# clean objects on the current host:
clean:
	(cd src/main; make clean)

# clean objects and executable on the current host:
cleaner:
	(cd src/main; make cleaner)

# clean everything:
distclean: clean_root clean_doc clean_examples
	(cd src/main; make distclean)

dist: clean_root clean_examples clean
	(CWD=`pwd`; DIST=`basename $${CWD}`; \
	cd ../ ; \
	tar 	--exclude '*INT' \
		--exclude '*.F90' \
		--exclude '*.inc' \
		--exclude '*.h' \
	-czvf $${DIST}.tar.gz $${DIST} ; \
	mv $${DIST}.tar.gz $${DIST}; \
	cd $${DIST}; ls -ls $${DIST}.tar.gz)

distsrc: distclean
	(CWD=`pwd`; DIST=`basename $${CWD}`; \
	cd ../ ; \
	tar -czvf $${DIST}-src.tar.gz $${DIST} ; \
	mv $${DIST}-src.tar.gz $${DIST}; \
	cd $${DIST}; ls -ls $${DIST}-src.tar.gz)
	
distsrc-uue: distsrc
	(CWD=`pwd`; DIST=`basename $${CWD}`; \
	uuencode $${DIST}-src.tar.gz $${DIST}-src.tar.gz > $${DIST}-src.uue ; \
	ls -lsa $${DIST}-src.uue)
