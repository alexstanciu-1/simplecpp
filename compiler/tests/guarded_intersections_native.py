"""Compare guarded intersection arguments and their lifetime barriers with fresh builds."""
import argparse
from pathlib import Path
import tempfile

from collection_goal_native import revisions
from interfaces_native import forms
from exceptions_native import catch


def prove(work, flavor):
    syntax, evaluate, klass, method, interface, implement = forms(flavor)
    var = syntax['v'] + 'item'
    field = var + syntax['access'] + 'n'
    fn = syntax['function']
    base = klass('Base', syntax['field']('n', 'int', '4') + method('marker', 'return 0;', intent='virtual'))
    get_value = syntax['v']+'this'+syntax['access']+'n+'+syntax['v']+'delta'
    leaf = implement('Leaf', evaluate('override', get_value, default='1'), 'Base')
    measure = fn('measure', 'int', [('item', 'Base&Readable')], 'return '+var+syntax['access']+'evaluate();')
    replace = fn('replace', 'int', [('item', 'Base')], var+'=new Base();return 1;')
    param = {'cpp':'Base item','php':'Base $item','js':'item:Base'}[flavor]
    ref = {'cpp':'Base &item','php':'Base &$item','js':'&item:Base'}[flavor]
    replace = replace.replace(param, ref)
    test = var + ' instanceof Readable'
    use = 'return measure('+var+');'
    def guarded(body):
        return fn('inspect', 'int', [('item', 'Base')], body)
    positive = guarded('if ('+test+') {'+field+'=6;'+use+'}return 0;')
    prefix = 'if (!('+test+')) return 0;'
    negative = guarded(prefix + use)
    throwing = guarded('if (!('+test+')) {throw new Failure();}'+use)
    main = 'echo inspect(new Leaf()),inspect(new Base());return 8;'
    bad = 'statically satisfy'
    original = fn('consume', 'int', [('pair','Base&Readable'), ('item','Base')],
                  var+'=new Base();return measure('+syntax['v']+'pair);').replace(param, ref)
    local = syntax['local']('copy', 'Base').removesuffix(';')+'='+var+';'
    copy = syntax['v']+'copy'
    local_guard = guarded(local+prefix.replace(var,copy)+use.replace(var,copy))
    outer_v = syntax['v']
    arrow_param = {'cpp': 'Base other', 'php': 'Base $other', 'js': 'other: Base'}[flavor]
    bad_lambda = syntax['infer']('callback', f'fn({arrow_param}): int => measure({outer_v}other)')
    good_lambda = syntax['infer']('callback', f'fn({arrow_param}): int => {outer_v}other{syntax["access"]}n')
    nested_guard = guarded(prefix + good_lambda + f'return {outer_v}callback({var}) + measure({var});')
    sources = dict(base=base, cap=interface(), leaf=leaf, api=measure+replace+original,
                   guard=positive, main=main, failure=klass('Failure','', 'Exception'))
    revisions(work, flavor, sources, [
        (None,None,b'70'), (None,None,b'70'), ('guard',negative,b'50'),
        ('leaf',implement('Leaf',base='Base',interfaces=()),b'00'), ('leaf',leaf,b'50'),
        ('guard',guarded('{'+prefix+use+'}'),b'50'),
        ('guard',guarded('{'+prefix+'}'+use),bad),
        ('guard',guarded(prefix+'{ replace('+var+'); }'+use),bad),
        ('guard',guarded(prefix+'{ '+field+'=6; }'+use),b'70'),
        ('guard',guarded(prefix+var+'=new Base();'+use),bad),
        ('guard',guarded(prefix+'replace('+var+');'+use),bad), ('guard',negative,b'50'),
        ('guard',guarded('if ('+test+') {'+field+'=6;}'+use),bad),
        ('guard',guarded(prefix+use).replace(param, ref),bad),
        ('guard',guarded(prefix+'return measure('+var+')+replace('+var+');'),'Separate guarded'),
        ('guard',guarded(prefix+'echo measure('+var+'),replace('+var+');return 8;'),b'5180'),
        ('guard',guarded(prefix+bad_lambda+use),bad),
        ('guard',nested_guard,b'90'),
        ('guard',negative,b'50'), ('main','echo inspect(new Leaf());return 8;',b'5'),
        ('guard',local_guard,b'5'),
        ('guard',guarded(prefix+'return consume('+var+','+var+');'),b'5'),
        ('guard',guarded(prefix+'if (true) {replace('+var+');}'+use),bad),
        ('guard',throwing,b'5'),
        ('main','try {echo inspect(new Base());}'+catch(flavor,'Failure','caught')+
         '{echo "caught";}echo inspect(new Leaf());return 8;',b'caught5'), ('cap',None,None), ('cap',interface(),b'caught5'),
    ])
    output = work / (flavor + '-out')
    generated = '\n'.join(p.read_text() for p in (output/'units').glob('*.hpp'))
    assert generated.count('Readable*>(source.view)') == 1, flavor
    units = '\n'.join(p.read_text() for p in (output/'units').glob('*.cpp'))
    adapter = next(p.stem for p in (output/'units').glob('*.hpp')
                   if 'Readable*>(source.view)' in p.read_text())
    assert units.count('scpp_adapter_'+adapter+'(') == 1, flavor
    assert units.count('auto* scpp_guard_') == 1, flavor


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-guarded-intersection-') as tmp:
        work = args.output or Path(tmp)
        work.mkdir(exist_ok=True)
        for flavor in ('php','cpp','js'):
            prove(work, flavor)
