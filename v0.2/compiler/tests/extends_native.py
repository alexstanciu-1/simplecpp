"""Single-base storage, construction, visibility and incremental recovery across all flavors."""
import argparse
import json
import os
from pathlib import Path
import subprocess
import tempfile

from app_namespace_native import artifacts, invoke, service
from extends_regressions import incremental_receivers, constructor_publication

ROOT = Path(__file__).resolve().parents[2]


def run(command):
    result = subprocess.run(list(map(str, command)), capture_output=True, text=True)
    assert result.returncode == 0, (command, result.stdout, result.stderr)
    return result


def source_edit(path, text):
    # The filesystem frontend intentionally fingerprints second-resolution mtime and size.
    # Change only edited sources, including equal-size default edits, without sleeps.
    if path.exists() and path.read_text() == text:
        return
    stamp = int(path.stat().st_mtime) + 2 if path.exists() else 1990000000
    path.write_text(text)
    os.utime(path, (stamp, stamp))


def runtime(work, compiler):
    """Build the unchanged runtime with the same native compiler/standard library as the proof."""
    directory = work / compiler
    directory.mkdir()
    include = ROOT / 'runtime/generated/public'
    flags = ['-std=c++23', '-O1', '-Wall', '-Wextra', '-Werror', '-I', include]
    private = [ROOT / 'runtime/src' / name for name in ['numeric', 'sequence', 'hash']]
    flags += [arg for path in [*private, ROOT / 'runtime/generated/private'] for arg in ['-I', path]]
    sources = [ROOT / 'runtime/generated/src/runtime.cpp'] + [ROOT / 'runtime/src' / name for name in [
        'output/output.cpp', 'entry/entry.cpp', 'ownership/owner_access.cpp', 'numeric/arithmetic.cpp']]
    objects = []
    for index, source in enumerate(sources):
        obj = directory / f'{index}.o'
        run([compiler, *flags, '-c', source, '-o', obj])
        objects.append(obj)
    archive = directory / 'runtime.a'
    run(['ar', 'rcs', archive, *objects])
    return archive


def build(output, destination, compiler, library, expected):
    data = json.loads((output / 'manifest.json').read_text())
    units = [output / name for name in data['translation_units']]
    run([compiler, '-std=c++23', '-O1', '-Wall', '-Wextra', '-Werror', '-I', ROOT / 'runtime/generated/public',
         *units, library, '-o', destination])
    result = subprocess.run([destination], capture_output=True)
    assert (result.returncode, result.stdout, result.stderr) == (8, expected, b''), result


