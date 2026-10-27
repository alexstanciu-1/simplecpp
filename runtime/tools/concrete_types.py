"""Materialize prepared vector/result demands and reuse compatible published exports.

Physical generation only: source admission remains with existing catalogs/compiler.
"""
import argparse
import hashlib
import json
from pathlib import Path
import subprocess
import tempfile
from type_names import Registry, parse
from public_package import sdk_identity

VERSION = 'vector-result-v1'
RUNTIME = Path(__file__).resolve().parents[1]

def digest(data):
    return hashlib.sha256(data).hexdigest()

def write(path, text):
    path.parent.mkdir(parents=True, exist_ok=True)
    if not path.exists() or path.read_text() != text:
        path.write_text(text)

def run(command, source=None):
    return subprocess.check_output(command, input=source, text=True)

def abi(compiler, flags):
    macros = run([compiler, '-std=c++23', *flags, '-dM', '-E', '-x', 'c++', '-'], '#include <string>\n')
    selected = sorted(line for line in macros.splitlines() if any(
        word in line for word in ('__GLIBCXX__ ', '_GLIBCXX_USE_CXX11_ABI ', '_GLIBCXX_DEBUG ',
                                 '_LIBCPP_VERSION ', '__SIZEOF_POINTER__ ', '__BYTE_ORDER__ ')))
    return digest((VERSION + repr(flags) + run([compiler, '-dumpmachine']) + run([compiler, '--version']) + '\n'.join(selected)).encode())

