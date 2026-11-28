"""Explicit shared struct views, concrete destruction, checked access and retained recovery."""
from source_suffix import source_suffix
import argparse
import subprocess
import tempfile
from pathlib import Path

from collection_goal_native import guard, revisions
from application_vectors_native import measure
from object_access_native import syntax


def prove(work, flavor, memcheck):
    s, _, _, cast = syntax(flavor)
    v, field_access, fn = s['v'], s['access'], s['function']
    record, field, infer, local = s['record'], s['field'], s['infer'], s['local']
    node = record('Node', field('x', 'int', '3'))
    middle = record('Middle', '', 'Node')
    leaf = record('Leaf', field('token', 'shared<Token>'), 'Middle')
    def unary(name, result, arg, body):
        return fn(name, result, [('n', 'shared<'+arg+'>')], body)
    n = v+'n'
    api = unary('asBase', 'shared<Node>', 'Leaf', f'return {n};')
    api += unary('read', 'int', 'Node', f'return {n}{field_access}x;')
    api += unary('once', 'shared<Leaf>', 'Leaf', f'echo "P";return {n};')
    arg = f'once({n})'
    main = infer('n', 'new Leaf()')+infer('token', 'new Token()')+infer('watch', f'weak({v}token)')
    main += f'{n}{field_access}token={v}token;{v}token=null;'
    main += f'if (!({arg} instanceof Leaf) || !({arg} instanceof Node)) {{return 1;}}'
    # The vector stores inline Slot values; its shared field uses the ordinary typed destination.
    main += local('slot','Slot')+f'{v}slot{field_access}item={n};'
    main += local('slots','vector<Slot>')[:-1]+' = [];'+f'{v}slots[]={v}slot;'
    main += f'if ({v}slots[0]{field_access}item !== {n}) {{return 5;}}{v}slots{field_access}clear();{v}slot{field_access}item=null;'
    main += local('b', 'shared<Node>')+f'{v}b={n};'
    main += f'if ({v}b !== {n} || asBase({n}) !== {n} || '+cast(arg, 'Leaf')+f' !== {n}) {{return 2;}}'
    main += infer('checked', cast(arg, 'Node'))+f'{v}checked{field_access}x=7;echo read({n});'
    main += f'{v}checked=null;{n}=null;if (lock({v}watch)===null || {v}b{field_access}x!=7) {{return 3;}}'
    main += f'{v}b=null;if (lock({v}watch)!==null || asBase({n})!==null || {n} instanceof Node) {{return 4;}}return 8;'
    sources = dict(node=node, middle=middle, leaf=leaf, token=record('Token'), api=api, main=main,
                   slot=record('Slot',field('item','shared<Node>')), idle=fn('idle','int',[],'return 1;'))
    ok = b'PPPP7'
    bad = unary('down', 'shared<Leaf>', 'Node', 'return '+cast(n,'Leaf')+';')
    ref = {'php':'function bind(shared<Node> &$b):void {}','cpp':'void bind(shared<Node>& b) {}',
           'js':'function bind(&b:shared<Node>):void {}'}[flavor]
    revisions(work, flavor, sources, [
        (None,None,ok), (None,None,ok),
        ('middle',record('Middle','','Token'),None), (None,None,None), ('middle',middle,ok),
        ('middle',None,None), ('middle',middle,ok),
        ('api',api+bad,'polymorphic source'),
        ('api',api+unary('unrelated','bool','Leaf',f'return {n} instanceof Token;'),'related structs'),
        ('api',api+unary('implicitDown','shared<Leaf>','Node',f'return {n};'),'Unsupported explicit conversion'),
        ('api',api+fn('slice','Node',[('n','Leaf')],f'return {n};'),'Unsupported explicit conversion'),
        ('api',api+ref+unary('badBind','void','Leaf',f'bind({n});'),'Reference argument requires exact storage type'),
        ('api',api+fn('widen','vector<Node>',[('items','vector<Leaf>')],f'return {v}items;'),'Unsupported explicit conversion'),
        ('api',api,ok),
    ], unaffected_source='idle.'+source_suffix(flavor))
    # Run the recovered fresh bundle independently as well as comparing its emitted artifacts.
    measure(work/f'{flavor}-fresh-13', work/f'{flavor}-fresh-native', ok)
    if memcheck:
        run = subprocess.run(['valgrind','--leak-check=full','--errors-for-leak-kinds=all','--error-exitcode=99',
                              work/f'{flavor}-fresh-native/program'], capture_output=True)
        assert run.returncode == 8 and b'All heap blocks were freed' in run.stderr, run.stderr
    for target in ['Node', 'Leaf']:
        guard(work, flavor, 'empty-'+target, node+middle+record('Leaf','','Middle')+
              local('n','shared<Leaf>')+infer('unused',cast(n,target))+'return 7;', 'invalid_object_cast')


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    parser.add_argument('--memcheck', action='store_true')
    args = parser.parse_args()
    with tempfile.TemporaryDirectory() as tmp:
        work = args.output or Path(tmp)
        work.mkdir(exist_ok=True)
        for flavor in ['php','cpp','js']:
            prove(work,flavor,args.memcheck)
