"""Mixed and dynamic boxed fields retain different copy and mutation semantics."""
from pathlib import Path
import tempfile

from collection_goal_native import guard, revisions


FIELDS = 'struct Box { public mixed $m; public dynamic $d; }'
ENTRY = """
$box Box;
$box->m = (mixed)["x" => 7, "z" => 0];
$box->d = (dynamic)["x" => 7, "z" => 0];
$copy = $box;
$copy->m["x"] += 2;
$copy->d["x"] += 3;
echo (int)$box->m["x"], ":", (int)$box->d["x"], ":";
echo (int)$copy->m->has("z"), (int)$copy->d->remove("z"), (int)$box->d->has("z"), ":";
$copy->m->clear();
$copy->d->clear();
echo count($box->m), count($box->d), count($copy->m), count($copy->d);
return 8;
"""


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-boxed-containers-') as tmp:
        work = Path(tmp)
        revisions(work, 'php', dict(main=ENTRY, types=FIELDS), [
            (None, None, b'7:10:110:2000'),
            ('main', ENTRY.replace('->has("z")', '->has(true)'), None),
            ('main', ENTRY.replace('+= 3', '+= 4'), b'7:11:110:2000'),
        ])
        guard(work, 'php', 'missing', '$m mixed = []; $m["x"] += 1; return 8;', 'boxed_missing_key')
