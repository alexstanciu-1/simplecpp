import json

def build():
    cases=[]
    for role in [1,2,3,5]:
        for change in ['valid','missing_impl','missing_import','stale_capability','wrong_role','impl_name','impl_cc','impl_return','impl_extension','impl_parameter','impl_count','impl_param_extension','import_name','import_cc','import_return','import_extension','import_parameter','import_count','import_param_extension','import_operation','stale_operation','unavailable_abi','missing_role','extra_role','reordered']:
            cases.append(dict(role=role,change=change,layout='e-p:64:64',accept=change in ['valid','reordered']))
    for layout,accept in [('A0',True),('e-A00-p:64:64',True),('e-A1',False),('A0002',False),('Ax-A0',True),('A-A2',False),('A0-A1',True),('A1-A0',False),('A99999999999999999999999999999999999',False),('a1',True),('A1x',True),('A0\n-A2',False)]:
        cases.append(dict(role=3,change='valid',layout=layout,accept=accept))
    for text in ['a_b', 'a-X', 'quote"slash\\', 'é', '漢字', 'line\nnext', 'AZaz09', '_x5F_']:
        for role in [1,3]:
            cases.append(dict(role=role,change='valid',layout='e',accept=True,text=text))
    names={1:'default_construct',2:'destroy',3:'copy_construct',5:'copy_assign'}
    encode=lambda s: ''.join(chr(b) if (48<=b<=57 or 65<=b<=90 or 97<=b<=122) else '__' if b==95 else '_x%02X_'%b for b in s.encode())
    for case in cases:
        case.setdefault('text','R')
        key=json.dumps(['source','p',['r.phs','',case['text']],[]],separators=(',',':'),ensure_ascii=True)
        signature='ccc:void(ptr,ptr)' if case['role'] in [3,5] else 'ccc:void(ptr)'
        case['symbol']='scpp_source_'+'_X_'.join(map(encode,['inline_source_payload_v1',key,names[case['role']],signature]))
    return cases
