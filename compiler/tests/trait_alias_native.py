"""Host method selection and private aliases keep independent consumer-owned bodies."""
from source_suffix import source_suffix
import argparse
import tempfile
from pathlib import Path

from collection_goal_native import revisions
from parent_methods_native import syntax


def prove(work, flavor):
    s, _, klass, _, method = syntax(flavor)
    receiver = s['v'] + 'this' + s['access']
    use = 'use Naming { name as private namespace_name; }'
    common = method('name', 'return 4;')
    def trait(name, body):
        return f'trait {name} {{\n{body}\n}}' + (';' if flavor == 'cpp' else '')
    naming = trait('Naming', common)
    custom = method('name', f'return {receiver}namespace_name() + 1;')
    reveal = method('original', f'return {receiver}namespace_name();')
    host = klass('Host', use + custom + reveal)
    other = klass('Other', 'use Naming;')
    init = s['infer']('h', 'new Host()') + s['infer']('o', 'new Other()')
    h, o = [s['v'] + name + s['access'] for name in ('h', 'o')]
    main = init + f'echo {h}name(),{h}original(),{o}name(); return 8;'
    sources = dict(naming=naming, conflicting=trait('Another', common), host=host, other=other, main=main)
    revisions(work, flavor, sources, [
        (None, None, b'544'), (None, None, b'544'),
        ('host', klass('Host', custom + reveal + use), b'544'),
        ('main', init + f'echo {h}namespace_name(); return 8;', 'private'),
        (None, None, 'private'), ('main', main, b'544'),
        ('host', klass('Host', use + reveal), b'444'),
        ('host', host, b'544'),
        ('host', host.replace('name as', 'missing as'), 'Unknown trait method for alias'),
        ('host', host.replace('private namespace_name', 'private original'), 'Trait alias conflicts'),
        ('host', host.replace(use, use + 'use Another;'), 'Naming and Another'),
        ('host', host, b'544'),
        ('naming', trait('Naming', common.replace('return 4;', f'return {receiver}missing();')), f'naming.{source_suffix(flavor)}:2:'),
        ('naming', trait('Naming', common.replace('4', '7')), b'877'),
    ])


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-trait-alias-') as tmp:
        work = args.output or Path(tmp)
        work.mkdir(exist_ok=True)
        for flavor in ('php', 'cpp', 'js'):
            prove(work, flavor)
