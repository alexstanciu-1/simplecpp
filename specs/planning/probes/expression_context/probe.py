"""Run from a configured Simple C++ checkout. Supply a fresh scratch directory."""
import json
import subprocess
import sys
from pathlib import Path

root = Path.cwd()
work = Path(sys.argv[1]).resolve()
work.mkdir()
sys.path.insert(0, str(root / 'compiler/tests'))
from application_vectors_native import measure

ops = '$ops vector<function<int(int)>> = [fn($x)=>$x+2];'
model = '''
const int SLOT=0;
class Selector {
    public function func():vector<int> { echo "M"; $keys vector<int> = [0]; return $keys; }
}
function indexOnce():int { echo "I"; return 0; }
function argument():int { echo "A"; return 6; }
function witness(int $arg):int { echo "C",$arg; return $arg+2; }
'''
setup = '''
$rows vector<Selector> = [new Selector()];
$row vector<function<int(int)>> = [witness(...)];
$something vector<vector<function<int(int)>>> = [$row];
'''
anchor = '$something[$rows[indexOnce()]->func()[0]][SLOT](argument())'
holder = 'class Holder { public vector<int> $values; public int $n=2; }'
hsetup = '$h=new Holder();$h->values=[3];$owners vector<Holder> = [$h];'
cases = [
    ('literal-tree','php','$something[$an_expression_may_go_here[12]->func()[10]][a_constant]($arg_1);',None),
    ('anchor-statement','php',model+setup+anchor+';return 8;',b'IMAC6'),
    ('anchor-value','php',model+setup+'echo '+anchor+';return 8;',b'IMAC68'),
    ('integer-callee','php','$v vector<int> = [3]; $v[0](2);return 8;',None),
    ('bad-key','php','$v vector<int> = [3];echo $v["x"];return 8;',None),
    ('stored-local','php',ops+'$f=$ops[0];echo $f(6);return 8;',b'8'),
    ('grouped-local','php',ops+'$f=$ops[0];echo ($f)(6);return 8;',None),
    ('grouped-index','php',ops+'echo ($ops[0])(6);return 8;',b'8'),
    ('returned-call','php','function makeOp():function<int(int)> { return fn($x)=>$x+2; } echo makeOp()(6);return 8;',None),
    ('returned-call-local','php','function makeOp():function<int(int)> { return fn($x)=>$x+2; } $f=makeOp();echo $f(6);return 8;',b'8'),
    ('field-read','php',holder+hsetup+'echo $owners[0]->values[0];return 8;',b'3'),
    ('field-write','php',holder+hsetup+'$owners[0]->values[0]=7;echo $h->values[0];return 8;',None),
    ('field-write-local','php',holder+hsetup+'$o=$owners[0];$o->values[0]=7;echo $h->values[0];return 8;',b'7'),
    ('owner-field','php',holder+hsetup+'$owners[0]->n=7;echo $h->n;return 8;',None),
    ('wrong-arity','php',ops+'echo $ops[0]();return 8;',None),
    ('php-value-write','php','$v vector<vector<int>> = [[3]];$v[0][0]+=4;$v[0][0]++;echo $v[0][0];return 8;',b'8'),
    ('cpp-value-write','cpp','vector<vector<int>> v=[[3]];v[0][0]+=4;v[0][0]++;echo v[0][0];return 8;',b'8'),
    ('js-value-write','js','let v:vector<vector<int>> = [[3]];v[0][0]+=4;v[0][0]++;echo v[0][0];return 8;',b'8'),
    ('py-read','py','v: vector<vector<int>> = [[3]]\nreturn v[0][0]+5\n',b''),
    ('py-write','py','v: dynamic = [[3]]\nv[0][0] = 8\nif v[0][0] == 8:\n    return 8\nreturn 1\n',b''),
]
exact_model = model.replace('SLOT', 'a_constant').replace('= [0]', '= [' + ','.join(['0'] * 11) + ']')
exact_setup = setup.replace('$rows', '$an_expression_may_go_here').replace('[new Selector()]', '[' + ','.join(['new Selector()'] * 13) + ']')
cases.extend([
    ('exact-native','php',exact_model+exact_setup+'$arg_1=6;$something[$an_expression_may_go_here[12]->func()[10]][a_constant]($arg_1);return 8;',b'MC6'),
    ('py-typed-nested-write','py','v: vector<vector<int>> = [[3]]\nv[0][0] = 8\nreturn v[0][0]\n',None),
    ('reference-index','php','function byRef(int &$x):void {$x=9;}$v vector<int> = [3];byRef($v[0]);return 8;',None),
    ('value-argument','php','function byValue(int $x):int {return $x+5;}$v vector<int> = [3];return byValue($v[0]);',b''),
    ('lazy-key','php','function noisy():int {echo "X";return 0;}$v vector<int> = [3];if(false && $v[noisy()]) {return 1;}return 8;',b''),
    ('typed-callee-replaced','php', 'function replace(vector<function<int(int)>> &$v):int {$v[0]=fn($x)=>$x+20;echo "A";return 6;}'+ops+'echo $ops[0](replace($ops)),":",$ops[0](6);return 8;',b'A8:26'),
    ('grouped-target','php',holder+hsetup+'($owners[0])->values[0]=7;echo $h->values[0];return 8;',None),
])
for ext in ('cpp','js','py'):
    end = '\n' if ext == 'py' else ';'
    cases.append((ext+'-callee-syntax', ext, 'return ops[0](6)'+end, None))

