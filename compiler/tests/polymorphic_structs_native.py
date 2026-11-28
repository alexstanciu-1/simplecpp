"""Polymorphic struct values, abstract storage restrictions and checked shared downcasts."""
from source_suffix import source_suffix
import argparse
import subprocess
import tempfile
from pathlib import Path

from abstract_methods_native import forms
from collection_goal_native import guard, revisions
from application_vectors_native import measure
from object_access_native import syntax


def prove(work, flavor, memcheck):
    s, method, _, _, _, pure = forms(flavor)
    rec, field, fn, local, infer = (s[k] for k in ['record','field','function','local','infer'])
    v, a = s['v'], s['access']
    n, p, q = (v+x for x in ['n','p','q'])
    x = field('x','int','3')
    node = rec('Node', x+method('virtual','1',default='1'))
    leaf = rec('Leaf', field('item','shared<Token>')+method('override',v+'this'+a+'x',default='1'), 'Node')
    read = fn('read','int',[('n','shared<Node>')],f'return {n}{a}evaluate();')
    copy = fn('copyLeaf','Leaf',[('n','Leaf')],f'return {n};')
    main = local('p','Leaf')+f'{p}{a}item=new Token();'+infer('q',f'copyLeaf({p})')
    main += local('rows','vector<Leaf>')[:-1]+' = [];'+f'{v}rows[]={q};{q}{a}x=8;'
    main += f'if ({v}rows[0]{a}evaluate()!=3) {{return 3;}}'
    main += f'if ({q}{a}item!=={p}{a}item) {{return 2;}} echo {p}{a}evaluate(),{q}{a}evaluate(),read(new Leaf());return 8;'
    src = dict(node=node,leaf=leaf,token=rec('Token'),api=read+copy,main=main,idle=fn('idle','int',[],'return 4;'))
    ok = b'383'
    abstract = 'abstract '+rec('Node', x+pure())
    abstract_with_body = 'abstract '+node
    missing = rec('Leaf',field('item','shared<Token>'),'Node')
    ctor = {'php':'public function __construct(Node $n) {}','cpp':'Box(Node n) {}','js':'constructor(n:Node) {}'}[flavor]
    invalid = 'Abstract struct cannot be used as a complete value'
    edits = [(None,None,ok),(None,None,ok),('node',abstract,ok),
             ('leaf',missing,'must implement abstract method'),(None,None,None),('leaf',leaf,ok),
             ('node',abstract_with_body,ok)]
    for extra in [fn('takeNode','int',[('n','Node')],'return 2;'), fn('give','Node',[],'return new Leaf();'),
                  rec('Box',ctor), rec('Box',field('item','Node')), fn('listNodes','vector<Node>',[],'return [];'),
                  fn('mapNodes','hash<Node,int>',[],'return [];')]:
        edits.extend([('extra',extra,invalid),('extra',None,ok)])
    edits.extend([('main',local('p','Node')+'return 8;',invalid),('main',main,ok),
                  ('main',infer('p','new Node()')+'return 8;',invalid),('main',main,ok),
                  ('api',read+copy+fn('slice','Node',[('n','Leaf')],f'return {n};'),invalid),
                  ('node',node,'Unsupported explicit conversion'),('api',read+copy,ok)])
    cast = syntax(flavor)[3]
    as_base = fn('asBase','shared<Node>',[('n','shared<Node>')],f'return {n};')
    down = fn('down','shared<Leaf>',[('n','shared<Node>')],'return '+cast(n,'Leaf')+';')
    test = fn('isLeaf','bool',[('n','shared<Node>')],f'return {n} instanceof Leaf;')
    calls = as_base+test+down
    source = infer('p','new Leaf()')+infer('q',f'down(asBase({p}))')+f'if ({p}!=={q} || !isLeaf({p}) || isLeaf(null) || isLeaf(new Sibling())) {{return 1;}}'
    source += f'{q}{a}x=9;echo {p}{a}x;return 8;'
    src['sibling'] = rec('Sibling',method('override','5',default='1'),'Node')
    # A separate family lets us remove only the source polymorphism, without invalidating an override first.
    branch = rec('Branch',method('virtual','1'))+rec('Twig','','Branch')
    probe = fn('probe','bool',[('n','shared<Branch>')],f'return {n} instanceof Twig;')
    edits.extend([('api',read+copy+calls,ok),('main',source,b'9'),('node',abstract,b'9'),
                  ('leaf',None,None),(None,None,None),('leaf',leaf,b'9'),
                  ('branch',branch,b'9'),('checkBranch',probe,b'9'),('branch','abstract '+rec('Branch')+rec('Twig','','Branch'),'polymorphic source'),
                  (None,None,None),('branch',branch,b'9')])
    marker = 'abstract '+rec('Marker')
    ctor_bad = ctor.replace('Node','Marker')
    edits.extend([('marker',rec('Marker'),b'9'),('extra',rec('Box',ctor_bad),b'9'),
                  ('marker',marker,invalid),('extra',None,b'9'),
                  ('extra',fn('makeMarker','shared<Marker>',[],'return new Marker();'),invalid),('extra',None,b'9')])
    # The abstract base constructor runs once for each new descendant, and never on an inline copy.
    base_ctor = {'cpp':'Origin(int x) {echo "C";this->x=x;}', 'php':'public function __construct(int $x) {echo "C";$this->x=$x;}',
                 'js':'constructor(x:int) {echo "C";this.x=x;}'}[flavor]
    family = 'abstract '+rec('Origin',x+base_ctor)+rec('Child','','Origin')
    inline = {'cpp':'Child c(4);','php':'$c Child(4);','js':'let c:Child(4);'}[flavor]
    copy_main = inline+infer('k',v+'c')+f'{v}k{a}x=7;echo {v}c{a}x,{v}k{a}x;'
    copy_main += infer('owner','new Child(2)')+f'echo {v}owner{a}x;return 8;'
    edits.extend([('family',family,b'9'),('main',copy_main,b'C47C2'),('main',source,b'9')])
    # Even with no pure virtual slots, an abstract receiver can select fields but cannot be copied.
    self_copy = infer('copy',v+'this')+'return 2;'
    edits.extend([('marker','abstract '+rec('Marker',method('', '2').replace('return 2;',self_copy)),invalid),
                  ('marker',marker,b'9')])
    revisions(work,flavor,src,edits,unaffected_source='idle.'+source_suffix(flavor))
    measure(work/f'{flavor}-fresh-{len(edits)-1}',work/f'{flavor}-recovered-fresh',b'9')
    if memcheck:
        run = subprocess.run(['valgrind','--leak-check=full','--errors-for-leak-kinds=all','--error-exitcode=99',
                              work/f'{flavor}-recovered-fresh/program'],capture_output=True)
        assert run.returncode == 8 and b'All heap blocks were freed' in run.stderr,run.stderr
    defs = abstract+leaf+src['token']+src['sibling']+calls
    for label, operand in [('empty','null'),('mismatch','new Sibling()')]:
        guard(work,flavor,label,defs+infer('p',f'down({operand})')+'return 1;', 'invalid_object_cast')


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output',type=Path)
    parser.add_argument('--memcheck',action='store_true')
    args = parser.parse_args()
    with tempfile.TemporaryDirectory() as tmp:
        work = args.output or Path(tmp)
        work.mkdir(exist_ok=True)
        for flavor in ['php','cpp','js']:
            prove(work,flavor,args.memcheck)
