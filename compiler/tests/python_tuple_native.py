"""Exercise boxed tuple literals through the Python frontend and the native runtime."""
import argparse
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import guard, revisions
from application_vectors_native import measure
from exceptions_native import fresh_build
from parent_methods_native import write_app


def program(family, child_value):
    nested_write = 'alias[0][0] = 11' if family == 'dynamic' else ''
    return f"""def make(child: {family}) -> {family}:
    return (child, 7, "x",)

child: {family} = [1]
pair: {family} = make(child)
alias: {family} = pair
child[0] = 9
child = [0]
{nested_write}
empty: {family} = ()
one: {family} = ((7),)
seq: {family} = [7]
other: {family} = (7,)
if count(empty) != 0 or count(pair) != 3 or alias[0][0] != {child_value}:
    return 1
if one != other or one == seq or pair[-1] != "x" or 7 not in pair:
    return 2
nested: {family} = (one, empty)
if other not in nested or seq in nested:
    return 3
visits: int = 0
for item in pair:
    if item == 7:
        visits += 1
for pair in pair:
    visits += 1
return 8 if visits == 4 else 4
"""


def peer_roundtrip(work):
    app, out = work / 'roundtrip', work / 'roundtrip-out'
    # Cross the inline input threshold. Calls also reveal operand order and multiplicity.
    values = ', '.join(f'mark({i})' for i in range(33))
    source = f"""slots: dynamic = cppPass(phpPass(jsPass(({values}))))
return 8 if count(slots) == 33 and slots[-1] == 32 else 1
"""
    write_app(app, 'py', {'main': source})
    (app / 'peer.cpp').write_text('dynamic cppPass(dynamic x) {return x;} int mark(int x) {echo x; return x;}')
    (app / 'peer.phs').write_text('function phpPass(dynamic $x): dynamic {return $x;}')
    (app / 'peer.js').write_text('function jsPass(x: dynamic): dynamic {return x;}')
    result = fresh_build(app, out)
    assert 'error' not in result, result
    measure(out, work / 'roundtrip-native', ''.join(map(str, range(33))).encode())


def prove(work):
    for family, child in [('mixed', 1), ('dynamic', 11)]:
        case = work / family
        case.mkdir()
        source = program(family, child)
        revisions(case, 'py', {'main': source}, [
            (None, None, b''), (None, None, b''),
            ('main', source.replace('((7),)', '(7,)'), b''),
            ('main', source.replace(f'one: {family}', 'one: vector<int>'), 'Tuple requires'),
            ('main', source, b''),
        ])
        for name, operation, error in [
            ('write', 'pair[0] = 4', 'TypeError'),
            ('bounds', f'item: {family} = pair[-2]', 'IndexError'),
        ]:
            guard(case, 'py', name, f'pair: {family} = (3,)\n{operation}\nreturn 8', error)


if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('--quick', action='store_true')
    args = parser.parse_args()
    with TemporaryDirectory() as tmp:
        peer_roundtrip(Path(tmp))
        if not args.quick:
            prove(Path(tmp))
