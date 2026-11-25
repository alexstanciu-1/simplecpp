def build():
    types=['int','int32','uint32','uint8','bool','float','void']
    out=[]
    for source in types:
        for destination in types:
            for purpose in range(4):
                form=0; primitive=0; target=0
                if source!='void' and destination!='void' and purpose!=2:
                    if source==destination:form=1
                    elif purpose==0 and (source,destination) in [('int32','int'),('uint8','uint32')]:form=2;primitive=1
                    elif (source,destination)==('int32','uint8'):
                        if purpose==1:form=3;target=10000
                        if purpose==3:form=3;target=10001
                out.append(dict(source=source,destination=destination,purpose=purpose,form=form,primitive=primitive,target=target))
    return out
