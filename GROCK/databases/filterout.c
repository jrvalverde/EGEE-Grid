/*
 * Awful hack of a program by JR.
 * Yet it does the work it is intended to do *for us*
 *
 *  OK: some docs: this works as a filter. Read a fasta file from stdin
 * and write a new fasta file on stdout with the following changes:
 *  	- Nucleic acids are removed
 *  	- Any entry that has no PDB counterpart is removed
 *  	- Entries stating chain A where PDB has no chain A are reverted
 *  	  to default (all atoms)
 *
 *  The second condition requires reading the PDB file, hence results in
 *  this program becoming terribly slow. Thanks goodness we need run it
 *  anyway as the first step in the database building process and only
 *  then. Yet, it would be probably better if instead of grepping we read
 *  the file ourselves and stopped at the first appearance of a chain ATOM
 *  or HETATM (regexp '"^[AH][TE][OT][MA].................A').
 *
 *  BUGS:
 *  	Some problematic structures containing weird residues are not
 *  	filtered out by this program. Something should be done about it.
 *  	An example is 1A7Z.
 *
 *  (C) J. R. Valverde, 2006
 */

#include <ctype.h>
#include <fcntl.h>
#include <regex.h>
#include <stdio.h>
#include <stdlib.h>
#include <strings.h>
#include <sys/resource.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <sys/time.h>
#include <sys/wait.h>


main()
{
    char line[8192];
    char cmd[8192];
    char *template = "/data/gen/pdb/pdbXXXX.ent";
    char *pattern = "^[AH][TE][OT][MA].................A";
    regex_t reg;
    char strunam[8192], string[8192];
    int status, ok;
    FILE *strufp;
    
    strcpy(strunam, template);
    
    while (fgets(line, 8192, stdin) != NULL) {
JMP:   	if (line[0] == '>') {
	    /* new entry */
    	    /* first check it is not a nucleic acid */
	    if (line[16] == 'n') {  /* skip entry */
    	    	while (fgets(line, 8192, stdin) != NULL)
		    if (line[0] == '>') goto JMP;
	    }
	    /* check the file exists */
	    strunam[17] = tolower(line[5]);
	    strunam[18] = tolower(line[6]);
	    strunam[19] = tolower(line[7]);
	    strunam[20] = tolower(line[8]);
	    /* if file does not exist skip entry */
	    if ((strufp = fopen(strunam, "r")) == NULL) {
	    	while (fgets(line, 8192, stdin) != NULL)
		    if (line[0] == '>') goto JMP;
	    } else {
	    	/* check chain exists: some times the entry states chain A
		   when there is only one chain, but convpdb.pl will choke
		   on these */
		if ( line[10] == 'A') {
#ifdef USE_GREP
		    snprintf(cmd, 8192,
		    	    "grep '%s' %s > /dev/null 2>&1",
			    pattern,
		    	    strunam);
		    status = system(cmd);
		    if (WEXITSTATUS(status) != 0) {
		    	/* the A chain does not exist: clear it */
			line[10] = ' ';
		    }
#else
    	    	    regcomp(&reg, pattern, 
		    	    REG_EXTENDED | REG_NOSUB | REG_NEWLINE);
		    ok = 0;
		    while (!feof(strufp)) {
		    	fgets(string, 8192, strufp);
		    	status = regexec(&reg, string, (size_t) 0, NULL, 0);
			if (status == 0) {
			    ok = 1;
			    break;
			}
		    }
		    regfree(&reg);
		    if (ok == 0) line[10] = ' ';
#endif
		}
	    	fclose(strufp);
	    	fputs(line, stdout);
	    }
	} else {
	    fputs(line, stdout);
	}
    }
}
