"""Consumer-owned trait field initialization, visibility edits and incremental recovery."""
import argparse
import tempfile
from pathlib import Path

from classes_native import fixture
from collection_goal_native import revisions


def prove(work, flavor):
    s = fixture(flavor)
    this = s['v'] + 'this' + s['access']
    field, method, constructor = {
        'php': ('{visibility} {qualifier}int ${name}{default};', 'public function get():int',
                'public function __construct()'),
        'cpp': ('{visibility}: {qualifier}int {name}{default};', 'public: int get()', 'public: Box()'),
        'js': ('{visibility} {qualifier}{name}:int{default};', 'get():int', 'constructor()'),
    }[flavor]
    def declare(name, visibility, default='', qualifier='readonly '):
        return field.format(name=name, visibility=visibility, qualifier=qualifier, default=default)

    serial = declare('serial', 'private')
    limit = declare('limit', 'public', '=4')
    level = declare('level', 'protected', '=3')
    get = method + ' { return ' + this + 'serial; }'
    trait_end = '};' if flavor == 'cpp' else '}'
    fields = 'trait Fields {\n' + serial + limit + '\n' + get + '\n' + trait_end
    family = 'trait Family {' + level + trait_end
    init = this + 'serial=2;'
    host = s['record']('Box', 'use Fields; ' + constructor + ' {' + init + '}')
    base = s['record']('Base', 'use Family;')
    child = s['record']('Child', get.replace('serial', 'level'), 'Base')
    b = s['v'] + 'b' + s['access']
    c = s['v'] + 'c' + s['access']
    code = s['local']('b', 'Box') + s['local']('c', 'Child')
    main = code + f'echo {b}get(),{b}limit,{c}get(); return 8;'
    ok = b'243'
    edits = [
        (None, None, ok), (None, None, ok),
        ('box', host.replace(init, ''), 'Readonly property is not initialized: serial'),
        ('box', host.replace(init, init + init), 'Readonly property must be initialized exactly once: serial'),
        ('box', host.replace(init, this + 'limit=3;' + init), 'Readonly property must be initialized exactly once: limit'),
        ('box', host, ok),
        ('fields', fields.replace('return ', this + 'serial=1; return '), 'Cannot modify readonly property serial'),
        (None, None, 'Cannot modify readonly property serial'),
        ('fields', fields, ok),
        ('main', code + b + 'limit=5; return 8;', 'Cannot modify readonly property limit'),
        ('fields', fields.replace(limit, declare('limit', 'public', '=4', '')), b''),
        ('fields', fields, 'Cannot modify readonly property limit'),
        ('main', code + f'echo {b}serial; return 8;', 'Cannot access private field'),
        ('main', code + b + 'serial=8; return 8;', 'Cannot access private field'),
        ('main', code + f'echo {c}level; return 8;', 'Cannot access protected field'),
        ('main', code + c + 'level=8; return 8;', 'Cannot access protected field'),
        ('main', main, ok),
        ('family', family.replace('protected', 'private'), 'Cannot access private field'),
        ('family', family, ok),
    ]
    revisions(work, flavor, dict(fields=fields, family=family, box=host, base=base, child=child, main=main), edits)


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-trait-fields-') as tmp:
        work = args.output or Path(tmp)
        work.mkdir(exist_ok=True)
        for flavor in ('php', 'cpp', 'js'):
            prove(work, flavor)