def fixtures(flavor):
    if flavor == 'php':
        base = ('struct Node { public int $start; protected int $hidden; private int $secret; '
                'public function __construct(int $start,int $h=3) { $this->start=$start; $this->hidden=$h; $this->secret=7; } }')
        derived = ('struct Literal extends Node { public int $value; private int $secret; '
                   'public function __construct(int $start,int $value) { parent::__construct(mark($start),mark(3)); '
                   '$this->value=$value; $this->secret=9; } public function get():int { return $this->hidden; } }')
        inherit = 'struct Inherited extends Node { public int $value=5; }'
        main = ('$x=new Literal(12,8); echo $x->start,$x->value,$x->get(); $y=new Inherited(4); '
                'echo $y->start; $z Literal(6,2); $copy=$z; $copy->start=9; echo $z->start,$copy->start; return 8;')
        mark = 'function mark(int $x):int { echo $x; return $x; }'
        borrow = 'function consume(shared<Node> $x):void {}'
        upcast = '$x=new Literal(1,2); consume($x); return 8;'
        inaccessible = '$x=new Literal(1,2); echo $x->hidden; return 8;'
        invalid_this = 'parent::__construct($this->value,3);'
        parent = 'parent::__construct(mark($start),mark(3));'
        empty = 'struct Empty {}'
        base_prefix, child_prefix = 'struct Node', 'struct Literal extends Node'
    elif flavor == 'cpp':
        base = ('struct Node { int start; protected: int hidden; private: int secret; public: '
                'Node(int start,int h=3) { this->start=start; this->hidden=h; this->secret=7; } };')
        derived = ('struct Literal : public Node { int value; private: int secret; public: '
                   'Literal(int start,int value) : Node(mark(start),mark(3)) { this->value=value; this->secret=9; } '
                   'int get() { return this->hidden; } };')
        inherit = 'struct Inherited : Node { int value=5; };'
        main = ('auto x=new Literal(12,8); echo x->start,x->value,x->get(); auto y=new Inherited(4); echo y->start; '
                'Literal z(6,2); auto copy=z; copy->start=9; echo z->start,copy->start; return 8;')
        mark = 'int mark(int x) { echo x; return x; }'
        borrow = 'void consume(shared<Node> x) {}'
        upcast = 'auto x=new Literal(1,2); consume(x); return 8;'
        inaccessible = 'auto x=new Literal(1,2); echo x->hidden; return 8;'
        invalid_this = ': Node(this->value,3)'
        parent = ': Node(mark(start),mark(3))'
        empty = 'struct Empty {};'
        base_prefix, child_prefix = 'struct Node', 'struct Literal : public Node'
    else:
        base = ('struct Node { start:int; protected hidden:int; private secret:int; '
                'constructor(start:int,h:int=3) { this.start=start; this.hidden=h; this.secret=7; } }')
        derived = ('struct Literal extends Node { value:int; private secret:int; '
                   'constructor(start:int,value:int) { super(mark(start),mark(3)); this.value=value; this.secret=9; } '
                   'get():int { return this.hidden; } }')
        inherit = 'struct Inherited extends Node { value:int=5; }'
        main = ('let x=new Literal(12,8); echo x.start,x.value,x.get(); let y=new Inherited(4); echo y.start; '
                'let z:Literal(6,2); let copy=z; copy.start=9; echo z.start,copy.start; return 8;')
        mark = 'function mark(x:int):int { echo x; return x; }'
        borrow = 'function consume(x:shared<Node>):void {}'
        upcast = 'let x=new Literal(1,2); consume(x); return 8;'
        inaccessible = 'let x=new Literal(1,2); echo x.hidden; return 8;'
        invalid_this = 'super(this.value,3);'
        parent = 'super(mark(start),mark(3));'
        empty = 'struct Empty {}'
        base_prefix, child_prefix = 'struct Node', 'struct Literal extends Node'
    return locals()


def inspection(work, flavor):
    source = fixtures(flavor)
    path = work / (flavor + '-inspection.input')
    path.write_text(source['base'] + source['derived'] + source['inherit'] + source['mark'] + source['main'])
    facts = work / 'target.json'
    facts.write_text(json.dumps({'schema': 1, 'native_integers': {'int': {'signed': True, 'digits': 31}}}))
    parsed = json.loads(run(['php', ROOT / 'compiler/main.php', 'parse', flavor, path]).stdout)
    assert next(record for record in parsed['structs'] if record['name'] == 'Literal')['base'] == 'Node'
    base = [statement for function in parsed['functions'] for statement in function['body']
            if statement['kind'] == 'base_initialization']
    assert len(base) == 1 and base[0]['explicit'] and len(base[0]['arguments']) == 2
    prepared = json.loads(run(['php', ROOT / 'compiler/main.php', 'prepare', flavor, path, facts]).stdout)
    base = [statement for function in prepared['functions'] for statement in function['body']['statements']
            if statement['kind'] == 'base_initialization']
    assert len(base) == 1 and base[0]['value']['type'] == 'Node'


