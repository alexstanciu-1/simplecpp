"""Deferred PHP conditional-assignment destinations reject during parsing or preparation."""
from pathlib import Path
from tempfile import TemporaryDirectory

from app_namespace_native import invoke, service
from extends_native import source_edit

CASES = [
    ('function probe(mixed $b):void {$b["x"]["y"] ??= 3;}', 'requires a local'),
    ('function probe(dynamic &$b):void {$b["x"] ??= 3;}', 'requires a local'),
    ('global $n int = 1; $n ??= 2;', 'global writes are deferred'),
    ('class H {public static int $n = 1;} H::$n ??= 3;', 'compound'),
    ('$b mixed = null; $b ??= 3;', 'nullable carrier'),
    ('class H {} $h ?H = null; $h ??= new H();', 'nullable carrier'),
    ('$v vector<mixed> = []; $v[0]["x"] ??= 3;', 'Unavailable runtime specialization'),
    ('$s string = "x"; $s[0] ??= 3;', 'mutable collection'),
    ('$a mixed = []; $a[] ??= 3;', 'requires a key'),
    ('class H {public ?int $n;} $h ?H = null; $h?->n ??= 3;', 'Expected call statement'),
    ('$b mixed = []; $x = ($b["x"] ??= 3);', 'Expected )'),
    ('$b dynamic = []; for ($b["x"] ??= 3; false; ) {}', 'Expected update operator'),
    ('$b mixed = []; $b->{"x"} ??= 3;', 'requires a dynamic receiver'),
    ('$b dynamic = []; $b->{42} ??= 3;', 'requires a string'),
    ('class H {} $b mixed = ["x" => 0]; $b["x"] ??= new H();', 'Unsupported boxing'),
]

with TemporaryDirectory(prefix="scpp-coalesce-scope-") as tmp:
    work = Path(tmp)
    app = work / "app"
    app.mkdir()
    (app / "module.json").write_text('{"entry":"main.php"}')
    source = app / "main.php"
    source_edit(source, "return 8;")
    proc = service(app, work / "out")
    try:
        for code, diagnostic in CASES:
            source_edit(source, code + "return 8;")
            result = invoke(proc)
            assert diagnostic in result.get("error", ""), (code, result)
    finally:
        proc.stdin.close()
        proc.wait()
