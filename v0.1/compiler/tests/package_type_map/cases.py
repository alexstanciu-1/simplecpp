"""Independent routing, identity and rejection cases for complete package maps."""
import copy
import json

def build():
    key=json.dumps(['source','p',['r.phs','','R'],[]],separators=(',',':'))
    integer=dict(id='T',kind='integer',size_bytes=8,alignment_bytes=8,value_bits=64,signed=True,language_type={'name':'Int','namespace':''})
    source=dict(id='S',kind='runtime_value',size_bytes=8,alignment_bytes=8,storage='inline',source_payload=key,lifecycle={})
    native=dict(id='N',kind='runtime_value',size_bytes=8,alignment_bytes=8,storage='inline',native_import={'provider':'accepted','id':'foreign'},lifecycle={},cpp_traits={'copy_constructible':True,'copy_assignable':True})
    cases=[]
    def add(name,rows,accept=True,types=None,sources=None,imports=None,operations=None):
        cases.append(dict(name=name,rows=copy.deepcopy(rows),accept=accept,types=types or {},sources=sources or [],imports=imports or [],operations=operations or []))
    add('empty',[])
    add('catalog identity',[integer])
    add('unexposed',[dict(integer,language_type=None)])
    add('compiler binding overrides producer',[dict(integer,language_type='Unknown')],types={'T':'Int'})
    add('source exact identity',[source],sources=['S'])
    add('native exact identity',[native],imports=['N'])
    add('all owners',[integer,source,native],sources=['S'],imports=['N'])
    add('duplicate types',[integer,integer],False)
    add('duplicate operations',[],False,operations=[{'id':'copy'},{'id':'copy'}])
    add('unknown type binding',[integer],False,types={'absent':'Int'})
    add('unknown source binding',[integer],False,sources=['absent'])
    add('unknown native binding',[integer],False,imports=['absent'])
    add('invalid reference kind',[integer],False,types={'T':'@parameter'})
    add('source missing binding',[source],False)
    add('native missing binding',[native],False)
    add('source and type conflict',[source],False,types={'S':'Int'},sources=['S'])
    add('source and native conflict',[source],False,sources=['S'],imports=['S'])
    add('native and type conflict',[native],False,types={'N':'Int'},imports=['N'])
    for field,value in [('source_payload','wrong'),('source_payload',None),('size_bytes',16),('alignment_bytes',4),('kind','value_record'),('lifecycle',None),('lifecycle',{'destroy':'x'}),('language_type','R'),('native_import',{}),('resource',None),('storage_family','x'),('struct_field',False)]:
        add('source reject '+field+repr(value),[integer,dict(source,**{field:value})],False,sources=['S'])
    for field in ['language_type','native_import','storage_family','struct_field']:
        add('source nullable '+field,[dict(source,**{field:None})],sources=['S'])
    add('source empty list lifecycle',[dict(source,lifecycle=[])],sources=['S'])
    add('late source failure after native',[native,dict(source,size_bytes=16)],False,sources=['S'],imports=['N'])
    add('unexposed resource still validated',[dict(integer,language_type=None,resource=None)],False)
    add('late unknown binding',[integer,source,native],False,types={'absent':'Int'},sources=['S'],imports=['N'])
    return cases