def prove(work, flavor, libraries):
    app = work / flavor
    app.mkdir()
    output = work / f'{flavor}-output'
    source = fixtures(flavor)
    files = {name: app / f'{name}.{flavor}' for name in ['main', 'base', 'derived', 'support']}
    (app / 'module.json').write_text(json.dumps({'entry': files['main'].name}))
    files['main'].write_text(source['main'])
    files['base'].write_text(source['base'])
    files['derived'].write_text(source['derived'] + source['inherit'])
    files['support'].write_text(source['mark'] + source['borrow'])
    edits = [
        (None, None, None),
        ('main', source['inaccessible'], 'Cannot access protected field'),
        (None, None, 'Cannot access protected field'),
        ('main', source['main'], None),
        ('main', source['upcast'], 'Unsupported explicit conversion'),
        ('main', source['main'], None),
        ('derived', source['derived'].replace(source['parent'], source['invalid_this']) + source['inherit'], 'Current instance'),
        ('derived', source['derived'] + source['inherit'], None),
        ('base', source['empty'], 'Base must be a struct'),
        (None, None, 'Base must be a struct'),
        ('base', source['base'], None),
        ('base', source['base'].replace('struct Node', 'struct Node : Literal' if flavor == 'cpp' else 'struct Node extends Literal'), 'Recursive inline layout'),
        ('base', source['base'], None),
        ('derived', source['derived'].replace('secret', 'start') + source['inherit'], 'Inherited field redeclaration'),
        ('derived', source['derived'] + source['inherit'], None),
    ]
    # Base contract edits must invalidate constructor callers without copying that contract.
    edits += [
        ('base', source['base'].replace('=3', '=4'), None),
        ('base', source['base'].replace('=3', ''), 'Argument count mismatch'),
        ('base', source['base'], None),
    ]
    if flavor == 'php':
        bad_type = source['base'].replace('int $start', 'string $start')
        private_ctor = source['base'].replace('public function __construct', 'private function __construct')
        protected_receiver = 'public function bad(shared<Node> $n):int { return $n->hidden; }'
        bad_base = source['derived'].replace('extends Node', 'extends int')
        multiple = source['derived'].replace('extends Node', 'extends Node, Inherited')
    elif flavor == 'cpp':
        bad_type = source['base'].replace('int start', 'string start')
        private_ctor = source['base'].replace('public: Node(', 'private: Node(')
        protected_receiver = 'int bad(shared<Node> n) { return n->hidden; }'
        bad_base = source['derived'].replace(': public Node', ': int')
        multiple = source['derived'].replace(': public Node', ': Node, Inherited')
    else:
        bad_type = source['base'].replace('start:int', 'start:string')
        private_ctor = source['base'].replace('constructor(', 'private constructor(')
        protected_receiver = 'bad(n:shared<Node>):int { return n.hidden; }'
        bad_base = source['derived'].replace('extends Node', 'extends int')
        multiple = source['derived'].replace('extends Node', 'extends Node, Inherited')
    insert = source['derived'].rfind('}')
    for name, text, error in [
        ('base', bad_type, 'Unsupported explicit conversion'),
        ('base', private_ctor, 'Cannot call private constructor'),
        ('derived', source['derived'][:insert] + protected_receiver + source['derived'][insert:] + source['inherit'], 'Cannot access protected field'),
        ('derived', bad_base + source['inherit'], 'Base must be a struct'),
        ('derived', multiple + source['inherit'], 'Expected {'),
    ]:
        edits += [(name, text, error), (name, source[name] + (source['inherit'] if name == 'derived' else ''), None)]
    resident = service(app, output)
    try:
        for revision, (name, content, error) in enumerate(edits):
            if name is not None:
                source_edit(files[name], content)
            before = artifacts(output)
            current = invoke(resident)
            fresh_output = work / f'{flavor}-fresh-{revision}'
            fresh = service(app, fresh_output)
            try:
                other = invoke(fresh)
            finally:
                fresh.stdin.close()
                fresh.wait()
            if error is not None:
                assert error in current.get('error', ''), (flavor, revision, current)
                assert current['error'] == other.get('error'), (current, other)
                assert artifacts(output) == before
                continue
            assert 'error' not in current and 'error' not in other, (flavor, revision, current, other)
            assert artifacts(output) == artifacts(fresh_output), (flavor, revision)
            # Build both the retained and fresh paths initially and after failure recovery.
            if revision in [0, 10, 14]:
                for compiler, library in libraries.items():
                    build(output, app / f'native-{compiler}', compiler, library, b'123128346369')
                    build(fresh_output, app / f'fresh-{compiler}', compiler, library, b'123128346369')
    finally:
        resident.stdin.close()
        resident.wait()


