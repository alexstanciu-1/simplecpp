"""Isolated feasibility probe. Run after building the Clang runtime and compiler publication."""
import json
import re
from pathlib import Path
import subprocess
import sys
import tempfile

ROOT = Path(__file__).resolve().parents[5]
sys.path.insert(0, str(ROOT / 'compiler/tests'))
from classes_native import fixture
from parent_methods_native import write_app


def publish(work, flavor, typ, key, nested=False):
    s = fixture(flavor)
    v, access = s['v'], s['access']
    record = s['record']('Token', s['field']('x', 'int') + s['field']('y', 'int'))
    empty = {'cpp': f'{typ} items=[];', 'php': f'$items {typ} = [];', 'js': f'let items:{typ} = [];'}[flavor]
    body = empty + s['local']('item', 'Token') + f'{v}item{access}x=7;{v}item{access}y=9;'
    body += f'{v}items[{key}]={v}item;'
    if nested:
        body = empty + empty.replace('items', 'child') + f'{v}child[0]=7;{v}items[1]={v}child;{v}child[0]=9;'
    fn = s['function']('produce', typ, [], body + f'return {v}items;')
    main = s['infer']('h', 'produce()')
    if typ.startswith('hash'):
        main += s['infer']('found', f'{v}h[{key}]') + f'echo {v}found{access}x,{v}found{access}y;'
    if nested:
        main += s['infer']('found', f'{v}h[1]') + f'echo (int)({v}found[0]);'
    app = work / 'app'
    write_app(app, flavor, {'main': main + 'return 8;', 'api': record + fn})
    out = work / 'out'
    result = subprocess.run(['php', ROOT / 'compiler/main.php', 'project', app,
                             ROOT / 'build/clang/native-layout/facts.json', out, '--optimize-function=produce'],
                            text=True, capture_output=True)
    if result.returncode:
        return None, result.stderr.strip()
    return out, ''


def native(out, executable, expected):
    manifest = json.loads((out / 'manifest.json').read_text())
    subprocess.run(['clang++', '-std=c++23', '-stdlib=libc++', '-O2', '-I', ROOT / 'runtime/generated/public',
                    *(out / p for p in manifest['translation_units']), ROOT / 'build/clang/libscpp_runtime.a',
                    '-o', executable], check=True)
    result = subprocess.run([executable], capture_output=True)
    assert (result.stdout, result.returncode) == (expected, 8), result


def storage_helper(text):
    """Split the actual recipe at its construction point, sharing every growth and publication step."""
    signature = 'void insert_new(key_type&& key, value_type&& value) {'
    placement = '::new (data + used * sizeof(entry)) entry{static_cast<key_type&&>(key), static_cast<value_type&&>(value)};'
    begin, create, end = (text.index(signature), text.index(placement), text.index('    bool remove(const key_type&'))
    assert begin < create < end
    prepare = text[begin + len(signature):create].rstrip()
    commit = text[create + len(placement):end]
    assignment = 'at_slot(found.position).value = static_cast<value_type&&>(value);'
    assert text.count(assignment) == 1
    shared = """void replace_value(std::uint32_t position, value_type&& value) {
        at_slot(position).value = static_cast<value_type&&>(value);
    }
    void set_fields(key_type&& key, scpp::int64 x, scpp::int64 y) {
        const auto found = locate(key);
        if (found.position != UINT32_MAX) {
            ++replacements;
            replace_value(found.position, value_type{x, y});
            return;
        }
        prepare_insert();
        ::new (data + used * sizeof(entry)) entry{static_cast<key_type&&>(key), {x, y}};
        publish_insert();
        ++inserts;
    }
    """
    text = (text[:begin].replace(assignment, 'replace_value(found.position, static_cast<value_type&&>(value));')
            + shared + signature + '\n        prepare_insert();\n        ' + placement
            + '\n        publish_insert();\n    }\n    void prepare_insert() {' + prepare
            + '\n    }\n    void publish_insert() noexcept {' + commit + text[end:])
    helper = """inline void NAME::set_fields(KEY key, scpp::int64 x, scpp::int64 y) {
    if (!storage_) { storage_ = new storage; ++ordinary; }
    storage_->set_fields(static_cast<KEY&&>(key), x, y);
}
"""
    return text, helper


def install_helper(out, counters=True, single_lookup=False):
    """Install either experimental helper only in the throwaway generated bundle."""
    headers = list((out / 'units').glob('*.hpp'))
    header, = [p for p in headers if 'void insert_new(key_type&&' in p.read_text()]
    text = header.read_text()
    name = re.search(r'class (scpp_hash_\w+) final', text)[1]
    key = re.search(r'using key_type = (.*?);', text)[1]
    text = text.replace('private:\n    struct storage;', f'inline static int inserts=0, replacements=0, ordinary=0;\nvoid set_fields({key} key, scpp::int64 x, scpp::int64 y);\nprivate:\n    struct storage;')
    anchor = f'inline void {name}::clear()'
    helper = """inline void NAME::set_fields(KEY key, scpp::int64 x, scpp::int64 y) {
    auto* s = storage_;
    if (s) {
        auto position = s->locate(key).position;
        if (position != UINT32_MAX) {
            auto& value = s->at_slot(position).value;
            ++replacements;
            value.x = x; value.y = y;
            return;
        }
        if (s->used < UINT32_MAX && s->used < s->capacity && s->index.holds(s->used + 1)
            && std::uint64_t(s->index.size()) + s->index.deleted() < std::uint64_t(s->index.capacity()) * 3 / 4) {
            ++inserts;
            ::new (s->data + s->used * sizeof(storage::entry)) storage::entry{static_cast<KEY&&>(key), {x, y}};
            s->live[s->used] = 1;
            s->index.add(s->hash_key(s->at_slot(s->used).key), static_cast<std::uint32_t>(s->used));
            ++s->used; ++s->live_size;
            return;
        }
    }
    ++ordinary;
    set(static_cast<KEY&&>(key), {x, y});
}
"""
    if single_lookup:
        text, helper = storage_helper(text)
    assert text.count(anchor) == 1
    if not counters:
        text = text.replace('inline static int inserts=0, replacements=0, ordinary=0;\n', '')
        for counter in ('inserts', 'replacements', 'ordinary'):
            text = text.replace('++' + counter + ';', '')
            helper = helper.replace('++' + counter + ';', '')
    header.write_text(text.replace(anchor, helper.replace('NAME', name).replace('KEY', key) + anchor))
    return header, name, key


