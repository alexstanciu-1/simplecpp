"""Accepted owner identity and capability rejection, including exact v1 metadata shapes."""
import copy

def build():
    base=dict(row=dict(id='local',kind='runtime_value',storage='inline',size_bytes=8,alignment_bytes=8,native_import={'provider':'accepted','id':'foreign'},lifecycle={},cpp_traits={'copy_constructible':True,'copy_assignable':True}),mode=0,accept=True,missing='')
    out=[base]
    def change(key,value,accept=False):
        c=copy.deepcopy(base);c['row'][key]=value;c['accept']=accept;out.append(c)
    for key,value in [('size_bytes',16),('alignment_bytes',4),('kind','address'),('lifecycle',{'copy_construct':'new'}),('lifecycle',None),('native_import',None),('native_import',{'provider':'other','id':'foreign'}),('native_import',{'provider':'accepted','id':'other'}),('native_import',{'provider':'accepted','id':'foreign','extra':1}),('native_import',{'id':'foreign','provider':'accepted'}),('native_import',{'provider':1,'id':'foreign'})]:change(key,value)
    for key in ['language_type','source_payload','resource','storage_family','struct_field']:
        change(key,False);change(key,None,key != 'resource')
    change('lifecycle',[],True)
    for key in ['native_import','lifecycle','cpp_traits']:
        c=copy.deepcopy(base);del c['row'][key];c['accept']=False;out.append(c)
    for key in ['copy_constructible','copy_assignable']:
        for value in [False,1,'true',None]:
            c=copy.deepcopy(base);c['row']['cpp_traits'][key]=value;c['accept']=False;out.append(c)
    for mode,missing in [(1,'copy construction'),(2,'copy assignment'),(3,''),(4,'value lifetime'),(5,''),(6,'')]:
        c=copy.deepcopy(base);c.update(mode=mode,accept=False,missing=missing);out.append(c)
    return out
