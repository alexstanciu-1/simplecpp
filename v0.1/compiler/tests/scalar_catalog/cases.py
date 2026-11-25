"""Invalid schema/type variants derived from the retained scalar language catalog."""
import copy
import json

def invalid_cases(base):
    out=[]
    def edit(path,value,remove=False):
        row=copy.deepcopy(base);parent=row
        for key in path[:-1]:parent=parent[key]
        if remove:del parent[path[-1]]
        else:parent[path[-1]]=value
        out.append(json.dumps(row))
    out.extend(['null','[]','{}','{'])
    for value in [0,2,True,1.0,'1',None]:edit(['schema_version'],value)
    for field in ['provider','representation_scope','types','literal_types','entry_return_type']:edit([field],None,True)
    edit(['unknown'],True);edit(['provider'],'');edit(['representation_scope'],'native');edit(['types'],{})
    edit(['types'],[]);edit(['types',1,'bit_width'],0);edit(['types',1,'bit_width'],-1)
    for value in [True,'64',64.0,9223372036854775808]:edit(['types',1,'bit_width'],value)
    for value in [0,'true',None]:edit(['types',1,'signed'],value)
    edit(['types',1,'name'],'');edit(['types',1,'namespace'],False)
    edit(['types',1,'kind'],'pointer');edit(['types',1,'format'],'ieee_binary64')
    edit(['types',1,'integer_family'],'');edit(['types',1,'addition'],'saturating');edit(['types',1,'comparison'],'arbitrary')
    edit(['types',1,'struct_field'],1);edit(['types',1,'lifetime'],None)
    edit(['types',1,'lifetime','copy'],'construct');edit(['types',1,'lifetime','cleanup'],'destroy')
    edit(['types',1,'lifetime','unknown'],True);edit(['types',0,'lifetime'],{'copy':'value','cleanup':'none'})
    edit(['types',3,'format'],'float');edit(['types',3,'signed'],False)
    edit(['entry_return_type','name'],'missing');edit(['entry_return_type','name'],'float')
    edit(['literal_types','integer','name'],'void');edit(['literal_types','boolean','name'],'int')
    edit(['types',6,'signed'],True);edit(['literal_types','unexpected'],{})
    row=copy.deepcopy(base);row['types'].append(copy.deepcopy(row['types'][1]));out.append(json.dumps(row))
    return out
