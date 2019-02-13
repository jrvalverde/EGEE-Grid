#!/usr/bin/env python2
import sys
import getopt
import os
import shutil
from HTMLReport import HTMLReport,HTMLMarkup

def usage():
    print 'make_html_report -l CElist -d testNo -o outputsdir -f <files to include>'
    

opts, rest = getopt.getopt(sys.argv[1:], "l:d:f:o:")

for (opt, arg) in opts:
    if (opt == "-f"):
        files = arg.split()
    if (opt == "-l"):
        celist_file = file(arg).readlines()
    if (opt == "-d"):
        testNo = arg
    if (opt == "-o"):
        outputsdir = arg

def parse(line):
    if len(line) == 0 or line[0]=='#': return None
    return line.strip()

celist = map(parse,celist_file) # parse lines and mark comments as None
celist = filter(lambda x: x,celist) # remove all Nones

tdir = testNo

print 'USING:',tdir

report = HTMLReport()
report.beginDocument('LCG2 worker node capability scan: '+testNo)

testscript = 'test_wn_capabilities'
shutil.copy(testscript,tdir)

report.addParagraph(HTMLMarkup('capability testing script', link=testscript))

i = -1
for ce in celist:
    i += 1
    print 'analysing output of',i,'',ce
    testcasename = "test_x_%d"%(i,)
    fo = "%s/%s"%(tdir,testcasename,)
#    fe = "%s/%s/%s"%(tdir,outputsdir,testcasename,)
    fe = "%s/%s"%(outputsdir,testcasename,)
    outdirdest = fe+'.outputdir'

    try:
        status = None
        for l in  file(fo+'.tmp3').readlines():
            idx = l.find('Current Status')
            if idx != -1:
                status = l.split(':')[1].strip()
                break

        capslink = ''
        celink = ce
        str = ''
        for ifile in files:
            if os.path.exists('%s/%s' % (outdirdest,ifile)):
                capslink = HTMLMarkup(ifile,link='%s/%s.outputdir/%s'%(outputsdir,testcasename,ifile))
                str = str + ' ' +capslink
                celink = HTMLMarkup(ce,underline=1)
                
        
        report.addParagraph('%d %s %s %s' %(i,HTMLMarkup(status,bold=1),str,celink))


    except IOError: # FILE NOT FOUND
        report.addParagraph('%d %s %s %s' %(i,HTMLMarkup('*error*',bold=1),'',ce))

    

report.endDocument()

repfile = '%s/report.html'%(tdir,)

file(repfile,'w').write(report.buffer())
print 'created HTML report:', repfile


    