def construction_contract(work, flavor, libraries):
    """Implicit base defaults, checked boundaries and required left-to-right base arguments."""
    if flavor == 'php':
        base = 'struct Base { public int $x; public function __construct(int $x=7,int $y=0) { $this->x=$x; } }'
        child = 'struct Child extends Base { public function __construct() {} }'
        checked = ('struct Checked extends Base { public function __construct() { parent::__construct(huge(),later()); echo "D"; } } '
                   'function huge():float { echo "H"; return 1e30; } function later():int { echo "L"; return 1; } '
                   'function fail():void { $x=new Checked(); }')
        main = '$x=new Child(); echo $x->x; return 8;'
        explicit = child.replace('{}', '{ parent::__construct(); }')
        protected = base.replace('public function __construct', 'protected function __construct')
        bad_order = child.replace('{}', '{ echo "D"; parent::__construct(); }')
        empty = 'struct Base {}'
        direct = '$x=new Base(); return 8;'
        qualify = lambda text: 'namespace Tree; ' + text
    elif flavor == 'cpp':
        base = 'struct Base { int x; Base(int x=7,int y=0) { this->x=x; } };'
        child = 'struct Child : Base { Child() {} };'
        checked = ('struct Checked : Base { Checked() : Base(huge(),later()) { echo "D"; } }; '
                   'float huge() { echo "H"; return 1e30; } int later() { echo "L"; return 1; } '
                   'void fail() { auto x=new Checked(); }')
        main = 'auto x=new Child(); echo x->x; return 8;'
        explicit = child.replace('Child() {}', 'Child() : Base() {}')
        protected = base.replace('Base(int', 'protected: Base(int')
        bad_order = child.replace('Child() {}', 'Child() : Child() {}')
        empty = 'struct Base {};'
        direct = 'auto x=new Base(); return 8;'
        qualify = lambda text: 'namespace Tree { ' + text + ' }'
    else:
        base = 'struct Base { x:int; constructor(x:int=7,y:int=0) { this.x=x; } }'
        child = 'struct Child extends Base { constructor() {} }'
        checked = ('struct Checked extends Base { constructor() { super(huge(),later()); echo "D"; } } '
                   'function huge():float { echo "H"; return 1e30; } function later():int { echo "L"; return 1; } '
                   'function fail():void { let x=new Checked(); }')
        main = 'let x=new Child(); echo x.x; return 8;'
        explicit = child.replace('{}', '{ super(); }')
        protected = base.replace('constructor(', 'protected constructor(')
        bad_order = child.replace('{}', '{ echo "D"; super(); }')
        empty = 'struct Base {}'
        direct = 'let x=new Base(); return 8;'
        qualify = lambda text: 'namespace Tree { ' + text + ' }'
    runtime_call = {'php': 'str_starts_with("ab","a")', 'cpp': '"ab".starts_with("a")', 'js': '"ab".startsWith("a")'}[flavor]
    runtime_child = explicit.replace('Base()', 'Base(' + runtime_call + ')') if flavor == 'cpp' else explicit.replace('__construct();', '__construct(' + runtime_call + ');').replace('super();', 'super(' + runtime_call + ');')
    app = work / (flavor + '-contract')
    app.mkdir()
    (app / 'module.json').write_text(json.dumps({'entry': 'main.' + flavor}))
    output = work / (flavor + '-contract-output')
    resident = service(app, output)
    try:
        for index, (model, derived, entry, error) in enumerate([
            (base, child, main, None),
            (base.replace('=7', '=9'), child, main, None),
            (base.replace('=7', ''), child, main, 'Argument count mismatch'),
            (base, bad_order, main, ''),
            (empty, explicit, 'return 8;', 'Explicit parent call requires'),
            (protected, child, direct, 'Cannot call protected constructor'),
            (protected, child, main, None),
            (base, child, main, None),
            (base, runtime_child, main, None),
            (base, runtime_child, main, None),
        ]):
            if index == 9:
                source_edit(app / 'module.json', json.dumps({'entry': 'main.' + flavor, 'cpp_namespace': None}))
            for name, text in [('base', model), ('child', derived), ('main', entry)]:
                source_edit(app / (name + '.' + flavor), qualify(text))
            result = invoke(resident)
            fresh_output = work / f'{flavor}-contract-fresh-{index}'
            fresh = service(app, fresh_output)
            try:
                other = invoke(fresh)
            finally:
                fresh.stdin.close()
                fresh.wait()
            if error is not None:
                assert 'error' in result and error in result['error'], result
                assert result['error'] == other.get('error'), (result, other)
            else:
                assert 'error' not in result and 'error' not in other, (result, other)
                assert artifacts(output) == artifacts(fresh_output), (flavor, index, [str(k) for k in artifacts(output) if artifacts(output)[k] != artifacts(fresh_output).get(k)])
                for compiler, library in libraries.items():
                    build(output, work / (flavor + '-contract-' + compiler), compiler, library, b'9' if index == 1 else b'1' if index >= 8 else b'7')
        source_edit(app / 'module.json', json.dumps({'entry': 'main.' + flavor}))
        # The first conversion throws: no evaluation of later(), no base or child body.
        source_edit(app / ('child.' + flavor), qualify(child + checked))
        result = invoke(resident)
        assert 'error' not in result, result
        units = [output / name for name in json.loads((output / 'manifest.json').read_text())['translation_units'] if name != 'entry.cpp']
        probe = work / (flavor + '-failure.cpp')
        probe.write_text('#include <stdexcept>\n#include <string>\nnamespace App { void Tree_ns_fail_(); }\n'
                         'int main() { try { App::Tree_ns_fail_(); return 1; } catch (const std::exception& e) { return std::string(e.what()) == "integer_cast_out_of_range" ? 0 : 2; } }')
        for compiler, library in libraries.items():
            binary = work / (flavor + '-failure-' + compiler)
            run([compiler, '-std=c++23', '-O1', '-Wall', '-Wextra', '-Werror', '-I', ROOT / 'runtime/generated/public',
                 probe, *units, library, '-o', binary])
            result = subprocess.run([binary], capture_output=True)
            assert (result.returncode, result.stdout, result.stderr) == (0, b'H', b''), result
    finally:
        resident.stdin.close()
        resident.wait()


