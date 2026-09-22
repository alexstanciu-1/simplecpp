"""Independent role/arity/production expectations plus retained-provider comparison."""
import copy

def build():
    cases=[]
    def add(mode,row,count,passing,production,want,source='Source',target='Target',source_ns='',target_ns=''):
        cases.append(dict(mode=mode,row=row,count=count,passing=passing,production=production,want=want,source=source,target=target,source_ns=source_ns,target_ns=target_ns))
    for binding in ['byte_literal','echo']:
        for count in [0,1,2]:
            for passing in range(4):
                for production in range(4):
                    valid=count==1 and ((binding=='byte_literal' and passing==3 and production==2) or (binding=='echo' and passing==1 and production==0))
                    add(0,{'language_binding':binding},count,passing,production,binding+':false' if valid else 'error')
    for row,want in [({},'none:false'),({'language_binding':None},'none:false'),({'default_literal':None},'none:false'),({'default_literal':True},'error'),({'language_binding':'unknown'},'error'),({'language_binding':False},'error'),({'default_literal':'false'},'error'),({'language_binding':'byte_literal','default_literal':True},'byte_literal:true'),({'language_binding':'byte_literal','default_literal':False},'byte_literal:false'),({'language_binding':'byte_literal','default_literal':0},'error'),({'language_binding':'echo','default_literal':True},'error')]:
        add(0,row,1,3,2,want)
    for purpose in ['explicit_cast','text','implicit','condition','other',False,None]:
        for count in [0,1,2]:
            for passing in range(4):
                for production in [0,1,2]:
                    want='error'
                    if purpose is None:want='none'
                    elif purpose in ['explicit_cast','text'] and count==1 and passing!=3 and production!=0:want=purpose
                    add(1,{'kind':'free_function','conversion_purpose':purpose},count,passing,production,want)
    for kind in ['construct','const_method','construct_from_bytes']:
        add(1,{'kind':kind,'conversion_purpose':'text'},1,1,2,'error')
    for binding in ['echo','byte_literal','unknown',False]:
        add(1,{'kind':'free_function','conversion_purpose':'text','language_binding':binding},1,1,2,'error')
    add(1,{'kind':'free_function','conversion_purpose':'text','language_binding':None},1,1,2,'text')
    add(1,{'kind':'free_function','conversion_purpose':'text'},1,1,2,'error',source='Same',target='Same')
    add(1,{'kind':'free_function','conversion_purpose':'text'},1,1,2,'text',source='Same',target='Same',source_ns='one',target_ns='two')
    add(1,{},0,0,0,'none')
    return cases
