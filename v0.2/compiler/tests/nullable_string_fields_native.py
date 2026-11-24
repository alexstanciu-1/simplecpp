"""Nullable string instance storage across construction, mutation, reset and type edits."""
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import revisions


def prove(work):
    fields = """class TextState {
        public ?string $implicit;
        public ?string $absent = null;
        public ?string $emptyText = "";
        public ?string $text = "seed";
        public ?string $value;
        public function __construct(?string $initial) { $this->value = $initial; }
        public function reset():void { $this->value = null; }
    }"""
    observe = """function show(?string $s):void {
        echo "[", ($s ?? "absent"), "]";
    }"""
    main = """
        $a = new TextState(null);
        $b = new TextState("");
        $c = new TextState("first");
        show($a->implicit); show($a->absent); show($a->emptyText); show($a->text);
        show($a->value); show($b->value); show($c->value);
        $a->value = $c->value; $c->reset();
        show($a->value); show($c->value);
        $a->value = ""; show($a->value);
        $a->reset(); show($a->value);
        $a->value = "after"; show($a->value);
        return 8;
    """
    expected = b'[absent][absent][][seed][absent][][first][first][absent][][absent][after]'
    broken = fields.replace('?string $text = "seed"', 'int $text = 42')
    repaired = fields.replace('"seed"', '"repaired"')
    diagnostic = 'Unsupported explicit conversion from int64 to string'
    revisions(work, 'php', dict(main=main, fields=fields, observe=observe), [
        (None, None, expected),
        (None, None, expected),
        ('fields', broken, diagnostic),
        (None, None, diagnostic),
        ('fields', repaired, expected.replace(b'seed', b'repaired')),
    ], unaffected_source='observe.php', unaffected_revisions={4})


if __name__ == '__main__':
    with TemporaryDirectory() as tmp:
        prove(Path(tmp))
