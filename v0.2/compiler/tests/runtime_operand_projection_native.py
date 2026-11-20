"""Native bootstrap proof for reading runtime operands directly from call syntax."""
import json
from pathlib import Path
import subprocess
import tempfile

from collection_goal_native import revisions
from virtual_methods_native import forms


# Receiver, written argument identities, resolved binding name and qualifier mode.
CASES = [
    (None, [], 'free', False),
    (None, ['A', 'B'], 'free', False),
    ('R', [], 'member', False),
    ('R', ['A', 'B'], 'member', False),
    ('R', ['B', 'A'], 'qualified', True),
    (None, ['T', 'B'], 'enum_from_value', False),
    (None, ['A'], 'enum_from_value', False),
]


def fixture(flavor, cases):
    s, method, klass = forms(flavor)
    v, access = s['v'], s['access']
    fn, infer = s['function'], s['infer']
    types = klass('Node', method('virtual')) + klass('TypeMarker', '', 'Node')
    types += klass('CallSyntax', s['field']('receiver', '?Node') + s['field']('written', 'vector<Node>'))
    receiver = f'{v}call{access}receiver'
    written = f'{v}call{access}written'
    # Local to a single traversal. Written index = logical index + offset.
    projection = fn('operand_offset', 'int', [('call', 'CallSyntax'), ('name', 'string'), ('qualified', 'bool')],
                    f'if ({v}name === "enum_from_value" && count({written}) > 0 && '
                    f'{written}[0] instanceof TypeMarker) {{return 1;}}'
                    f'if ({receiver} !== null && !{v}qualified) {{return -1;}}return 0;')
    projection += fn('operand_at', '?Node', [('call', 'CallSyntax'), ('offset', 'int'), ('i', 'int')],
                     f'if ({v}offset == -1 && {v}i == 0) {{return {receiver};}}'
                     f'return {written}[{v}i + {v}offset];')
    main = ''.join(infer(key, 'new TypeMarker()' if key == 'T' else 'new Node()') for key in 'RABT')
    main += infer('aliasNode', v+'A')
    main += f'if ({v}aliasNode !== {v}A) {{return 91;}}'
    main += f'if (!({v}A === {v}aliasNode)) {{return 92;}}'
    main += f'if (!({v}A === {v}aliasNode && {v}A !== {v}B)) {{return 93;}}'
    for receiver_key, arguments, name, qualified in cases:
        main += '{' + infer('call', 'new CallSyntax()')
        if receiver_key:
            main += f'{receiver}={v}{receiver_key};'
        for argument in arguments:
            main += f'{written}[]={v}{argument};'
        main += infer('offset', f'operand_offset({v}call,{json.dumps(name)},{str(qualified).lower()})')
        main += infer('n', f'count({written}) - {v}offset')
        main += f'echo {v}n,":"; for (' + s['infer']('i', '0') + f'{v}i < {v}n; {v}i++) {{'
        main += infer('operand', f'operand_at({v}call,{v}offset,{v}i)')
        for key in 'RABT':
            main += f'if ({v}operand === {v}{key}) {{echo "{key}";}}'
        main += '} echo ";";}'
    return {'types': types, 'projection': projection, 'main': main + 'return 8;'}


def expected(cases):
    return subprocess.check_output(['php', Path(__file__).with_name('runtime_operand_oracle.php')],
                                   input=json.dumps(cases).encode())


if __name__ == '__main__':
    edited = [*CASES, ('R', ['B'], 'qualified', True)]
    original, changed = expected(CASES), expected(edited)
    assert original == b'0:;2:AB;1:R;3:RAB;2:BA;1:B;1:A;'
    with tempfile.TemporaryDirectory(prefix='scpp-runtime-operands-') as directory:
        for flavor in ('cpp', 'php', 'js'):
            sources = fixture(flavor, CASES)
            revisions(Path(directory), flavor, sources, [
                (None, None, original), ('main', fixture(flavor, edited)['main'], changed),
                ('main', sources['main'], original),
            ], unaffected_source='projection.' + flavor)
