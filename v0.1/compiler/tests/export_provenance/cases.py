"""Project identity/path normalization and explicit backend provenance."""
def build():
    cases=[]
    def project(source,expected,accept=True,output='/out',key='project',byte=-1,key_byte=-1):
        cases.append(dict(mode='project',source=source,expected_source=expected,output=output,key=key,byte=byte,key_byte=key_byte,accept=accept))
    for source,expected in [('/','/'),('//','/'),('/./','/'),('/a//b/./','/a/b'),('/a b/.../','/a b/...'),('/a\\b','/a\\b'),('/a/../b',''),('relative',''),('',''),('C:/x',''),('/..','')]:
        project(source,expected,bool(expected))
    project('/src','/src',False,key='')
    project('/src','/src',False,output='relative')
    project('/src','/src',False,output='/a/../b')
    project('/src','/src',False,key_byte=0)
    # Exercise arbitrary path bytes without trying to represent invalid UTF-8 in JSON.
    for byte in range(256):
        expected='/p/'
        if byte==47:expected='/p'  # appended slash normalizes away
        if byte==46:expected='/p'  # appended dot segment normalizes away
        project('//p//./',expected,byte!=0,byte=byte)
    base=['backend','target','layout','','','abi','runtime']
    cases.append(dict(mode='backend',values=base,accept=True))
    for index in range(7):
        values=list(base);values[index]='' if index in [0,1,2,5,6] else 'explicit'
        cases.append(dict(mode='backend',values=values,accept=index in [3,4]))
    return cases