from classes_native import fixture
for ext in ('cpp','js'):
    f = fixture(ext)
    access = f['access']
    model_source = f['klass']('Node', f['field']('x','int','0') + f['getter']())
    local_source = ('vector<Node> nodes = [new Node()]; vector<int> values = [8];' if ext == 'cpp'
                    else 'let nodes:vector<Node> = [new Node()]; let values:vector<int> = [8];')
    cases.append((ext+'-method-key', ext, model_source+local_source+'return values[nodes[0]'+access+'get()];', b''))
cases.append(('py-method-key','py', 'class Node:\n    def key(self) -> int:\n        return 0\nnodes: vector<Node> = [Node()]\nvalues: vector<int> = [8]\nreturn values[nodes[0].key()]\n', b''))

negative = {
    'integer-callee': ('AFTER PARSE', 'Invocation requires an owned callable'),
    'bad-key': ('AFTER PARSE', 'Vector index requires int64'),
    'wrong-arity': ('AFTER PARSE', 'Argument count mismatch'),
    'grouped-local': ('PARSE', 'Expected ;'), 'returned-call': ('PARSE', 'Expected ;'),
    'field-write': ('PARSE', 'Expected call statement'),
    'grouped-target': ('AFTER PARSE', 'Field assignment requires writable local storage'),
    'owner-field': ('AFTER PARSE', 'Field assignment requires writable local storage'),
    'reference-index': ('AFTER PARSE', 'Reference argument requires stable variable storage'),
    'py-typed-nested-write': ('AFTER PARSE', 'Nested indexed write requires'),
    **{ext+'-callee-syntax': ('PARSE','Expected '+('NEWLINE' if ext=='py' else ';')) for ext in ('cpp','js','py')},
}

for name, ext, source, expected in cases:
    site = work / name
    site.mkdir()
    parsed = subprocess.run(['php', Path(__file__).with_name('parse.php'), root, ext], input=source, text=True, capture_output=True)
    if parsed.returncode:
        message = parsed.stdout or parsed.stderr
        print(name,'PARSE',message,flush=True)
        assert name in negative and negative[name][0]=='PARSE' and negative[name][1] in message
        continue
    if name=='literal-tree':
        tree = parsed.stdout.strip()
        print(name, 'TREE', tree, flush=True)
        assert tree == 'indexed callable(index(index($something, index(func(index($an_expression_may_go_here, 12)), 10)), a_constant), $arg_1)'
        continue
    ext = 'phs' if ext == 'php' else ext
    app, out = site/'app', site/'out'
    app.mkdir()
    (app/'module.json').write_text(json.dumps({'entry':'main.'+ext}))
    (app/('main.'+ext)).write_text(source)
    response=json.loads(subprocess.check_output(['php',root/'compiler/tests/incremental_executable.php',app,out],input='"run"\n',text=True))
    if 'error' in response:
        print(name,'AFTER PARSE',response['error'],flush=True)
        assert name in negative and negative[name][0]=='AFTER PARSE' and negative[name][1] in response['error']
        continue
    assert expected is not None, (name, 'unexpectedly emitted')
    measure(out,site/'native',expected)
    print(name,'NATIVE',repr(expected),flush=True)

# Edit only the provider while the consuming expression keeps its AST.
from collection_goal_native import revisions
incremental = work/'cross-file'
incremental.mkdir()
caller = setup+'echo '+anchor+';return 8;'
bad_model = model.replace('vector<int>', 'vector<string>').replace('= [0]', '= ["bad"]')
revisions(incremental, 'php', {'main':caller, 'provider':model}, [
    (None, None, b'IMAC68'),
    ('provider', bad_model, 'Vector index requires int64'),
    ('provider', model, b'IMAC68'),
])
