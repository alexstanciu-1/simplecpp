"""Grammar inputs retained from parser units plus boundary/stress cases for this owner.

Only literal Parsing_Test::parse/reject arguments and PHS nowdocs are harvested.
Whole unit execution still requires unmigrated semantic/session/view owners.
"""
import re
from pathlib import Path

UNITS = ['parsing', 'parameter_parsing', 'variable_parsing', 'struct_parsing', 'metaprogramming_parsing']

def cases(root: Path):
    texts = ['', 'return;', 'return 42;', 'function f(): int {}',
             'return 1; function f(): int {} return 2; const N = 3; return N;']
    provenance = []
    for unit in UNITS:
        path = root / f'compiler/reference/pre-rewrite/tests/03_parse/{unit}.php'
        source = path.read_text()
        for match in re.finditer(r"Parsing_Test::(?:parse|reject)\(\s*'((?:\\.|[^'\\])*)'", source):
            # PHP single-quoted literals recognize only escaped slash and quote.
            text = re.sub(r"\\([\\'])", r'\1', match[1])
            texts.append(text)
            provenance.append({'file':str(path.relative_to(root)), 'line':source[:match.start()].count('\n')+1,'text':text})
        for match in re.finditer(r"<<<'PHS'\n(.*?)\nPHS", source, re.S):
            texts.append(match[1])
            provenance.append({'file':str(path.relative_to(root)), 'line':source[:match.start()].count('\n')+1,'text':match[1]})
    texts += [
        'echo 1, 2+3, f(4); return;', '$x int; $x = 2; $x; { $y Box<int> = new Box<int>(); }',
        'if (true) { echo 1; } else if (false) { return; } else { echo 2; }',
        'while ($x<10) { $x = $x+1; if ($x<5) { echo $x; } }',
        'if constexpr (true) { const N: int = 2; } else { echo 0; }',
        'if consteval { return 1; } else { return 2; }',
        'const N: int = 2; constexpr function f($x int): int { return $x+N; }',
        'consteval function f(const Box<int> &$x, int &$y): int { return $x->size+$y; }',
        'struct Box { public int $x; public int $items[4+1]; public function get(): int { return $this->x; } public const function size(): int { return 4; } }',
        'template<typename T, int N> struct Box { public T $items[N]; }',
        'template<typename T> constexpr function identity($x T): T { return $x; }',
        '/* é */ echo "hé"; // 尾\n', 'function empty(): void {} function other(): void {}',
        'if (true) echo 1;', 'while (true) {} else {}', 'if consteval {} else if (true) {}',
        'struct S { private int $x; }', 'struct S { public int $x=1; }',
        'struct S { public int $x[ ]; }', 'struct S { public function f() {} }',
        'function f($x int=1): int {}', 'function f($x int,): int {}',
        'function f(int $x): int {}', 'function f(const int $x): int {}',
        'function f(&$x int): int {}', 'template<> struct S {}',
        'template<typename T,> struct S {}', 'template<typename T> const N=1;',
        'constexpr const N=1;', 'const N;', 'echo;', 'echo 1,;',
        '{ const N=1; function bad(): int {} }', '{ struct S {} }',
        'return 1; function broken(): int { return 2;', 'return "unfinished',
        '{'*128+'$x int = 42; return $x;'+'}'*128,
        'if (true) {} else '*128+'{}',
        'function many('+','.join('$p'+str(i)+' int' for i in range(300))+'): int { return 1; }',
        'echo '+','.join('f('+str(i)+')' for i in range(500))+';',
        '$v '+'list<'*1000+'int'+'>'*1000+';',
        'struct Wide {'+''.join('public int $f'+str(i)+';' for i in range(300))+'}',
        ''.join('$v'+str(i)+' int = f('+str(i)+');' for i in range(1000)),
    ]
    return list(dict.fromkeys(texts)), provenance
