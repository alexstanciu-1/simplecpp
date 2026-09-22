"""Language binding and explicit opaque/span permissions, independent of native/source imports."""
import copy

def build():
    cases=[]
    def add(kind,name,accept=True,**fields):
        row=dict(id='T',kind=kind,size_bytes=8,alignment_bytes=8)
        if kind=='integer':row.update(value_bits=64,signed=True)
        if kind=='void':row['size_bytes']=0
        if kind=='runtime_value':row.update(storage='inline',lifecycle={'cleanup':'none'},cpp_traits={'trivially_destructible':True})
        row.update(fields)
        cases.append(dict(row=row,name=name,namespace='',accept=accept))
    add('integer','Int');add('integer','UInt',signed=False)
    add('integer','Int',False,signed=False);add('integer','UInt',False)
    add('integer','Int',False,value_bits=32);add('integer','Missing',False);add('integer','Void',False)
    add('address','Int',False);add('address','Missing',False)
    add('void','Void');add('void','Int',False);add('void','Missing',False)
    for size in [None,1,'0',0.0]:add('void','Void',False,size_bytes=size)
    add('value_record','Record');add('value_record','Int',False)
    add('byte_span','Bytes');add('byte_span','Int',False)
    add('runtime_value','Opaque');add('runtime_value','Field',struct_field=True)
    add('runtime_value','Owner',resource='allocation')
    add('runtime_value','Int',False)
    add('runtime_value','Opaque',False,struct_field=None)
    add('runtime_value','Opaque',False,struct_field=1)
    add('runtime_value','Opaque',False,cpp_traits={'trivially_destructible':False})
    add('runtime_value','Opaque',False,resource='other')
    add('integer','Int',False,native_import={'provider':'other','id':'x'})
    add('integer','Int',False,source_payload='x')
    add('integer','Int',native_import=None,source_payload=None)
    for kind,name in [('byte_span','Bytes'),('runtime_value','Opaque'),('value_record','Record')]:
        add(kind,name);cases[-1]['namespace']='private_scope'
    for kind,name in [('integer','Int'),('byte_span','Bytes'),('void','Void'),('value_record','Record')]:
        add(kind,name,False,resource='allocation')
    return cases
