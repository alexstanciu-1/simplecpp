"""An abstract consumer supplies one callable slot to siblings and reuses existing methods."""
import argparse
import tempfile
from pathlib import Path

from app_namespace_native import artifacts
from collection_goal_native import revisions
from parent_methods_native import syntax


def prove(work, flavor):
    s, _, klass, _, method = syntax(flavor)
    def hook(visibility='public'):
        return {'php': f'{visibility} abstract function step():int;',
                'cpp': f'{visibility}: virtual int step() = 0;',
                'js': f'{visibility} abstract step():int;'}[flavor]
    def trait(name, body):
        return f'trait {name} {{ {body} }}' + (';' if flavor == 'cpp' else '')
    def leaf(name, n, visibility='public'):
        return klass(name, method('step', f'return {n};', intent='override', access=visibility), 'Middle')
    counter = trait('Counter', hook('protected') + method('next', f'return {s["v"]}this{s["access"]}step()+1;'))
    base = 'abstract ' + klass('Base', 'use Counter, Exposed;')
    middle = 'abstract ' + klass('Middle', '', 'Base')
    left, right = leaf('Left', 4), leaf('Right', 8)
    read = s['function']('read', 'int', [('b', 'Base')], f'return {s["v"]}b{s["access"]}next();')
    main = 'echo read(new Left()), read(new Right()); return 8;'
    # An inherited concrete method remains ordinary in a second abstract consumer.
    plain = klass('Plain', method('step', 'return 6;'))
    reuse = 'abstract ' + klass('Reuse', 'use Counter;', 'Plain') + klass('Finished', '', 'Reuse')
    main = s['infer']('f', 'new Finished()') + f'echo {s["v"]}f{s["access"]}next();' + main
    revisions(work, flavor, dict(counter=counter, exposed=trait('Exposed', hook()), base=base, middle=middle,
                                left=left, right=right, read=read, plain=plain, reuse=reuse, main=main), [
        (None, None, b'759'), (None, None, b'759'),
        ('left', klass('Left', '', 'Middle'), 'must implement abstract method: step'),
        (None, None, 'must implement abstract method: step'),
        ('left', leaf('Left', 4, 'protected'), 'cannot narrow visibility'),
        ('left', leaf('Left', 3), b'749'), ('left', left, b'759'),
        ('base', base.replace('use Counter, Exposed;', 'use Counter, Exposed;' + hook()), b'759'),
        ('base', base, b'759'),
        ('plain', klass('Plain'), 'must implement abstract method: step'),
        ('plain', plain, b'759'),
    ], unaffected_source='right.' + flavor, unaffected_revisions={1, 5, 6})
    for revision in (0, 7, 10):
        files = artifacts(work / f'{flavor}-fresh-{revision}')
        assert sum(code.count(b'scpp_virtual_step() = 0;') for code in files.values()) == 1


def signatures(work, flavor):
    s, _, klass, _, method = syntax(flavor)
    def param(default):
        return {'php': 'int $n=', 'cpp': 'int n=', 'js': 'n:int='}[flavor] + str(default)
    def hook(result, default):
        args = param(default)
        return {'php': f'public abstract function make({args}):{result};',
                'cpp': f'public: virtual {result} make({args}) = 0;',
                'js': f'abstract make({args}):{result};'}[flavor]
    def requirement(name, result, default):
        return 'trait ' + name + ' {' + hook(result, default) + '}' + (';' if flavor == 'cpp' else '')
    base = 'abstract ' + klass('Factory', 'use General, Specific;')
    leaf = klass('Concrete', method('make', f'echo {s["v"]}n; return new Dog();', result='Dog',
                                    params=param(9), intent='override'), 'Factory')
    produce = s['function']('produce', 'Dog', [('f', 'Factory')], f'return {s["v"]}f{s["access"]}make();')
    sources = dict(animal=klass('Animal'), dog=klass('Dog', '', 'Animal'), general=requirement('General', 'Animal', 1),
                   specific=requirement('Specific', 'Dog', 2), factory=base, leaf=leaf, produce=produce,
                   main='produce(new Concrete()); return 8;')
    revisions(work, flavor, sources, [
        (None, None, b'2'), (None, None, b'2'),
        ('factory', base.replace('use General, Specific;', 'use General, Specific;' + hook('Dog', 7)), b'7'),
        ('factory', base, b'2'), ('specific', requirement('Specific', 'Dog', 3), b'3'),
        ('dog', klass('Dog'), 'Trait hook signature'), ('dog', sources['dog'], b'3'),
    ])


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-abstract-hooks-') as tmp:
        work = args.output or Path(tmp)
        work.mkdir(exist_ok=True)
        variant = work / 'signatures'
        variant.mkdir()
        for flavor in ('php', 'cpp', 'js'):
            prove(work, flavor)
            signatures(variant, flavor)
