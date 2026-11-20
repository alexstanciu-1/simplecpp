"""Required shared fields across successful construction, failure and retained recovery."""
from pathlib import Path
import tempfile

from collection_goal_native import revisions
from parent_methods_native import syntax


def prove(work, flavor):
    s, _, klass, _, method = syntax(flavor)
    v, access = s['v'], s['access']
    target = v + 'this' + access + 'child'
    assign = f'{target}={v}item;'
    read = f'echo {target}{access}number;'
    field = {
        'cpp': 'public: readonly Node initial;',
        'php': 'public readonly Node $initial;',
        'js': 'readonly initial:Node;',
    }[flavor]
    signature = {
        'cpp': 'public: Holder(Node item,bool flag)',
        'php': 'public function __construct(Node $item,bool $flag)',
        'js': 'constructor(item:Node,flag:bool)',
    }[flavor]
    initialize_readonly = f'{v}this{access}initial={v}item;'
    fail = 'throw Exception' + ('.' if flavor == 'js' else '::') + 'make("no object");'
    check = method('check', read, result='void')

    def holder(body, kind='Node'):
        return klass('Holder', s['field']('child', kind) + field
                     + s['field']('pending', '?Node', 'null')
                     + signature + '{' + initialize_readonly + body + '}' + check)

    branches = f'if ({v}flag) {{{assign}}} else {{{target}=new Node();}}'
    good = holder(branches)
    child = klass('Child', '', 'Holder')
    main = s['infer']('box', 'new Child(new Node(),true)')
    main += f'echo value({v}box{access}child);{v}box{access}child=new Node();'
    main += f'echo value({v}box{access}initial);return 8;'
    api = s['function']('value', 'int', [('node', 'Node')], f'return {v}node{access}number;')
    idle = s['function']('idle', 'int', [], 'return 17;')
    sources = dict(holders=good, children=child, nodes=klass('Node', s['field']('number', 'int', '7')),
                   api=api, main=main, idle=idle)
    revisions(work, flavor, sources, [
        (None, None, b'77'),
        (None, None, b'77'),
        ('holders', holder(f'if ({v}flag) {{{assign}}}'), 'Required property is not initialized: child'),
        (None, None, 'Required property is not initialized: child'),
        ('holders', holder(f'if ({v}flag) {{{assign}}} else {{{fail}}}'), b'77'),
        ('holders', holder(f'if ({v}flag) {{return;}}' + assign), 'Required property is not initialized: child'),
        ('holders', holder(read + assign), 'Required property is not initialized: child'),
        ('holders', holder(f'{v}this{access}check();' + assign), 'Required property is not initialized: child'),
        ('holders', holder(f'{target}={target};'), 'Required property is not initialized: child'),
        ('holders', holder(assign + f'{target}=null;'), 'Required object cannot be null'),
        ('holders', klass('Holder', s['field']('child', 'Node')), 'Required property needs a constructor: child'),
        ('holders', klass('Holder', s['field']('child', 'Node', 'null')), 'Required object cannot be null'),
        ('holders', None, None),
        ('holders', good, b'77'),
        ('main', main.replace(f'{v}box{access}child=new Node();', f'{v}box{access}child=null;'),
         'Required object cannot be null'),
        ('holders', holder(branches, '?Node'), b'77'),
        ('main', main, b'77'),
        ('holders', good, b'77'),
        ('children', klass('Child', s['field']('own', 'Node'), 'Holder'),
         'Required property needs a constructor: own'),
        ('children', child, b'77'),
        ('holders', holder(f'while ({v}flag) {{{assign}}}'), 'Required property must be initialized outside a loop'),
        ('holders', holder(f'if ({v}flag) {{{assign}}}' + assign), b'77'),
    ], unaffected_source='idle.' + flavor)
    for revision, checked in [(0, False), (15, True), (17, False)]:
        units = (work / f'{flavor}-fresh-{revision}' / 'units').iterdir()
        emitted = '\n'.join(unit.read_text() for unit in units)
        assert ('value_(owner_Node::require(' in emitted) == checked, (flavor, revision)


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-required-fields-') as directory:
        for language in ('php', 'cpp', 'js'):
            prove(Path(directory), language)
