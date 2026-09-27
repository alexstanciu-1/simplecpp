"""Prove shared trait accessors preserve each consumer's explicit object field type."""
import json
from pathlib import Path
import subprocess
import tempfile

ROOT = Path(__file__).resolve().parents[2]
TOOLS = ROOT / "tools/php_portability"


def run(command, ok=True):
    result = subprocess.run(list(map(str, command)), capture_output=True, text=True)
    assert (result.returncode == 0) == ok, (command, result.stdout, result.stderr)
    return result


def main():
    with tempfile.TemporaryDirectory(prefix="scpp-trait-field-types-") as directory:
        source = Path(directory) / "php"
        output = Path(directory) / "phs"
        source.mkdir()
        (source / "facts.php").write_text("<?php\nnamespace demo;\nfinal class FirstFacts {}\nfinal class SecondFacts {}\n")
        trait = '''<?php
namespace demo;
trait Access {
    public function read(): ?object /** @field-type facts */ { return $this->facts; }
    public function write(object /** @field-type facts */ $value): void { $this->facts = $value; }
    public function clear(): void { $this->facts = null; }
}
trait Label {
    public function label(): string { return "facts"; }
}
'''
        (source / "access.php").write_text(trait)
        consumers = '''<?php
namespace demo;
abstract class Base {
    public abstract function number(): int;
}
final class First extends Base {
    use Access, Label;
    private ?FirstFacts $facts = null;
    public function number(): int { return 1; }
}
final class Second {
    use Access;
    private ?SecondFacts $facts = null;
}
'''
        (source / "consumers.php").write_text(consumers)
        command = ["php", TOOLS / "convert.php", source, output, "--stats"]
        report = json.loads(run(command).stdout)
        assert report["converted"] == 3
        generated = (output / "consumers.phs").read_text()
        assert "public abstract function number(): int;" in generated
        for name in ("FirstFacts", "SecondFacts"):
            assert f"function read(): nullable<{name}>" in generated
            assert f"function write({name} $value): void" in generated
        assert "@field-type" not in generated
        assert "trait Access" not in (output / "access.phs").read_text()
        assert json.loads(run(command).stdout)["reused"] == 3
        run(["php", TOOLS / "check.php", source])

        host = '''require $argv[1] . '/facts.php'; require $argv[1] . '/access.php'; require $argv[1] . '/consumers.php';
$a = new demo\\First(); $b = new demo\\Second(); $facts = new demo\\FirstFacts();
if ($a->read() !== null || $b->read() !== null || $a->number() !== 1 || $a->label() !== 'facts') { exit(1); }
$a->write($facts);
if ($a->read() !== $facts || $b->read() !== null) { exit(2); }
try { $a->write(new demo\\SecondFacts()); exit(3); } catch (TypeError $expected) {}
if ($a->read() !== $facts) { exit(4); }
$a->clear(); if ($a->read() !== null) { exit(5); }
'''
        run(["php", "-r", host, source])

        # The consumer's explicit field declaration participates in its own cache key.
        (source / "consumers.php").write_text(consumers.replace("?FirstFacts", "?SecondFacts"))
        assert json.loads(run(command).stdout)["converted"] == 1
        assert "nullable<FirstFacts>" not in (output / "consumers.phs").read_text()
        (source / "consumers.php").write_text(consumers)
        run(command)
        (source / "access.php").write_text(trait.replace('return "facts"', 'return "changed"'))
        assert json.loads(run(command).stdout)["converted"] == 2
        (source / "access.php").write_text(trait)
        run(command)

        # Invalid bindings fail before publication, with trait and consumer context.
        manifest = (output / ".scpp-portability.json").read_bytes()
        rejects = [
            consumers.replace("private ?FirstFacts $facts = null;", ""),
            consumers.replace("private ?FirstFacts $facts = null;", "private int $facts = 0;"),
            consumers.replace("private ?FirstFacts $facts", "private static ?FirstFacts $facts"),
            consumers.replace("private ?FirstFacts $facts = null;", "").replace("abstract class Base {", "abstract class Base { protected ?FirstFacts $facts = null;"),
        ]
        for invalid in rejects:
            (source / "consumers.php").write_text(invalid)
            error = run(command, ok=False).stderr
            assert "access.php:" in error and "field facts in demo\\First" in error, error
            assert (output / ".scpp-portability.json").read_bytes() == manifest
        (source / "consumers.php").write_text(consumers)
        for invalid in (trait.replace("@field-type facts", "@field-type"), trait.replace("object /**", "string /**"), trait.replace("return $this->facts;", "return new object /** @field-type facts */();")):
            (source / "access.php").write_text(invalid)
            error = run(command, ok=False).stderr
            assert "access.php:" in error, error
            assert (output / ".scpp-portability.json").read_bytes() == manifest
        (source / "access.php").write_text(trait)
        run(command)
    print("Trait field types: PHP identity/type checks, concrete emission, abstract hooks, composition, cache and rejections passed")


if __name__ == "__main__":
    main()
