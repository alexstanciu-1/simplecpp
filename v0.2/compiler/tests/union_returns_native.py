"""Closed union return contracts across source files and incremental class-layout changes."""
import argparse
import json
from pathlib import Path
import tempfile

from collection_goal_native import revisions
from interfaces_native import forms


def prove(work, flavor):
    s, _, klass, method, _, _ = forms(flavor)
    fn, v = s['function'], s['v']
    pair = 'RecordDecl|TraitDecl'
    factory = fn('readDecl',pair,[('record','bool')],
                 'if ('+v+'record) {return new RecordDecl();}return new TraitDecl();')
    inspect = fn('inspect','int',[('node','TraitDecl|RecordDecl')],
                 'if ('+v+'node instanceof RecordDecl) {return 1;}return 2;')
    forward = fn('forwardDecl',pair,[('record','bool')],'return readDecl('+v+'record);')
    main = 'echo inspect(forwardDecl(true)),inspect(readDecl(false));return 8;'
    def record(base):
        return klass('RecordDecl','',base)
    def trait(base):
        return klass('TraitDecl','',base)
    root = klass('Node',method('tag','return 0;',intent='virtual'))
    child = klass('Child','','RecordDecl')
    from_child = fn('fromChild',pair,[('child','Child')],'return '+v+'child;')
    base_view = fn('baseView','Node',[('node',pair)],'return '+v+'node;')
    lifetime = fn('observe','weak<Node>',[],
                  s['infer']('owner','baseView(readDecl(true))')+
                  s['infer']('watch','weak('+v+'owner)')+
                  'if (lock('+v+'watch) === '+v+'owner) {echo 1;}return '+v+'watch;')
    sources = dict(root=root, common=klass('Middle','','Node'), record=record('Node'),
                   right=trait('Node'), other=klass('Other','','Node'), child=child,
                   api=inspect, factory=factory, forward=forward, wrap=from_child, main=main)
    maker = s['record']('Factory',method('make','return new RecordDecl();',result=pair))
    calls = s['local']('inlineFactory','Factory')+s['infer']('sharedFactory','new Factory()')
    calls += 'echo inspect('+v+'inlineFactory'+s['access']+'make()),inspect('+v+'sharedFactory'+s['access']+'make());return 8;'
    revisions(work,flavor,sources,[
        (None,None,b'12'),(None,None,b'12'),
        ('factory',factory.replace('new TraitDecl()', 'new Other()'),'Argument must belong'),
        ('factory',factory,b'12'),('child',klass('Child','','Other'),'Argument must belong'),
        (None,None,'Argument must belong'),('child',child,b'12'),
        ('record',record('Middle'),b'12'),('right',trait('Middle'),b'12'),
        ('right',None,None),('right',trait('Node'),b'12'),
        ('factory',factory.replace(pair,'TraitDecl|RecordDecl'),b'12'),
        ('factory',factory.replace(pair,'RecordDecl|Other').replace('new TraitDecl()', 'new Other()'),'Argument must belong'),
        ('factory',factory,b'12'),
        ('main','echo readDecl(false)'+s['access']+'tag();return 8;',b'0'),
        ('methods',maker,b'0'),
        ('methods',None,b'0'),('observer',base_view+lifetime,b'0'),
        ('main',s['infer']('watch','observe()')+'if (lock('+v+'watch)) {return 1;}return 8;',b'1'),
        ('methods',maker,b'1'),('main',calls,b'11'),
        ('methods',maker.replace('new RecordDecl()', 'new Other()'),'Argument must belong'),
        ('methods',maker.replace('new RecordDecl()', 'new TraitDecl()'),b'22'),
        ('right',trait('Middle'),b'22'),('right',None,None),('right',trait('Node'),b'22'),
    ])
    for rev, native in [(0,'Node'),(8,'Middle'),(10,'Node')]:
        units = work/f'{flavor}-fresh-{rev}'/'units'
        assert any('owner_'+native+' readDecl_' in p.read_text() for p in units.glob('*.cpp'))


