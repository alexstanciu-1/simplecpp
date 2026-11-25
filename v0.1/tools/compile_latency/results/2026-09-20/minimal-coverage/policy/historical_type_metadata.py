"""Project bounded publication facts from exact generated historical definitions.

The only alias is verified by Git commit b085d952. Preserve original definitions;
this is experimental metadata extraction, not production semantic inference.
"""
import copy,hashlib,json,re
from pathlib import Path
from callable_surface import CLASS

def project(generated, destination):
    base=json.loads(Path(__file__).with_name('type_publication_metadata.json').read_text());result=copy.deepcopy(base)
    aliases={'EmissionLLVMModuleCompositionSnapshot':'EmissionLLVMCompositeTextSnapshot'}
    evidence={}
    for role,spec in result['types'].items():
        source=(generated/spec['owner']).read_text();kind=spec.get('kind','class');pattern=CLASS if kind=='class' else re.compile(r'^struct (\w+) \{\n([\s\S]*?)^};',re.M)
        definitions={m[1]:m for m in pattern.finditer(source)}
        name=role if role in definitions else aliases.get(role,role);spec['name']=name
        if name not in definitions:
            evidence[role]={'present':False,'name':name};continue
        body=definitions[name][2];fields={}
        helpers={f'{name}* operator->() {{ return this; }}',f'const {name}* operator->() const {{ return this; }}'}
        for line in body.splitlines():
            if not line.strip() or line.strip() in helpers or line.strip().startswith(('static const void* __scpp_static_token()', 'static bool_t __scpp_static_accepts(')):continue
            m=re.fullmatch(r'\t(.+?) (\w+)(?: = [^\n]+)?;',line)
            if not m or m[2] in fields:raise ValueError('Unsupported historical field definition: '+line)
            fields[m[2]]=m[1]
        spec['fields']=fields;spec.pop('optional_fields',None)
        spec['source_header_sha256']=hashlib.sha256(source.encode()).hexdigest()
        evidence[role]={'present':True,'name':name,'fields':fields,'definition_sha256':hashlib.sha256(definitions[name][0].encode()).hexdigest()}
    names={role:spec['name'] for role,spec in result['types'].items()}
    for spec in result['types'].values():
        if 'forward_dependencies' in spec:spec['forward_dependencies']=[names.get(n,n) for n in spec['forward_dependencies']]
    result['scope']='Exact generated historical fields; same bounded publication roles; carrier alias verified by b085d952';result['historical_projection']=evidence
    destination.write_text(json.dumps(result,indent=2)+'\n');return destination
