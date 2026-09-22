import copy,importlib.util,json
from pathlib import Path

def module(name):
 s=importlib.util.spec_from_file_location(name,Path(__file__).parents[1]/name/'cases.py');m=importlib.util.module_from_spec(s);s.loader.exec_module(m);return m

def build():
 integer=dict(id='word',kind='integer',size_bytes=8,alignment_bytes=8,value_bits=64,signed=True,language_type=dict(name='Int',namespace=''))
 void=dict(id='void',kind='void',size_bytes=0,alignment_bytes=1,language_type=dict(name='Void',namespace=''))
 opaque=dict(id='opaque',kind='runtime_value',size_bytes=8,alignment_bytes=8,storage='inline',language_type=dict(name='Opaque',namespace=''),lifecycle=dict(cleanup='none'),cpp_traits=dict(trivially_destructible=True))
 span=dict(id='span',kind='byte_span',size_bytes=16,alignment_bytes=8,language_type=dict(name='Bytes',namespace=''))
 out=[]
 def add(rows,ops=(),accept=True,**kw):
  d=dict(rows=copy.deepcopy(rows),operations=copy.deepcopy(list(ops)),accept=accept,bound=False,foreign=False,project=False,definitions=2,records=0,families=0,calls=0,composed=False);d.update(kw);out.append(d);return d
 add([]);add([integer,void]);add([integer],bound=True);add([integer],bound=True,foreign=True)
 add([opaque],definitions=3,composed=True);add([span],definitions=3,composed=True);add([opaque,span],definitions=4,composed=True)
 record=module('record_import').build()[0]['rows'][0];record.update(size_bytes=16,alignment_bytes=8)
 add([integer,record],records=1,composed=True)
 op=module('callable_import').operation()
 add([integer,void],[op],calls=1);add([integer,void],[op],calls=1,bound=True)
 add([integer,void,opaque,record],[op],calls=1,definitions=3,records=1,composed=True)
 storage=module('storage_import').build()[0];desc=storage['rows'][0];desc.update(size_bytes=16,alignment_bytes=8,storage='inline',language_type=dict(name='Storage',namespace=''),lifecycle=dict(default_construct='ctor',cleanup='none'),cpp_traits=dict(trivially_destructible=True))
 counter=copy.deepcopy(integer);counter['id']='counter'
 addr=dict(id='address',kind='address',size_bytes=8,alignment_bytes=8)
 ctor=module('lifecycle_import').fixture(1)['operations']['op'];ctor['type']='descriptor';ctor['id']='ctor';ctor['symbol']='ctor';ctor['result']['type']='descriptor'
 add([desc,counter,addr,void],storage['operations']+[ctor],families=1,composed=True)
 for original in out.copy():
  if original['rows']:
   bad=copy.deepcopy(original);bad['rows'].append(copy.deepcopy(bad['rows'][0]));bad['accept']=False;out.append(bad)
 bad=copy.deepcopy(op);bad['parameters']=[{}];add([integer,void],[bad],False)
 add([integer],project=True,accept=False)
 add([opaque,dict(opaque,id='second')],accept=False)
 return out
