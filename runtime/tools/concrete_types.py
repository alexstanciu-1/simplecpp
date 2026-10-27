"""Drive the C++ metadata generator, target probes and existing publication protocol.

No family-specific resolution, declarations, lifecycle or operation emission lives here.
"""
import argparse
import hashlib
import json
import os
from pathlib import Path
import shutil
import subprocess
import tempfile
from public_package import sdk_identity

VERSION = 'metadata-api-v1'
RUNTIME = Path(__file__).resolve().parents[1]


def digest(data):
    return hashlib.sha256(data).hexdigest()


def write(path, text):
    path.parent.mkdir(parents=True, exist_ok=True)
    if not path.exists() or path.read_text() != text:
        path.write_text(text)


def run(command, source=None):
    return subprocess.check_output([str(x) for x in command], input=source, text=True)


def generator_tool(runtime):
    if os.environ.get('SCPP_API_GENERATOR'):
        return Path(os.environ['SCPP_API_GENERATOR'])
    published = runtime/'tools/scpp_api_generator'
    if published.is_file():
        return published
    build = runtime.parent/'compiler/my-try-cpp/build/runtime'
    tool = build/'scpp_api_generator'
    if not (build/'CMakeCache.txt').is_file():
        raise ValueError('Configure a runtime tooling build or set SCPP_API_GENERATOR first')
    sources = [p for p in (runtime/'tools/api_generator').rglob('*') if p.is_file()] + [runtime/'CMakeLists.txt']
    if not tool.exists() or any(p.stat().st_mtime_ns > tool.stat().st_mtime_ns for p in sources):
        subprocess.run(['cmake','--build',str(build),'--target','scpp_api_generator','-j8'], check=True)
    return tool


def abi(compiler, flags):
    macros = run([compiler, '-std=c++23', *flags, '-dM', '-E', '-x', 'c++', '-'], '#include <string>\n')
    selected = sorted(line for line in macros.splitlines() if any(
        word in line for word in ('__GLIBCXX__ ', '_GLIBCXX_USE_CXX11_ABI ', '_GLIBCXX_DEBUG ',
                                 '_LIBCPP_VERSION ', '__SIZEOF_POINTER__ ', '__BYTE_ORDER__ ')))
    return digest((VERSION + repr(flags) + run([compiler, '-dumpmachine']) + run([compiler, '--version']) + '\n'.join(selected)).encode())


def safe_path(root, relative):
    path = Path(relative)
    if path.is_absolute() or '..' in path.parts:
        raise ValueError('Invalid generated output path: '+relative)
    target = root/path
    if not target.resolve().is_relative_to(root.resolve()):
        raise ValueError('Generated output escapes staging root: '+relative)
    return target


def metadata(runtime):
    data = json.loads((runtime/'metadata/public_api.json').read_text())
    # Bind to the existing metadata representation owner; do not duplicate symbols.
    data['bindings'] = {}
    for path in sorted((runtime/'metadata/modules').glob('*.json')):
        catalog = json.loads(path.read_text())
        names = {row['id']:row['name'] for row in catalog.get('types', [])}
        for row in catalog.get('cpp_types', []):
            name = row.get('type', names.get(row['id']))
            if name:
                binding = {'cpp':row['symbol'], 'header':row['header']}
                if name in data['bindings'] and data['bindings'][name] != binding:
                    raise ValueError('Conflicting native binding: '+name)
                data['bindings'][name] = binding
    return data


