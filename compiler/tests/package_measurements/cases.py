"""Independent physical metadata boundary expectations; no language exposure in this slice."""
def build():
    out=[]
    def add(row,accept,kind=0,size=None,alignment=None,bits=0,signed=False):
        out.append(dict(row=row,accept=accept,kind=kind,size=row.get('size_bytes',0) if size is None else size,alignment=row.get('alignment_bytes',0) if alignment is None else alignment,bits=bits,signed=signed))
    for spelling,kind in [('integer',0),('address',1),('byte_span',2),('void',3),('runtime_value',4),('value_record',5)]:
        base=dict(id='type/id',kind=spelling,size_bytes=8,alignment_bytes=8)
        if spelling=='integer':base.update(value_bits=64,signed=True)
        if spelling=='runtime_value':base['storage']='inline'
        add(base,True,kind,size=0 if kind==3 else 8,bits=64 if kind==0 else 0,signed=kind==0)
        for key in ['id','kind','alignment_bytes']:
            row=dict(base);row.pop(key);add(row,False)
        for alignment in [0,-1,3,6,9223372036854775807]:
            add(dict(base,alignment_bytes=alignment),False)
        if kind!=3:
            for size in [0,-1,7,'8',8.0,None]:add(dict(base,size_bytes=size),False)
        else:
            for size in [-1,'ignored',None]:add(dict(base,size_bytes=size),True,kind,size=0)
            row=dict(base);row.pop('size_bytes');add(row,True,kind,size=0)
        if kind==4:
            for storage in ['pointer','',None]:add(dict(base,storage=storage),False)
    integer=dict(id='i',kind='integer',size_bytes=1,alignment_bytes=1,value_bits=8,signed=False)
    add(integer,True,bits=8)
    for key,values in [('value_bits',[0,-1,9,8.0,'8',None]),('signed',[0,1,'false',None])]:
        for value in values:add(dict(integer,**{key:value}),False)
    add(dict(integer,size_bytes=1152921504606846975,value_bits=9223372036854775800),True,bits=9223372036854775800)
    add(dict(integer,size_bytes=1152921504606846975,value_bits=9223372036854775801),False)
    add(dict(integer,size_bytes=1152921504606846976,value_bits=9223372036854775807),True,bits=9223372036854775807)
    add(dict(integer,size_bytes=4611686018427387904,alignment_bytes=4611686018427387904),True,bits=8)
    return out
