"""Direct trait fields use ordinary field lowering and retain collision/cache guarantees."""
import json
from pathlib import Path
import subprocess
import tempfile

ROOT = Path(__file__).resolve().parents[2]
with tempfile.TemporaryDirectory(prefix="scpp-trait-properties-") as directory:
    work = Path(directory)
    source = work / "source"
    source.mkdir()
    output = work / "output"
    trait = source / "span.php"
    trait.write_text('''<?php
namespace trait_fields;
trait Span {
    public int $first /** uint32 */ = 2;
    private ?Row $observer /** weak<Row> */ = null;
    public function remember(Row $row): void { $this->observer = $row; }
}
''')
    consumer = source / "main.php"
    consumer.write_text('''<?php
namespace trait_fields;
class Row {}
class First { use Span; }
class Second { use Span; }
$a = new First();
$b = new Second();
$a->first = 7;
echo $a->first, ":", $b->first, "\\n";
''')

    def convert(ok=True):
        result = subprocess.run(
            ["php", str(ROOT / "tools/php_portability/convert.php"), str(source), str(output)],
            capture_output=True, text=True, timeout=30)
        assert (result.returncode == 0) == ok, result.stdout + result.stderr
        return result

    convert()
    generated = (output / "main.phs").read_text()
    assert generated.count("$first uint32 = 2") == 2, generated
    assert generated.count("$observer weak<Row> = null") == 2, generated
    host = subprocess.run(["php", "-r", 'require $argv[1]; require $argv[2];',
                           str(trait), str(consumer)], capture_output=True, text=True, timeout=30)
    assert host.returncode == 0 and host.stdout == "7:2\n", host.stdout + host.stderr
    assert json.loads(convert().stdout)["converted"] == 0
    trait.write_text(trait.read_text().replace("= 2;", "= 23;"))
    assert json.loads(convert().stdout)["converted"] == 2
    assert (output / "main.phs").read_text().count("$first uint32 = 23") == 2

    manifest = (output / ".scpp-portability.json").read_bytes()
    for declaration, message in [
        ("class Collision { use Span; public int $first = 0; }", "trait field collision first"),
        ("trait Other { public int $first = 1; } class Collision { use Span, Other; }", "trait field collision first"),
        ("trait Bad { public static int $field = 0; }", "instance fields"),
        ("trait Bad { public const VALUE = 0; }", "instance fields"),
    ]:
        (source / "bad.php").write_text("<?php\nnamespace trait_fields;\n" + declaration)
        error = convert(False).stderr
        assert message in error and "bad.php:" in error, error
        assert (output / ".scpp-portability.json").read_bytes() == manifest
        (source / "bad.php").unlink()

print("PASS direct trait properties: state, typed expansion, collisions and cache invalidation")
