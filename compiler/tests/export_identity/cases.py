"""Exact tagged keys and independent normalization counterexamples."""
import json

def encode(parts):return json.dumps(parts,separators=(',',':'),ensure_ascii=True)
def build():
    cases=[]
    texts=['','provider','a/b','a\\/b','a/\\b','a\\\\/b','"quoted"','é/漢字','\u2028\u2029','source/provider','😀/𐐀']+[chr(i) for i in range(32)]
    for index,text in enumerate(texts):
        language=['language',text,'ns','I'];provider=['provider',text,'id']
        value=['0','-1','123456789012345678901234567890'][index%3]
        args=[['type',language],['constant',provider,value]]
        family=['family',text,'Vec',args];source=['source',text,[text,'ns','S'],args]
        array=source
        depth=index%8+1
        for level in range(depth):array=['array',array,str(level)]
        cases.append(dict(mode='valid',text=text,value=value,depth=depth,expected=[encode(x) for x in [language,provider,family,source,array]]))
    for value in ['', '-', '+1', '-0', '00', '01', '-01', '1.0','1e3',' 1','1 ','1\n','１２','1\x00']:
        cases.append(dict(mode='invalid',value=value))
    return cases