def direct_hash_probe(out, dest, single_lookup=False):
    header, name, key = install_helper(out, single_lookup=single_lookup)
    headers = list((out / 'units').glob('*.hpp'))
    caller, = [p for p in (out / 'units').glob('*.cpp') if 'var_item_1' in p.read_text()]
    code, count = re.subn(r'\t\[\[maybe_unused\]\] scpp_records::Token var_item_1 = \{\};.*?var_items_0\.set\((.*?), var_item_1\);',
                          r'\tvar_items_0.set_fields(\1, 7, 9);', caller.read_text(), flags=re.S)
    assert count == 1
    caller.write_text(code)
    native(out, dest / 'direct', b'79')
    # Compare whole contents after replacements, removals, growth and index-width transition.
    record, = [p for p in headers if 'struct Token {' in p.read_text()]
    src = dest / 'table.cpp'
    src.write_text(f'#include "{record}"\n#include "{header}"\nusing Table = App::{name};\n'
                   + ('auto key(int i){return scpp::int64(i);}' if 'int64' in key else
                      '#include <string>\nauto key(int i){auto s=std::to_string(i);return scpp::string{s.data(),long(s.size())};}')
                   + """
#include <cassert>
#include <cstdlib>
static int budget=-1;
void* operator new(std::size_t n){
    if(budget==0) throw std::bad_alloc();
    if(budget>0) --budget;
    if(auto p=std::malloc(n?n:1)) return p;
    throw std::bad_alloc();
}
void operator delete(void* p) noexcept {std::free(p);}
void operator delete(void* p,std::size_t) noexcept {std::free(p);}
int main(){
    Table plain, direct;
    for(int pass=0; pass<3; ++pass) {
        for(int i=0; i<330; ++i) {
            plain.set(key(i), {i, pass}); direct.set_fields(key(i), i, pass);
        }
        assert(plain.size()==direct.size());
        for(int i=0; i<330; ++i) {
            auto a=plain.at(key(i)), b=direct.at(key(i));
            assert(a.x==b.x && a.y==b.y);
            if(i%3==0) assert(plain.remove(key(i)) && direct.remove(key(i)));
        }
    }
    assert(Table::inserts && Table::replacements && Table::ordinary);
    // Fill capacity, then fail each allocation of the growing insertion in turn.
    int failures=0;
    for(int allowance=0; allowance<16; ++allowance) {
        Table t;
        for(int i=0;i<4;++i) t.set_fields(key(i),i,3);
        auto extra=key(10);
        budget=allowance;
        try { t.set_fields(extra,10,4); }
        catch(const std::bad_alloc&) {
            budget=-1;
            ++failures;
            assert(t.size()==4 && !t.has(extra));
            for(int i=0;i<4;++i) assert(t.at(key(i)).x==i && t.at(key(i)).y==3);
            t.set_fields(extra,10,4);
            assert(t.size()==5 && t.at(extra).y==4);
            continue;
        }
        budget=-1;
        assert(t.size()==5 && failures>0);
        return 0;
    }
    return 1;
}
""")
    exe = dest / 'table'
    subprocess.run(['clang++', '-std=c++23', '-stdlib=libc++', '-O2', '-I', ROOT / 'runtime/generated/public',
                    src, ROOT / 'build/clang/libscpp_runtime.a', '-o', exe], check=True)
    subprocess.run([exe], check=True)


def check(work, single_lookup=False):
    for flavor in ('cpp', 'php', 'js'):
        for typ, key in [('hash<Token,int>', '1'), ('hash<Token,string>', '"k"'), ('mixed', '1'), ('dynamic', '1')]:
            dest = work / (flavor + typ)
            dest.mkdir()
            out, error = publish(dest, flavor, typ, key)
            if not out:
                print(flavor, typ, error, flush=True)
                assert typ in ('mixed', 'dynamic') and f'Unsupported boxing into {typ}' in error, error
                box = dest / 'nested'
                box.mkdir()
                out, error = publish(box, flavor, typ, key, nested=True)
                assert out, error
                native(out, box / 'run', b'7' if typ == 'mixed' else b'9')
                continue
            assert typ.startswith('hash'), (flavor, typ)
            native(out, dest / 'ordinary', b'79')
            direct_hash_probe(out, dest, single_lookup)
            print(flavor, typ, 'native record round trip passed', flush=True)


if __name__ == '__main__':
    with tempfile.TemporaryDirectory() as folder:
        check(Path(folder), '--single-lookup' in sys.argv)
