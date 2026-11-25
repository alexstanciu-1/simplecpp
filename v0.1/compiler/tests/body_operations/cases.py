def build():
    types=['int','uint32','int32','uint8','bool','float','void']
    cases=[]
    for left in types:
        for right in types:
            for operation in ['addition','less_than','unsupported']:
                for boolean in [True,False]:
                    entry=''
                    if left==right and left in ['int','int32','uint32','uint8']:
                        if operation=='addition':entry='add_wrap'
                        if operation=='less_than' and boolean:entry='less_signed' if left in ['int','int32'] else 'less_unsigned'
                    cases.append(dict(left=left,right=right,operation=operation,boolean=boolean,entry=entry))
    return cases