def static_returns(work, flavor):
    s, _, klass, method, _, _ = forms(flavor)
    fn, v = s['function'], s['v']
    sep = '.' if flavor == 'js' else '::'
    pair = 'RecordDecl|TraitDecl'
    body = 'if ('+v+'flag) {return new RecordDecl();}return new TraitDecl();'
    signature = {'php':'public static function read(bool $flag): '+pair,
                 'cpp':'public: static '+pair+' read(bool flag)',
                 'js':'static read(flag:bool): '+pair}[flavor]
    factory = klass('RecordSyntax',signature+'{'+body+'}')
    def arm(name, base):
        return klass(name,'',base)
    inspect = fn('inspect','int',[('node',pair)],
                 'if ('+v+'node instanceof RecordDecl) {return 1;}return 2;')
    call = 'RecordSyntax'+sep+'read'
    main = 'echo inspect('+call+'(true)),inspect('+call+'(false));return 8;'
    lexical = {'php':'self::','cpp':'Self::','js':'class.'}[flavor]
    late = 'Static::' if flavor == 'cpp' else 'static::'
    sources = dict(root=klass('Node',method('tag','return 0;',intent='virtual')),
                   middle=klass('Middle','','Node'), record=arm('RecordDecl','Node'), right=arm('TraitDecl','Node'),
                   other=arm('Other','Node'), factory=factory, derived=klass('Derived','','RecordSyntax'), api=inspect, main=main)
    parent = {'php':'parent::','cpp':'Parent::','js':'super.'}[flavor]
    bridge = klass('Derived',signature.replace('read(', 'relay(')+'{return '+parent+'read('+v+'flag);}', 'RecordSyntax')
    make = fn('produce','Sibling',[],'echo "P";return new Sibling();')
    sibling = fn('consumeSibling','int',[],'return inspect(produce()::read(false));')
    sources['sibling'] = klass('Sibling','','RecordSyntax')+make
    revisions(work,flavor,sources,[
        (None,None,b'12'),(None,None,b'12'),
        ('factory',factory.replace('new TraitDecl()', 'new Other()'),'Argument must belong'),
        (None,None,'Argument must belong'),('factory',factory,b'12'),
        ('record',arm('RecordDecl','Middle'),b'12'),('right',arm('TraitDecl','Middle'),b'12'),
        ('main',main.replace('RecordSyntax','Derived'),b'12'),('main',main,b'12'),
        ('derived',klass('Derived',signature+'{'+body+'}','RecordSyntax'),b'12'),
        ('derived',sources['derived'],b'12'),('right',None,None),('right',arm('TraitDecl','Node'),b'12'),
        ('factory',factory.replace(pair,'RecordDecl|Other').replace('new TraitDecl()', 'new Other()'),'Argument must belong'),
        ('factory',factory,b'12'),
        ('factory',factory.replace('new TraitDecl()',lexical+'read(true)'),b'11'),
        ('factory',factory.replace('new TraitDecl()',late+'read(true)'),b'11'),
        ('factory',factory,b'12'),
        ('derived',bridge,b'12'),('user',sibling,b'12'),
        ('main','echo inspect(Derived'+sep+'relay(true)),consumeSibling();return 8;',b'1P2'),
        ('main','echo inspect(Derived'+sep+'relay(true));return 8;',b'1'),
        ('user',None,b'1'),('user',sibling,b'1'),('main',main,b'12'),
        ('derived',klass('Derived',signature.replace(pair,'TraitDecl|RecordDecl')+'{return new TraitDecl();}','RecordSyntax'),b'12'),
        ('main',main.replace('RecordSyntax','Derived'),b'22'),
        ('derived',klass('Derived',signature.replace(pair,'Other|RecordDecl')+'{return new Other();}','RecordSyntax'),'signature'),
        ('derived',sources['derived'],b'12'),
    ])
    for rev, retained in ((20,True),(22,False),(23,True)):
        units = work/f'{flavor}-fresh-{rev}'/'units'
        emitted = ''.join(p.read_text() for p in units.glob('*.cpp'))
        assert ('called_7_Sibling_12_RecordSyntax_read' in emitted) == retained
        assert 'called_7_Derived_12_RecordSyntax_read' in emitted
    for rev, owner in ((0,'Node'),(6,'Middle'),(12,'Node')):
        out = work/f'{flavor}-fresh-{rev}'
        units = json.loads((out/'manifest.json').read_text())['sources']
        provider = out/units[f'{flavor}/factory.{flavor}']
        caller = out/units[f'{flavor}/main.{flavor}']
        header = provider.with_suffix('.hpp')
        signature = f'owner_{owner} method_12_RecordSyntax_read('
        assert signature in provider.read_text() and signature in header.read_text()
        assert header.name in caller.read_text()