def lifetime(work, flavor, libraries, memcheck):
    """Count live owners through throwing base construction and exact derived weak expiry."""
    sources = {
        'php': (
            'struct Token { public int $v; } '
            'struct Base { public shared<Token> $hold; public function __construct(shared<Token> $t,bool $fail) { '
            '$this->hold=$t; if ($fail) { $missing shared<Token>; echo $missing->v; } } }',
            'struct Child extends Base { public shared<Token> $own; '
            'public function __construct(shared<Token> $t,bool $fail) { parent::__construct($t,$fail); '
            '$this->own=$t; echo "D"; } } '
            'function makeToken():shared<Token> { return new Token(); } '
            'function makeChild(shared<Token> $t,bool $fail):shared<Child> { return new Child($t,$fail); } '
            'function inlineFail(shared<Token> $t):void { $x Child($t,true); }'),
        'cpp': (
            'struct Token { int v; }; '
            'struct Base { shared<Token> hold; Base(shared<Token> t,bool fail) { '
            'this->hold=t; if (fail) { shared<Token> missing; echo missing->v; } } };',
            'struct Child : Base { shared<Token> own; Child(shared<Token> t,bool fail) : Base(t,fail) { '
            'this->own=t; echo "D"; } }; '
            'shared<Token> makeToken() { return new Token(); } '
            'shared<Child> makeChild(shared<Token> t,bool fail) { return new Child(t,fail); } '
            'void inlineFail(shared<Token> t) { Child x(t,true); }'),
        'js': (
            'struct Token { v:int; } '
            'struct Base { hold:shared<Token>; constructor(t:shared<Token>,fail:bool) { '
            'this.hold=t; if (fail) { let missing:shared<Token>; echo missing.v; } } }',
            'struct Child extends Base { own:shared<Token>; constructor(t:shared<Token>,fail:bool) { '
            'super(t,fail); this.own=t; echo "D"; } } '
            'function makeToken():shared<Token> { return new Token(); } '
            'function makeChild(t:shared<Token>,fail:bool):shared<Child> { return new Child(t,fail); } '
            'function inlineFail(t:shared<Token>):void { let x:Child(t,true); }'),
    }
    app = work / (flavor + '-lifetime')
    app.mkdir()
    (app / 'module.json').write_text(json.dumps({'entry': 'main.' + flavor}))
    (app / ('main.' + flavor)).write_text('return 8;')
    for name, text in zip(['base', 'child'], sources[flavor]):
        (app / (name + '.' + flavor)).write_text(text)
    output = work / (flavor + '-lifetime-output')
    compiler_service = service(app, output)
    try:
        result = invoke(compiler_service)
        assert 'error' not in result, result
    finally:
        compiler_service.stdin.close()
        compiler_service.wait()
    units = [output / name for name in json.loads((output / 'manifest.json').read_text())['translation_units'] if name != 'entry.cpp']
    factory = next(unit for unit in units if 'return owner_Token::make();' in unit.read_text())
    probe = work / (flavor + '-lifetime.cpp')
    probe.write_text(f'#include "{factory}"\n' + r"""
#include <cassert>
#include <cstring>
#include <stdexcept>
#include <type_traits>
int main() {
    using namespace App;
    static_assert(std::is_base_of_v<scpp_records::Base, scpp_records::Child>);
    static_assert(!std::is_polymorphic_v<scpp_records::Child>);
    auto token = makeToken_();
    for (int i=0; i<64; ++i) {
        try { makeChild_(token, true); return 1; }
        catch (const std::runtime_error& e) {
            assert(std::strcmp(e.what(), "ownership_empty_access") == 0);
            assert(token.ptr->count == 1);
        }
        try { inlineFail_(token); return 2; }
        catch (const std::runtime_error&) { assert(token.ptr->count == 1); }
    }
    auto child = makeChild_(token, false);
    assert(token.ptr->count == 3);
    auto observer = weak_Child(child);
    auto alias = child;
    alias->hold = {};
    assert(!child->hold && token.ptr->count == 2);
    alias = {};
    child = {};
    assert(token.ptr->count == 1 && !observer.lock());
    assert(observer.ptr->count == 0);
}
""")
    for compiler, library in libraries.items():
        binary = work / (flavor + '-lifetime-' + compiler)
        run([compiler, '-std=c++23', '-O1', '-g', '-fsanitize=address,undefined', '-fno-omit-frame-pointer',
             '-Wall', '-Wextra', '-Werror', '-I', ROOT / 'runtime/generated/public',
             probe, *(unit for unit in units if unit != factory), library, '-o', binary])
        result = subprocess.run([binary], capture_output=True, env={**os.environ, 'ASAN_OPTIONS': 'detect_leaks=0'})
        assert (result.returncode, result.stdout, result.stderr) == (0, b'D', b''), result
        if not memcheck:
            continue
        run([compiler, '-std=c++23', '-O1', '-I', ROOT / 'runtime/generated/public',
             probe, *(unit for unit in units if unit != factory), library, '-o', binary])
        checked = run(['valgrind', '--leak-check=full', '--show-leak-kinds=all', '--errors-for-leak-kinds=all',
                      '--error-exitcode=99', binary])
        assert checked.stdout == 'D' and 'All heap blocks were freed' in checked.stderr, checked



if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    parser.add_argument('--memcheck', action='store_true', help='Run separate Valgrind leak checks')
    args = parser.parse_args()
    with tempfile.TemporaryDirectory() as temporary:
        work = (args.output or Path(temporary)).resolve()
        work.mkdir(exist_ok=True)
        libraries = {compiler: runtime(work, compiler) for compiler in ['clang++', 'g++']}
        for flavor in ['cpp', 'php', 'js']:
            incremental_receivers(work, flavor, libraries, build, source_edit)
            constructor_publication(work, flavor, libraries, build, source_edit)
            inspection(work, flavor)
            prove(work, flavor, libraries)
            lifetime(work, flavor, libraries, args.memcheck)
            construction_contract(work, flavor, libraries)
            print(flavor, 'extends passed', flush=True)