def generate(request, output, runtime, dependencies, compiler, flags):
    data = json.loads(request.read_text())
    identity = abi(compiler, flags)
    imported = {}
    leaves = dict(data.get('leaves', {}))
    names = dict(data.get('names', {}))
    includes = [output/'public', output/'include', runtime/'public', runtime/'include']
    for dependency in dependencies:
        manifest = json.loads((dependency/'public/specializations.json').read_text())
        if not manifest.get('artifact_files'):
            raise ValueError('Dependency is not a sealed binary publication')
        if manifest.get('sdk_identity') != sdk_identity(dependency):
            raise ValueError('Incompatible/modified dependency implementation SDK')
        for relative, checksum in manifest['artifact_files'].items():
            if digest((dependency/relative).read_bytes()) != checksum:
                raise ValueError('Modified dependency library: '+relative)
        if manifest['abi'] != identity:
            raise ValueError('Incompatible dependency target ABI')
        for relative, checksum in manifest['public_files'].items():
            if digest((dependency/'public'/relative).read_bytes()) != checksum:
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
        includes += [dependency/'public', dependency/'include']
    # The supported physical recipes must still be registered at their existing owner.
    catalog = json.loads((runtime/'metadata/modules/core.json').read_text())
    type_names = {row['id']:row['name'] for row in catalog['types']}
    recipes = {row.get('type', type_names.get(row['id'])): row for row in catalog['cpp_types']}
    for family in ('vector', 'result'):
        if recipes[family]['symbol'] != 'scpp::'+('vector_t' if family == 'vector' else 'result'):
            raise ValueError('Changed native recipe requires adapter review: '+family)
    naming = Registry({'version':1, 'types':list(leaves),
        'families': {'vector':{'parameters':['type']}, 'result':{'parameters':['type']}},
        'mappings':[{'type':key, 'name':name} for key,name in names.items()]})
    for leaf in leaves:
        naming.resolve(leaf)
    command = [compiler, '-std=c++23', *flags, *['-I'+str(p) for p in includes]]
    exports, sources, visiting = {}, [], set()

    def materialize(text):
        canonical, name = naming.resolve(text)
        if canonical in exports:
            return exports[canonical]
        if canonical in leaves:
            return leaves[canonical]
        if canonical in imported:
            exports[canonical] = imported[canonical]
            return imported[canonical]
        if canonical in visiting:
            raise ValueError('Unresolved layout dependency cycle: '+canonical)
        visiting.add(canonical)
        node = naming.normalize(parse(canonical))
        if node.name not in ('vector','result'):
            raise ValueError('Unsupported concrete demand: '+canonical)
        payload = materialize(node.arguments[0].canonical())
        native = recipes[node.name]['symbol']+'<'+payload['cpp']+'>'
        prefix = '#include "'+payload['header']+'"\n#include "'+recipes[node.name]['header']+'"\n'
        probe = prefix+'#include <cstdio>\n#include <type_traits>\nint main() {\n'
        probe += f'using N = {native}; static_assert(std::is_copy_constructible_v<N> && std::is_copy_assignable_v<N> && std::is_nothrow_destructible_v<N>);\n'
        probe += 'std::printf("%zu %zu\\n", sizeof(N), alignof(N));\n'
        if node.name == 'vector':
            probe += f'using C = scpp::iteration_detail::vector_range<{payload["cpp"]}>; std::printf("%zu %zu\\n", sizeof(C), alignof(C));\n'
        probe += '}\n'
        with tempfile.TemporaryDirectory(prefix='scpp-concrete-probe-') as directory:
            executable = str(Path(directory)/'probe')
            subprocess.run([*command, '-x','c++','-','-o',executable],input=probe,text=True,check=True)
            layouts = [tuple(map(int,line.split())) for line in run([executable]).splitlines()]
        header, source = emit(name, payload, native, node.name, layouts)
        path = 'scpp/'+name+'.hpp'
        write(output/'public'/path, header)
        write(output/'include'/path, '#pragma once\n#include "../../public/'+path+'"\n')
        cpp = 'include/core/generated/'+name+'.cpp'
        write(output/cpp, prefix+source)
        sources.append(cpp)
        exports[canonical] = {'name':name,'cpp':'scpp::'+name,'header':path,'owner':data['package'],
            'native':native,'layout':list(layouts[0]),'source':cpp,'dependencies':[node.arguments[0].canonical()],
            'ordinary_api':VERSION+':'+node.name}
        visiting.remove(canonical)
        return exports[canonical]

    for root in data['roots']:
        materialize(root)
    # Carry transitive imported exports so the inventory remains usable downstream.
    for key,value in imported.items():
        exports.setdefault(key,value)
    previous_path = output/'public/specializations.json'
    if previous_path.exists():
        previous = json.loads(previous_path.read_text())
        stale = set(previous.get('sources', [])) - set(sources)
        if stale:
            raise ValueError('Removed generated demands require a fresh output staging directory: '+', '.join(sorted(stale)))
    public_files = {str(p.relative_to(output/'public')):digest(p.read_bytes())
                    for p in sorted((output/'public').rglob('*.hpp'))}
    manifest = {'version':1,'generator':VERSION,'abi':identity,'package':data['package'],
                'leaves':leaves,'types':exports,'sources':sources,'public_files':public_files,
                'artifacts':['scpp_runtime_core'] if data['package']=='runtime.core' else [data['package']]}
    write(output/'public/specializations.json',json.dumps(manifest,indent=2,sort_keys=True)+'\n')
    return manifest