def virtual_returns(work, flavor):
    s, _, klass, method, _, implement = forms(flavor)
    fn, v, op = s['function'], s['v'], s['access']
    pair = 'RecordDecl|TraitDecl'
    reverse = 'TraitDecl|RecordDecl'
    different = 'RecordDecl|Other'
    def reader(result, arm, intent):
        return method('read','return new '+arm+'();',result=result,intent=intent)
    def interface(result):
        signature = {'php':'public function read(): '+result,'cpp':result+' read()', 'js':'read(): '+result}[flavor]
        return 'interface Reader {'+signature+';}'+(';' if flavor=='cpp' else '')
    base = klass('Factory',reader(pair,'RecordDecl','virtual'))
    leaf = implement('Leaf',reader(reverse,'TraitDecl','override'),'Factory',('Reader',))
    other = klass('Foreign',reader(different,'Other','virtual'))
    probe = fn('inspect','int',[('node',pair)],'if ('+v+'node instanceof RecordDecl) {return 1;}return 2;')
    callers = ''.join(fn('via'+name,'int',[('source',name)],'return inspect('+v+'source'+op+'read());')
                      for name in ('Factory','Reader','Leaf'))
    main = s['infer']('leaf','new Leaf()')+'echo viaFactory('+v+'leaf),viaReader('+v+'leaf),viaLeaf('+v+'leaf);return 8;'
    src = dict(node=klass('Node',method('tag','return 0;',intent='virtual')),record=klass('RecordDecl','','Node'),
               trait=klass('TraitDecl','','Node'),other=klass('Other','','Node'), reader=interface(pair),
               base=base,leaf=leaf,foreign=other,api=probe+callers,main=main)
    revisions(work,flavor,src,[
        (None,None,b'222'),(None,None,b'222'),('leaf',leaf.replace('new TraitDecl()', 'new RecordDecl()'),b'111'),
        ('leaf',leaf.replace(reverse,different).replace('new TraitDecl()', 'new Other()'),'signature'),
        (None,None,'signature'),('leaf',leaf,b'222'),('reader',interface(different),'signature'),
        ('reader',None,None),('reader',interface(reverse),b'222'),
        ('leaf',leaf.replace(reverse,'TraitDecl'),'signature'),('leaf',leaf,b'222'),
    ])
    emitted = ''.join(p.read_text() for p in (work/(flavor+'-out')/'units').iterdir())
    for identity in (pair, 'Other|RecordDecl'):
        assert 'scpp_slot_read_'+identity.encode().hex() in emitted


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output',type=Path)
    args = parser.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-union-return-') as tmp:
        work = args.output or Path(tmp)
        work.mkdir(exist_ok=True)
        for flavor in ('php','cpp','js'):
            prove(work,flavor)
        static_work = work/'static'
        static_work.mkdir()
        for flavor in ('php','cpp','js'):
            static_returns(static_work,flavor)
        virtual_work = work/'virtual'
        virtual_work.mkdir()
        for flavor in ('php','cpp','js'):
            virtual_returns(virtual_work,flavor)