def generate(request, output, runtime, dependencies, compiler, flags):
    data = json.loads(request.read_text())
    identity = abi(compiler, flags)
    api = metadata(runtime)
    api_identity = digest(json.dumps(api, sort_keys=True).encode())
    imported, leaves, names = {}, dict(data.get('leaves', {})), dict(data.get('names', {}))
    for dependency in dependencies:
        manifest = json.loads((dependency/'public/specializations.json').read_text())
        if not manifest.get('artifact_files'):
            raise ValueError('Dependency is not a sealed binary publication')
        if manifest.get('sdk_identity') != sdk_identity(dependency):
            raise ValueError('Incompatible/modified dependency implementation SDK')
        for relative, checksum in manifest['artifact_files'].items():
            if digest(safe_path(dependency, relative).read_bytes()) != checksum:
                raise ValueError('Modified dependency library/tool: '+relative)
        if manifest.get('api_identity') != api_identity:
            raise ValueError('Incompatible dependency API metadata')
        if manifest['abi'] != identity:
            raise ValueError('Incompatible dependency target ABI')
        for relative, checksum in manifest['public_files'].items():
            if digest(safe_path(dependency/'public', relative).read_bytes()) != checksum:
                raise ValueError('Modified/incomplete dependency public package: '+relative)
        for key, value in manifest['leaves'].items():
            if key in leaves and leaves[key] != value:
                raise ValueError('Conflicting leaf identity: '+key)
            leaves[key] = value
        for key, value in manifest['types'].items():
            if key in imported and imported[key] != value:
                raise ValueError('Duplicate/conflicting definition ownership: '+key)
            imported[key] = value
            if key in names and names[key] != value['name']:
                raise ValueError('Conflicting concrete name: '+key)
            names[key] = value['name']
    tool = generator_tool(runtime)
    envelope = {'metadata':api,'request':data,'imports':imported,'leaves':leaves,'names':names,'layouts':{}}
    def plan():
        return json.loads(run([tool], json.dumps(envelope)))
    with tempfile.TemporaryDirectory(prefix='scpp-api-generation-') as directory:
        stage = Path(directory)
        if (output/'public').is_dir(): shutil.copytree(output/'public',stage/'public')
        includes = [stage/'public',stage/'include',output/'public',output/'include',runtime/'public',runtime/'include']
        for dependency in dependencies: includes += [dependency/'public',dependency/'include']
        command = [compiler,'-std=c++23',*flags,*['-I'+str(p) for p in includes]]
        result = plan()
        # One compile/run per dependency layer unit, combining its companion layouts.
        # Declarations and lifecycle requirements come exclusively from the C++ plan.
        while any(unit['ready'] != 'yes' for unit in result['units']):
            for unit in result['units']:
                if unit['ready'] == 'yes':
                    write(safe_path(stage,unit['header_path']),unit['header'])
                    continue
                code = unit['probe_includes']+'#include <cstdio>\n#include <type_traits>\nint main() {\n'
                for check in unit.get('checks', []): code += 'static_assert('+check+');\n'
                for index, probe in enumerate(unit['probes']):
                    alias='N'+str(index)
                    code += 'using '+alias+' = '+probe['native']+';\n'
                    for mode, traits in {'default':['default_constructible'], 'copy':['copy_constructible','copy_assignable'], 'move':['move_constructible','move_assignable'], 'destroy':['nothrow_destructible']}.items():
                        if probe['lifecycle'][mode] == 'native':
                            for trait in traits: code += f'static_assert(std::is_{trait}_v<{alias}>);\n'
                    code += f'std::printf("%zu %zu\\n", sizeof({alias}), alignof({alias}));\n'
                code += '}\n'
                executable=stage/'layout_probe'
                subprocess.run([*command,'-x','c++','-','-o',str(executable)],input=code,text=True,check=True)
                layouts=[list(map(int,line.split())) for line in run([executable]).splitlines()]
                if len(layouts)!=len(unit['probes']) or any(len(x)!=2 or min(x)<=0 for x in layouts):
                    raise ValueError('Invalid layout probe result')
                for probe,layout in zip(unit['probes'],layouts): envelope['layouts'][probe['key']]=layout
                break
            result=plan()
        sources = [unit['source_path'] for unit in result['units']]
        previous_path=output/'public/specializations.json'
        if previous_path.exists():
            previous=json.loads(previous_path.read_text())
            stale=set(previous.get('sources',[]))-set(sources)
            if stale: raise ValueError('Removed generated demands require a fresh output staging directory: '+', '.join(sorted(stale)))
        generated=[]
        for unit in result['units']:
            for key,content in [('header_path','header'),('source_path','source')]:
                write(safe_path(stage,unit[key]),unit[content]); generated.append(unit[key])
            relative=Path(unit['header_path']).relative_to('public')
            forwarding='include/'+str(relative)
            write(safe_path(stage,forwarding),'#pragma once\n#include "'+'../'*len(relative.parts)+'public/'+str(relative)+'"\n')
            generated.append(forwarding)
        public_files={str(p.relative_to(stage/'public')):digest(p.read_bytes()) for p in sorted((stage/'public').rglob('*.hpp'))}
        manifest={'version':1,'generator':VERSION,'abi':identity,'api_identity':api_identity,'package':data['package'],
                  'leaves':result['leaves'],'types':result['types'],'sources':sources,'generated_files':generated,
                  'public_files':public_files,'artifacts':data.get('artifacts',[data['package']])}
        # Nothing is installed until parsing, generation and every target probe succeed.
        for relative in generated: write(safe_path(output,relative),safe_path(stage,relative).read_text())
        write(output/'public/specializations.json',json.dumps(manifest,indent=2,sort_keys=True)+'\n')
    return manifest


def main():
    parser=argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--request',type=Path,required=True)
    parser.add_argument('--output',type=Path,required=True)
    parser.add_argument('--runtime',type=Path,default=RUNTIME)
    parser.add_argument('--dependency',type=Path,action='append',default=[])
    parser.add_argument('--compiler',default='clang++')
    parser.add_argument('--flag',action='append',default=[])
    args=parser.parse_args()
    manifest=generate(args.request,args.output,args.runtime,args.dependency,args.compiler,args.flag)
    print('Generated',len(manifest['sources']),'implementation units through C++ API metadata; inventory:',args.output/'public/specializations.json')

if __name__=='__main__': main()
