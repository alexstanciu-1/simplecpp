"""Native-record schema acceptance, field extents, and publication rejection cases."""
import copy

def build():
    row=dict(id='row',kind='value_record',storage='inline',construction='zero',copy='value',cleanup='none',
        validation=dict(complete_public_fields=True,plain_value_record=True),declaration=dict(field_offsets_exported=True),
        cpp_traits=dict(trivially_copyable=True,trivially_destructible=True,copy_constructible=True),
        language_type=dict(name='Pair',namespace=''),fields=[dict(name='first',type='word',writable=True,offset_bytes=0),dict(name='last',type='word',writable=False,offset_bytes=8)])
    base=dict(rows=[row],target=dict(triple='test-target',data_layout='e-p:64:64'),scalar_mode=0,size=16,alignment=8,collision=0,accept=True)
    cases=[base]
    for path,value in [(('storage',),'heap'),(('construction',),'default'),(('copy',),'construct'),(('cleanup',),'destroy'),
        (('validation','complete_public_fields'),False),(('validation','plain_value_record'),False),(('declaration','field_offsets_exported'),False),
        (('cpp_traits','trivially_copyable'),False),(('cpp_traits','trivially_destructible'),False),(('cpp_traits','copy_constructible'),False),
        (('language_type','name'),'9bad'),(('language_type','namespace'),'other'),(('id',),'missing'),
        (('fields',0,'name'),''),(('fields',0,'name'),'é'),(('fields',1,'name'),'first'),(('fields',0,'type'),'missing'),
        (('fields',0,'writable'),0),(('fields',0,'offset_bytes'),-1),(('fields',0,'offset_bytes'),0.0),
        (('fields',1,'offset_bytes'),3),(('fields',1,'offset_bytes'),13),(('fields',),[]),(('fields',),{}),(('fields',),[[]])]:
        bad=copy.deepcopy(base);cursor=bad['rows'][0]
        for key in path[:-1]:cursor=cursor[key]
        cursor[path[-1]]=value;bad['accept']=False;cases.append(bad)
    for mode in range(1,5):
        bad=copy.deepcopy(base);bad['scalar_mode']=mode;bad['accept']=False;cases.append(bad)
    for size,alignment in [(0,8),(15,8),(16,3)]:
        bad=copy.deepcopy(base);bad.update(size=size,alignment=alignment,accept=False);cases.append(bad)
    for key in ['triple','data_layout']:
        bad=copy.deepcopy(base);bad['target'][key]='';bad['accept']=False;cases.append(bad)
    for collision in [1,2]:
        bad=copy.deepcopy(base);bad.update(collision=collision,accept=False);cases.append(bad)
    # Failure after normalizing one record must not update the caller's original type map.
    bad=copy.deepcopy(base);second=copy.deepcopy(row);second['fields'][1]['offset_bytes']=3;bad['rows'].append(second);bad['accept']=False;cases.append(bad)
    return cases
