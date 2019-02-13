#class for producing a table of contents TOC
class TableOfContents:

        def __init__(self, htmlClass):
                self.Title = '<h2>Table of Contents </h2><a name="TOC"></a><ul>'
                self.buff = self.Title  #add the title first
                self.htmlClass = htmlClass


        #called when TOC will appear on report, where it is called that is where the TOC will be
        def insert_TOC(self, report):
                report += self.buff  #put the current TOC buffer in the main report, should just contain <self.Title>
                return report


        #adds another entry, indent_level = where it should be indented to (Sections and Sub sections)
        def add_TOC_entry(self, indent_level, title):
                markup = self.htmlClass.markupClass()
                anchor = markup(title,link='#'+title)

                #add the link to the current TOC buffer
                if indent_level == 1:
                        self.buff= self.buff+ '<li>'+anchor+'</li>'
                elif indent_level == 2:
                        self.buff= self.buff+ '<ul><li>'+anchor+'</li></ul>'
                elif indent_level == 3:
                        self.buff= self.buff+ '<ul><ul><li>'+anchor+'</li></ul></ul>'
                else:
                        print "Table of Contents may be incomplete due to index levels"


        #called to replace first <empty> Table of Contents with the full version
        def finalise_report(self, report):
                self.buff += '</ul>'

                #find TOC title in current report and replace full TOC
                report = report.replace(self.Title,self.buff, 1)
                return report

def HTMLMarkup(text,link=None,bold=None,tt=None,italic=None,underline=None,**kwds):
    buf = text

    # generate prefix
    if link:
        buf = '<a href= "'+link+'">'+buf
    if bold:
        buf = '<b>'+buf
    if tt:
        buf = '<tt>'+buf
    if italic:
        buf = '<i>'+buf        

    if underline:
        buf = '<u>'+buf

    # generate suffix

    if underline:
        buf = buf+'</u>'
    if italic:
        buf = buf+'</i>'
    if tt:
        buf = buf+'</tt>'
    if bold:
        buf = buf + '</b>'
    if link:
        buf = buf+'</a>'
        
    return buf

class ReportBaseClass:
    def __init__(self):
        self.endl = '\n'
        
    def insertRows(self,seq):
        if type(seq) == types.DictionaryType:
            rows = zip(seq.keys(),seq.values())
            rows.sort()
            for x in rows: self.addRow(x)
            return

        if type(seq) == types.ListType:
            for x in seq: self.addRow(x)
            return

        raise Exception("sequence type expected not "+str(type(seq)))

    def PlainTextMarkup(text,**kwds):
        return text


class HTMLReport(ReportBaseClass):
    def __init__(self, section_numbering=1):
        self.ostr = ""
        self.endl = '<br>'
        self.section_num = 0
        self.subsection_num = 0
        self.subsubsection_num = 0
        self.section_numbering = section_numbering
        self.TOC = TableOfContents(self)

    def markupClass(self):
        return HTMLMarkup

    def addLine(self,text):
        self.ostr += text+ '<br>'

    def beginDocument(self,title,header=None):
        
        if not header:
            header = '''
<!doctype html public "-//w3c//dtd html 4.0 transitional//en">
<html>
<head>
   <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
   <title>''' + title + '''</title>
</head>
<body bgcolor="#FFFFFF" link="#1076A6" vlink="#1076A6" alink="#FF0000">
<h1><img src="http://lcg.web.cern.ch/LCG/peb/arda/images/LCGlogo.jpg" width="75" height="75"/> ''' + title +" </h1>"

        self.ostr = header

    def endDocument(self):
        self.ostr += '''

<p>
<hr>
Based on QA tools developed in LCG SPI Project <br>
<img src="http://spi.cern.ch/spilogo.gif"/> 
</body>
</html>
'''
        
        self.ostr = self.TOC.finalise_report(self.ostr)


    def beginTable(self,name,cols):
        self.ostr += '\n<table BORDER COLS='+str(len(cols))+'>'#' WIDTH="100%">'
        if reduce(lambda x,y: x+y, cols) != '':
            self.addRow(cols);

    def endTable(self):
        self.ostr += "\n</table>"

    def addRow(self, cols):
        cols = flatten(cols) # NEED TO FLATTEN COLS STRUCTURE HERE!
        self.ostr += "\n<tr>"
        for c in cols:
            self.ostr += "\n<td> "+str(c)+" </td>"
        self.ostr += "\n</tr>"

    def addParagraph(self,text):
        self.ostr += "\n<p> "+str(text)+ " </p>"

    #calls the private def __addSection()
    def addSection(self,text):
        self.__addSection(text, 1)
    
    #calls the private def __addSection()
    def addSubsection(self,text):
       self.__addSection(text, 2)
       
    #calls the private def __addSection()
    def addSubSubsection(self,text):
        self.__addSection(text, 3)

    #adds sections depending on where the user wants to indent to
    #ie if indent_level is 1 = x.  OR if indent_level is 2 = x.x.
    def __addSection(self, text, indent_level):
        number = ""

        if(indent_level == 1):

                self.section_num += 1
                number=str(self.section_num)
                
                #reset subsection num for this section
                self.subsection_num = 0

                #if the first section add TOC just before
                if self.section_num == 1:
                        self.ostr = self.TOC.insert_TOC(self.ostr)
                        
                self.ostr += "\n<h1> "+number+". "+str(text)+ " </h1>"

        if(indent_level == 2):

                #reset subsection num for this section
                self.subsubsection_num = 0


                if self.section_numbering:
                        self.subsection_num += 1
                        number=str(self.section_num)+"."+str(self.subsection_num)


                        self.ostr += "\n<h3> "+number+". "+str(text)+ " </h3>"


        if(indent_level == 3):

                if self.section_numbering:
                        self.subsubsection_num += 1
                        number=str(self.section_num)+"."+str(self.subsection_num)+"."+str(self.subsubsection_num)


                        self.ostr += "\n<h4> "+number+". "+str(text)+ " </h4>"

        #table of contents entry
        toc_entry = number+" "+str(text)

        #place bookmark and link back to the top
        self.ostr += '<a name="'+toc_entry+'"></a> \n'
        self.ostr += '<a href= "#TOC">Back to Table of Contents</a></br>'

        #add entry to Table of Contents
        self.TOC.add_TOC_entry(indent_level, toc_entry)


    def addImage(self,filename):
        self.ostr += "<img src="+quote(filenaQme)+" border = 0/>"

##    def addRawText(self,text):
##        self.ostr += text


    def buffer(self):
        return self.ostr
