"""Exercise nullable scalar field publication and field-type edits in a PHP project."""
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import revisions


fields = """
class Settings {
    public ?int $number;
    public ?float $amount = null;
    public ?bool $enabled;
    public ?int $limit = 7;
    public ?float $rate = 2.5;
    public ?bool $active = true;
}
"""
observe = """
function number(?int $v):void {
    if ($v === null) { echo "N;"; return; }
    $n int=$v; echo $n,";";
}
function amount(?float $v):void {
    if ($v === null) { echo "N;"; return; }
    $n float=$v; echo $n,";";
}
function enabled(?bool $v):void {
    if ($v === null) { echo "N;"; return; }
    $b bool=$v; echo $b ? "T;" : "F;";
}
"""
main = """
$s = new Settings();
number($s->limit); amount($s->rate); enabled($s->active);
number($s->number); amount($s->amount); enabled($s->enabled);
$s->number = 0; $s->amount = 0.0; $s->enabled = false;
number($s->number); amount($s->amount); enabled($s->enabled);
$s->number = null; $s->amount = null; $s->enabled = null;
number($s->number); amount($s->amount); enabled($s->enabled);
return 8;
"""

if __name__ == '__main__':
    with TemporaryDirectory() as tmp:
        expected = b'7;2.5;T;N;N;N;0;0;F;N;N;N;'
        broken = fields.replace('?int $number;', '?bool $number;')
        repaired = fields.replace('$limit = 7', '$limit = 12')
        diagnostic = 'Cannot pass nullable<bool_t> to a parameter of type nullable<int64>'
        revisions(Path(tmp), 'php', dict(fields=fields, observe=observe, main=main), [
            (None, None, expected),
            (None, None, expected),
            ('fields', broken, diagnostic),
            (None, None, diagnostic),
            ('fields', repaired, expected.replace(b'7;', b'12;', 1)),
        ], unaffected_source='observe.php', unaffected_revisions={4})
