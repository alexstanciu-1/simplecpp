"""Directional shared-value slots through class/interface views and resident/fresh recovery."""
import argparse
import tempfile
from pathlib import Path

from application_vectors_native import measure
from collection_goal_native import revisions
from interfaces_native import forms


def prove(work, flavor):
    s, _, klass, method, _, implement = forms(flavor)
    v, access = s['v'], s['access']

    def param(name, kind):
        return {'php': f'{kind} ${name}', 'cpp': f'{kind} {name}', 'js': f'{name}:{kind}'}[flavor]

    def exchange(intent, arg, result, mark, default):
        params = param('x', arg) + ',' + param('tag', 'int') + '=' + str(default)
        return method('exchange', f'echo "{mark}",{v}tag;return new {result}();', result, params, intent)

    dog = klass('Dog', '', 'Animal')
    base = klass('Base', method('make', 'echo "B";return new Dog();', 'Dog', intent='virtual')
                 + exchange('virtual', 'Pup', 'Animal', 'A', 1))
    interface = {'php': 'interface Factory { public function make():Animal; }',
                 'js': 'interface Factory { make():Animal; }',
                 'cpp': 'interface Factory { Animal make(); };'}[flavor]
    mid = implement('Middle', exchange('override', 'Dog', 'Dog', 'M', 2), 'Base', ('Factory',))
    leaf = klass('Leaf', method('make', 'echo "L";return new Dog();', 'Dog', intent='override')
                 + exchange('override', 'Animal', 'Pup', 'T', 3), 'Middle')
    api = ''
    for kind in ('Base', 'Factory', 'Middle', 'Leaf'):
        api += s['function']('make'+kind, 'Animal', [('y', kind)], f'return {v}y{access}make();')
    for kind in ('Base', 'Middle', 'Leaf'):
        api += s['function']('exchange'+kind, 'Animal', [('y', kind)], f'return {v}y{access}exchange(new Pup());')
    main = s['infer']('x', 'new Leaf()')
    for fn in ('makeBase', 'makeFactory', 'makeMiddle', 'makeLeaf', 'exchangeBase', 'exchangeMiddle', 'exchangeLeaf'):
        main += f'{fn}({v}x);'
    sources = dict(animal=klass('Animal'), dog=dog, pup=klass('Pup', '', 'Dog'),
                   base=base, factory=interface, middle=mid, leaf=leaf, api=api, main=main+'return 8;')
    # Shared structs are optional owners. Check both an adjusted live view and absence.
    sources['points'] = s['record']('Point') + s['record']('Colored', '', 'Point')
    wide, narrow = 'shared<Point>', 'shared<Colored>'
    def pick(arg, result, intent, body):
        return method('pick', body, result, param('x', arg) + ',' + param('item', narrow), intent)
    root = s['record']('Picker', pick(narrow, wide, 'virtual', f'return {v}item;'))
    child = s['record']('ConcretePicker', pick(wide, narrow, 'override', f'if ({v}x !== {v}item) {{return null;}} return {v}item;'), 'Picker')
    sources['pickers'] = root + child
    sources['api'] += s['function']('pickItem', wide, [('p', 'shared<Picker>'), ('x', narrow)],
                                    f'return {v}p{access}pick({v}x,{v}x);')
    sources['main'] = main + s['infer']('picker', 'new ConcretePicker()') + s['infer']('item', 'new Colored()')
    sources['main'] += f'if (pickItem({v}picker,{v}item) !== {v}item || pickItem({v}picker,null) !== null) {{return 3;}}return 8;'
    normal = b'LLLLT1T2T3'
    ordinary = base.replace(method('make', 'echo "B";return new Dog();', 'Dog', intent='virtual'),
                            method('make', 'echo "B";return new Dog();', 'Dog'))
    local_make = method('make', 'echo "L";return new Dog();', 'Dog', intent='override')
    inherited = leaf.replace(local_make, '')
    final_make = {'cpp': 'public: Dog make() override final {echo "F";return new Dog();}',
                  'php': 'public override final function make():Dog {echo "F";return new Dog();}',
                  'js': 'override final make():Dog {echo "F";return new Dog();}'}[flavor]
    sealed = klass('Sealed', final_make, 'Base')
    parent = {'cpp': 'Middle::make()', 'php': 'parent::make()', 'js': 'super.make()'}[flavor]
    changes = [
        (None, None, normal), (None, None, normal),
        ('leaf', leaf.replace('"T"', '"E"'), b'LLLLE1E2E3'),
        ('dog', klass('Dog'), 'match exactly'), (None, None, 'match exactly'),
        ('dog', dog, b'LLLLE1E2E3'), ('leaf', leaf, normal),
        ('factory', interface.replace('Animal', '?Animal'), 'match exactly'),
        ('factory', interface, normal), ('middle', None, None), ('middle', mid, normal),
        ('pickers', root + child.replace(param('x', wide), param('x', 'shared<Picker>')), 'match exactly'),
        ('pickers', root + child, normal),
        ('base', ordinary, b'BLLLT1T2T3'), ('base', base, normal),
        ('leaf', leaf.replace(local_make, method('make', 'return '+parent+';', 'Dog', intent='override')), b'BBBBT1T2T3'),
        ('leaf', inherited, b'BBBBT1T2T3'), ('sealed', sealed, b'BBBBT1T2T3'),
        ('middle', mid.replace('Base', 'Sealed'), b'FFFFT1T2T3'),
        ('leaf', leaf, 'Cannot override final'), ('middle', mid, normal),
        ('leaf', leaf.replace(param('x', 'Animal'), param('x', 'Pup')), 'match exactly'),
        ('leaf', leaf, normal),
        ('other', interface.replace('Factory', 'DogFactory').replace('Animal', 'Dog'), normal),
        ('middle', mid.replace('Factory', 'Factory, '+('public ' if flavor == 'cpp' else '')+'DogFactory'), normal),
        ('other', interface.replace('Factory', 'DogFactory').replace('Animal', 'Leaf'), 'match exactly'),
        ('other', interface.replace('Factory', 'DogFactory').replace('Animal', 'Dog'), normal),
    ]
    revisions(work, flavor, sources, changes)
    measure(work / f'{flavor}-fresh-{len(changes)-1}', work / f'{flavor}-fresh-native', normal)


if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('--work', type=Path)
    args = parser.parse_args()
    work = args.work or Path(tempfile.mkdtemp(prefix='scpp-shared-variance-'))
    work.mkdir(exist_ok=True)
    for flavor in ('php', 'cpp', 'js'):
        prove(work, flavor)
