"""Conflict provenance, data/storage, called-type methods and constructors from trait sources."""
import argparse
import tempfile
from pathlib import Path

from collection_goal_native import revisions
from protected_static_native import sources


def prove(work, flavor):
    _, (s, klass, method, static, sep) = sources(flavor)
    def trait(name, members):
        return 'trait ' + name + ' {\n' + members + '\n}' + (';' if flavor == 'cpp' else '')
    public = 'public: ' if flavor == 'cpp' else 'public '
    const, prop, field, ctor, local, parent, late = {
        'php': ('const int STEP=2;', 'static int $n=1;', '$n', 'public function __construct(int $v=4)', 'self::', 'parent::', 'static::'),
        'cpp': ('static constexpr int STEP=2;', 'static int n=1;', 'n', 'public: Storage(int v=4)', 'Self::', 'Parent::', 'Static::'),
        'js': ('const STEP:int=2;', 'static n:int=1;', 'n', 'constructor(v:int=4)', 'class.', 'super.', 'static::'),
    }[flavor]
    x = s['field']('x', 'int', '0')
    this = s['v'] + 'this' + s['access']
    constructor = ctor + '{' + this + 'x=' + s['v'] + 'v;}'
    members = public + const + public + prop + x + constructor
    body = f'{local}{field}+={local}STEP;return {local}{field}+{parent}bonus()+{late}tag();'
    members += static('bump', body)
    storage = trait('Storage', members)
    base = klass('Base', static('bonus', 'return 10;') + static('tag', 'return 1;'))
    host = klass('Host', 'use Storage;', 'Base')
    peer = klass('Peer', 'use Storage;', 'Base')
    child = klass('Child', static('tag', 'return 4;'), 'Host')
    main = s['infer']('o', 'new Host()') + f'echo {s["v"]}o{s["access"]}x,",",Host{sep}bump(),",",Child{sep}bump(),",",Peer{sep}bump();return 8;'
    initial = b'4,14,19,14'
    other_ctor = constructor.replace('Storage(', 'Other(')
    local_ctor = constructor.replace('Storage(', 'Host(').replace('=4)', '=7)')
    origin = lambda text: 'namespace Library {use Base;' + text + '}'
    import_trait = 'use Library' + {'php': chr(92), 'cpp': '::', 'js': '.'}[flavor] + 'Storage;'
    host = import_trait + host
    peer = import_trait + peer
    sources_map = dict(storage=origin(storage), base=base, host=host, peer=peer, child=child, main=main,
                       other=trait('Other', other_ctor))
    edits = [
        (None, None, initial), (None, None, initial),
        ('storage', storage.replace('STEP=2','STEP=3').replace('STEP:int=2','STEP:int=3'), b'4,15,21,15'),
        ('storage', storage.replace('bonus()', 'unknown()'), 'ancestor method'),
        (None, None, 'storage.' + flavor), ('storage', storage, initial),
        ('host', klass('Host', local_ctor + 'use Storage;', 'Base'), b'7,14,19,14'),
        ('host', klass('Host', local_ctor + 'use Storage, Other;', 'Base'), 'Storage and Other'),
        ('host', host, initial),
        ('host', klass('Host', x + 'use Storage;', 'Base'), 'Host and Library'),
        (None, None, 'first at '), ('host', host, initial),
        ('other', trait('Other', x), initial),
        ('host', klass('Host', 'use Storage, Other;', 'Base'), 'Storage and Other'),
        ('host', host, initial),
        ('storage', storage.replace(const, const + public + const.replace('STEP', 'UNUSED').replace('int', 'Missing')), 'Unknown or unsupported constant type'),
        ('storage', storage, initial),
    ]
    base_ctor = {'php': 'public function __construct(){echo "B";}', 'js': 'constructor(){echo "B";}',
                 'cpp': 'public: Base(){echo "B";}'}[flavor]
    parent_init = {'php': '{parent::__construct();', 'js': '{super();', 'cpp': ': Base(){'}[flavor]
    edits.extend([
        ('base', klass('Base', base_ctor + static('bonus', 'return 10;') + static('tag', 'return 1;')), b'B'+initial),
        ('storage', storage.replace(constructor, constructor.replace('{',parent_init,1)), b'B'+initial),
        ('base', base, 'Explicit parent call requires'),
        ('storage', storage, initial),
    ])
    private = 'private: ' if flavor == 'cpp' else 'private '
    edits.extend([
        ('storage', storage.replace(public+const,private+const).replace(public+prop,private+prop+('public: ' if flavor == 'cpp' else '')), initial),
        ('main', f'echo Host{sep}STEP;return 8;', 'private'),
        ('main', f'echo Host{sep}{field};return 8;', 'private'),
        ('main', main, initial),
        ('storage', storage, initial),
        ('other', trait('Other',public+const+public+prop), initial),
        ('host', klass('Host','use Storage,Other;','Base'), 'Conflicting trait constant'),
        ('other', trait('Other',public+prop), 'Conflicting trait static property'),
        ('host', host, initial),
    ])
    revisions(work, flavor, sources_map, [(name, origin(text) if name == 'storage' else
        import_trait + text if name == 'host' and text != host else text, expected)
        for name, text, expected in edits])


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-trait-members-') as tmp:
        work = args.output or Path(tmp)
        work.mkdir(exist_ok=True)
        for flavor in ('php', 'cpp', 'js'):
            prove(work, flavor)