def emit(name, payload, native, family, layouts):
    value = payload['cpp']
    size, alignment = layouts[0]
    cursor = name.rstrip('_')+'_cursor_'
    header = '// Generated by runtime/tools/concrete_types.py.\n#pragma once\n#include "'+payload['header']+'"\n#include "scpp/integers.hpp"\n'
    if family == 'result':
        header += '#include "scpp/error_value.hpp"\n'
    header += 'namespace scpp {\nnamespace detail { struct concrete_access; }\n'
    if family == 'vector':
        header += f'class {cursor};\n'
    header += f'''class {name} final {{
    alignas({alignment}) unsigned char storage_[{size}];
    friend struct detail::concrete_access;
    {name}(void (*construct)(void *, const void *), const void *context);
public:
    {name}();
    {name}(const {name} &);
    {name}({name} &&);
    {name} &operator=(const {name} &);
    {name} &operator=({name} &&);
    ~{name}() noexcept;
'''
    source = '#include "scpp/'+name+'.hpp"\n#include "scpp/detail/concrete_access.hpp"\n#include "scpp/detail/error_value_access.hpp"\n#include <type_traits>\nnamespace scpp {\n'
    source += f'using native_{name} = {native};\nstatic_assert(sizeof(native_{name}) == {size} && alignof(native_{name}) == {alignment});\n'
    source += f'static_assert(std::is_nothrow_destructible_v<native_{name}>);\n'
    source += f'''{name}::{name}(void (*construct)(void *, const void *), const void *context) {{ construct(this->storage_, context); }}
{name}::{name}() {{ ::new (this->storage_) native_{name}(); }}
{name}::{name}(const {name} &other) {{ ::new (this->storage_) native_{name}(detail::concrete_access::get<native_{name}>(other)); }}
{name}::{name}({name} &&other) {{ ::new (this->storage_) native_{name}(std::move(detail::concrete_access::get<native_{name}>(other))); }}
{name} &{name}::operator=(const {name} &other) {{ if (this != &other) detail::concrete_access::get<native_{name}>(*this) = detail::concrete_access::get<native_{name}>(other); return *this; }}
{name} &{name}::operator=({name} &&other) {{ if (this != &other) detail::concrete_access::get<native_{name}>(*this) = std::move(detail::concrete_access::get<native_{name}>(other)); return *this; }}
{name}::~{name}() noexcept {{ detail::concrete_access::get<native_{name}>(*this).~native_{name}(); }}
'''
    access = f'detail::concrete_access::get<native_{name}>(*this)'
    methods = []
    if family == 'vector':
        methods = [
            ('uint64','size','() const',f'return uint64({access}.size());'),
            ('uint64','capacity','() const',f'return uint64({access}.capacity());'),
            ('uint64','estimated_storage_bytes','() const',f'return uint64({access}.estimated_storage_bytes());'),
            ('boolean','empty','() const',f'return boolean({access}.empty().native_value());'),
            ('void','reserve','(uint64 count)',f'{access}.reserve(detail::concrete_size(count));'),
            ('void','resize',f'(uint64 count, const {value} &fill)',f'{access}.resize(detail::concrete_size(count), fill);'),
            ('void','clear','()',f'{access}.clear();'), ('void','_unset_','()',f'{access}._unset_();'),
            ('void','compact','()',f'{access}.compact();'),
            ('void','compact','(uint64 capacity)',f'{access}.compact(detail::concrete_size(capacity));'),
            (value,'at','(uint64 index) const',f'return {access}.at(detail::concrete_size(index));'),
            (value,'index','(uint64 index) const','return this->at(index);'),
            (value,'operator[]','(uint64 index) const','return this->at(index);'),
            ('void','set',f'(uint64 index, const {value} &value)',f'{access}.at(detail::concrete_size(index)) = value;'),
            ('void','append',f'(const {value} &value)',f'{access}.append(value);'),
            ('void','append',f'({value} &&value)',f'{access}.append(std::move(value));'),
            ('void','push_back',f'(const {value} &value)','this->append(value);'),
            ('void','push_back',f'({value} &&value)','this->append(std::move(value));'),
            ('boolean','remove','(int64 index)',f'return boolean({access}.remove(int_t<>(index.native_value())));'),
            (cursor,'iterate','() const',f'return detail::concrete_access::make<{cursor}, native_{cursor}>([&] {{ return {access}.value_range(); }});')]
        cs, ca = layouts[1]
        source += f'using native_{cursor} = scpp::iteration_detail::vector_range<{value}>;\nstatic_assert(sizeof(native_{cursor}) == {cs} && alignof(native_{cursor}) == {ca});\n'
        # Cursor has no copied raw self-pointers: construct directly and forbid moves/copies.
        cursor_header = f'''class {cursor} final {{
    alignas({ca}) unsigned char storage_[{cs}];
    friend struct detail::concrete_access;
    {cursor}(void (*construct)(void *, const void *), const void *context);
public:
    {cursor}(const {cursor} &) = delete;
    {cursor} &operator=(const {cursor} &) = delete;
    ~{cursor}() noexcept;
    boolean valid() const;
    int64 key() const;
    {value} value();
    void next();
}};
'''
        cur=f'detail::concrete_access::get<native_{cursor}>(*this)'
        source += f'''{cursor}::{cursor}(void (*construct)(void *, const void *), const void *context) {{ construct(this->storage_, context); }}
{cursor}::~{cursor}() noexcept {{ {cur}.~native_{cursor}(); }}
boolean {cursor}::valid() const {{ auto &range = const_cast<native_{cursor} &>({cur}); return boolean(range.begin() != range.end()); }}
int64 {cursor}::key() const {{ if (!this->valid()) raise_runtime_error("Finished iteration", "index_out_of_range", "core", "iteration"); auto &range = const_cast<native_{cursor} &>({cur}); return int64((*range.begin()).key().native_value()); }}
{value} {cursor}::value() {{ if (!this->valid()) raise_runtime_error("Finished iteration", "index_out_of_range", "core", "iteration"); return (*{cur}.begin()).value_copy(); }}
void {cursor}::next() {{ if (!this->valid()) raise_runtime_error("Finished iteration", "index_out_of_range", "core", "iteration"); auto iterator = {cur}.begin(); ++iterator; }}
'''
    else:
        cursor_header = ''
        header += f'''    explicit {name}(const {value} &value);
    explicit {name}({value} &&value);
    explicit {name}(const error_value &error);
    {name} &operator=(const {value} &value);
    {name} &operator=({value} &&value);
    {name} &operator=(const error_value &error);
'''
        source += f'''{name}::{name}(const {value} &value) {{ ::new (this->storage_) native_{name}(value); }}
{name}::{name}({value} &&value) {{ ::new (this->storage_) native_{name}(std::move(value)); }}
{name}::{name}(const error_value &error) {{ ::new (this->storage_) native_{name}(detail::error_value_access::get(error)); }}
{name} &{name}::operator=(const {value} &value) {{ {access} = value; return *this; }}
{name} &{name}::operator=({value} &&value) {{ {access} = std::move(value); return *this; }}
{name} &{name}::operator=(const error_value &error) {{ {access} = detail::error_value_access::get(error); return *this; }}
'''
        methods = [('boolean','has_value','() const',f'return boolean({access}.has_value().native_value());'),
                   ('boolean','has_error','() const',f'return boolean({access}.has_error().native_value());'),
                   (value,'value','() const',f'return {access}.value();'),
                   (value,'require_value','(const char *context) const',f'return {access}.require_value(context);'),
                   ('error_value','error','() const',f'return detail::error_value_access::make([&] {{ return {access}.require_error("error()"); }});'),
                   ('error_value','require_error','(const char *context) const',f'return detail::error_value_access::make([&] {{ return {access}.require_error(context); }});')]
    for result,method,args,body in methods:
        header += f'    {result} {method}{args};\n'
        source += f'{result} {name}::{method}{args} {{ {body} }}\n'
    header += '};\n'+cursor_header
    if family == 'vector':
        for result,method,body in [('uint64','count','return value.size();'),('boolean','empty','return value.empty();'),('boolean','isset','return boolean(true);')]:
            header += f'{result} {method}(const {name} &value);\n'
            source += f'{result} {method}(const {name} &value) {{ {body} }}\n'
    else:
        header += f'boolean take({value} &value, error_value &error, const {name} &source);\n'
        source += f'''boolean take({value} &value, error_value &error, const {name} &source) {{
    const {name} snapshot(source);
    if (snapshot.has_value()) {{ value = snapshot.value(); error = error_value(); return boolean(true); }}
    error = snapshot.error(); return boolean(false);
}}
'''
    return header+'}\n', source+'}\n'


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--request', type=Path, required=True)
    parser.add_argument('--output', type=Path, required=True)
    parser.add_argument('--runtime', type=Path, default=RUNTIME)
    parser.add_argument('--dependency', type=Path, action='append', default=[])
    parser.add_argument('--compiler', default='clang++')
    parser.add_argument('--flag', action='append', default=[])
    args = parser.parse_args()
    manifest = generate(args.request, args.output, args.runtime, args.dependency, args.compiler, args.flag)
    print('Generated',len(manifest['sources']),'implementation units; inventory:',args.output/'public/specializations.json')

if __name__ == '__main__':
    main()
