"""Family selection, ordered literal construction and recovery in all four flavors."""
from pathlib import Path
from tempfile import TemporaryDirectory

from classes_native import fixture
from collection_goal_native import guard, revisions


def prove(work, flavor):
    if flavor == 'py':
        source = ('m: mixed = (mixed)["Alex", 12]\nd: dynamic = (dynamic)[[3]]\n'
                  'return 8 if count(m) == 2 and count(d) == 1 else 1')
        invalid = source.replace('(dynamic)[[3]]', '(dynamic)[(mixed)[3]]')
        expected = b''
    else:
        syntax = fixture(flavor)
        v = syntax['v']
        infer = syntax['infer']
        source = infer('m', '(mixed)["Alex", 12]') + infer('d', '(dynamic)[[3]]')
        source += infer('child', v + 'd[0]') + f'echo count({v}m),count({v}child);'
        expected = b'21'
        if flavor in ('php', 'js'):
            family = 'mixed' if flavor == 'php' else 'dynamic'
            source += syntax['function']('size', 'int', [('items', family)], f'return count({v}items);')
            source += infer('items', '[]') + infer('list', '["Alex", 12]')
            source += f'echo size({v}items),size({v}list);'
            source += syntax['function']('key', 'string', [], 'echo "K";return "age";')
            source += syntax['function']('number', 'int', [], 'echo "V";return 12;')
            source += infer('row', '["name" => ["Alex"], key() => number()]')
            source += infer('object', '{name: ["Alex"], "age": 12}')
            source += infer('names', v + 'row["name"]') + infer('values', v + 'object["name"]')
            source += f'echo (string){v}names[0],(int){v}row["age"],(string){v}values[0],(int){v}object["age"];'
            source += infer('nested', '["data" => [["id" => 1], ["id" => 2]]]')
            source += infer('rows', v + 'nested["data"]') + infer('second', v + 'rows[1]')
            source += f'echo (int){v}second["id"];'
            source += infer('copy', v + 'list') + f'{v}copy[0]="edited"; echo (string){v}list[0];'
            expected += b'02KVAlex12Alex122' + (b'Alex' if flavor == 'php' else b'edited')
            for family in ('mixed', 'dynamic'):
                entries = ','.join(f'{i} => {i}' for i in range(33))
                source += f'echo count(({family})[{entries}]);'
                source += f'echo count(({family})[' + ','.join(['0'] * 33) + ']);'
                expected += b'3333'
        source += 'return 8;'
        invalid = source.replace('(dynamic)[[3]]', '(dynamic)[(mixed)[3]]')
    edits = [(None, None, expected), (None, None, expected), ('main', invalid, 'Unsupported boxed conversion')]
    if flavor in ('php', 'js'):
        for literal in ('{name: ["n" => 1]}', '["name" => {n: 1}]'):
            edits.append(('main', source.replace('{name: ["Alex"], "age": 12}', literal), 'Unsupported boxed conversion'))
    if flavor == 'py':
        edits.append(('main', 'return count([])', 'explicitly typed'))
    if flavor == 'cpp':
        edits.append(('main', source.replace('(mixed)["Alex", 12]', '["Alex", 12]'), 'explicitly typed'))
    edits.append(('main', source, expected))
    sources = {'main': source}
    if flavor in ('php', 'js'):
        body = infer('copy', v + 'items') + f'{v}copy[0]=9;return (int){v}items[0];'
        mixed = syntax['function']('observe', 'int', [('items', 'mixed')], body)
        dynamic = syntax['function']('observe', 'int', [('items', 'dynamic')], body)
        sources['observer'] = mixed
        caller = 'echo observe([1]);return 8;'
        edits.extend([('main', caller, b'1'), ('observer', dynamic, b'9'),
                      ('observer', None, 'Unknown function'), ('observer', mixed, b'1')])
    revisions(work, flavor, sources, edits)
    if flavor in ('php', 'js'):
        marker = syntax['function']('mark', 'int', [('x', 'int')], f'echo {v}x; return {v}x;')
        for family in ('mixed', 'dynamic'):
            duplicate = f'({family})[1 => mark(1), "1" => mark(2), 3 => mark(3)]'
            guard(work, flavor, family + '-duplicate', marker + infer('x', duplicate) + 'return 1;',
                  'duplicate_initializer_key', expected_stdout=b'123')


if __name__ == '__main__':
    with TemporaryDirectory() as temporary:
        for flavor in ('php', 'js', 'cpp', 'py'):
            prove(Path(temporary), flavor)
