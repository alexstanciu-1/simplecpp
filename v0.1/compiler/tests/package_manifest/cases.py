import copy

def build():
    digest='0'*64
    base=dict(name='ordinary',project=False,accept=True,
              pointer=dict(schema_version=1,input_key='key',manifest='package/manifest.json',manifest_sha256=digest),
              manifest=dict(schema_version=1,provider='p',input_key='key',target=dict(triple='t',data_layout='d'),
                            validation=dict(defined_abi_signatures=True,native_link_no_undefined=True),
                            artifacts={'metadata.json':digest,'runtime.bc':digest,'runtime.ll':digest,'full.bc':digest,'thin.bc':digest},
                            metadata='metadata.json',modules=[dict(format='llvm_bitcode',path='runtime.bc'),dict(format='llvm_text',path='runtime.ll'),dict(format='full_lto_bitcode',path='full.bc'),dict(format='thin_lto_bitcode',path='thin.bc')],
                            link_driver=dict(executable='/clang',arguments=['--driver-mode=g++','--target=t']),inputs=dict(clang={'/clang':digest})),
              metadata=dict(schema_version=1,provider='p',target=dict(triple='t',data_layout='d'),types=[{'id':'T'}],operations=[{'id':'op'}]))
    cases=[copy.deepcopy(base)]
    def changed(name,path,value,accept=False):
        case=copy.deepcopy(base);case.update(name=name,accept=accept);node=case
        for key in path[:-1]:node=node[key]
        node[path[-1]]=value;cases.append(case);return case
    for path,values in [(['pointer','schema_version'],[0,1.0,'1']),(['pointer','manifest'],['manifest.json','../manifest.json']),
                        (['pointer','manifest_sha256'],['0'*63,'A'*64,'g'*64,None]),(['pointer','input_key'],['other','',None]),
                        (['manifest','input_key'],['other',None]),(['manifest','schema_version'],[2,1.0]),(['manifest','provider'],['',None]),
                        (['manifest','validation','defined_abi_signatures'],[False,1]),(['manifest','validation','native_link_no_undefined'],[False,1]),
                        (['manifest','module_kind'],['project','other']),(['manifest','artifacts'],[{},[],{'metadata.json':'bad'}]),
                        (['manifest','metadata'],['absent','../metadata.json','']),(['manifest','modules'],[[],{},[dict(format='llvm_text',path='runtime.ll')], [dict(format='unknown',path='runtime.bc')], [dict(format='llvm_bitcode',path='absent')]]),
                        (['manifest','target'],[dict(triple='',data_layout='d'),dict(triple='t',data_layout=''),dict(triple='t',data_layout='d',extra=True)]),
                        (['manifest','link_driver','executable'],['',None]),(['manifest','link_driver','arguments'],[[],['--target=t','--driver-mode=g++'],['--driver-mode=g++','--target=other'],{},['--driver-mode=g++','--target=t','extra']]),
                        (['manifest','inputs','clang'],[{},[],{'/clang':None},{'':digest}]),
                        (['metadata','schema_version'],[2,1.0]),(['metadata','provider'],['other']),(['metadata','target'],[dict(triple='other',data_layout='d'),dict(triple='t',data_layout='other')]),
                        (['metadata','types'],[{},[1]]),(['metadata','operations'],[{},[None]])]:
        for value in values:changed('.'.join(path)+repr(value),path,value)
    for name in ['../escape','dir/file','dir\\file','.', '..','bad\x00file','']:
        artifacts=copy.deepcopy(base['manifest']['artifacts']);artifacts[name]=digest;changed('artifact '+repr(name),['manifest','artifacts'],artifacts)
    changed('duplicate module', ['manifest','modules'],base['manifest']['modules']+[dict(format='llvm_bitcode',path='runtime.bc')])
    changed('default module kind null',['manifest','module_kind'],None,True)
    changed('target key order is not semantic',['metadata','target'],dict(data_layout='d',triple='t'),True)
    changed('numeric artifact name stays a string',['manifest','artifacts'],dict(base['manifest']['artifacts'],**{'0':digest}),True)
    project=copy.deepcopy(base);project.update(name='project',project=True);project['manifest']['module_kind']='project';project['manifest']['validation'].update(native_link_no_undefined=False,source_imports_validated=True);project['manifest']['artifacts']['project.json']=digest;cases.append(project)
    for field in ['receipt','source validation','link validation','module kind']:
        case=copy.deepcopy(project);case.update(name='project missing '+field,accept=False)
        if field=='receipt':del case['manifest']['artifacts']['project.json']
        elif field=='source validation':case['manifest']['validation']['source_imports_validated']=False
        elif field=='link validation':case['manifest']['validation']['native_link_no_undefined']=True
        else:case['manifest']['module_kind']='runtime'
        cases.append(case)
    for owner,key in [('pointer','input_key'),('manifest','input_key')]:
        case=copy.deepcopy(base);case.update(name='missing '+owner+'.'+key,accept=False);del case[owner][key];cases.append(case)
    return cases
