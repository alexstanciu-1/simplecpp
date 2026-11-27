"""Grouping an indexed shared owner preserves the preparation diagnostic."""
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import revisions


model = 'class Holder { public vector<int> $values; }'
setup = '$h=new Holder(); $h->values=[3]; $owners vector<Holder> =[$h];'
# Exercise a call continuation through an indexed field alongside the read control.
model += ' class Box { public vector<function<int(int)>> $ops; }'
call = ('function witness(int $n):int { echo $n; return $n; } '
        '$box=new Box(); $ops vector<function<int(int)>> = [witness(...)]; $box->ops=$ops; '
        '$boxes vector<Box> = [$box]; $boxes[0]->ops[0](6);')
controls = setup + 'echo $owners[0]->values[0];' + call
diagnostic = 'Field assignment requires writable local storage'
local = controls + '$o=$owners[0]; $o->values[0]=7; echo $h->values[0]; return 8;'

with TemporaryDirectory(prefix='indexed-owner-target-') as tmp:
    revisions(Path(tmp), 'php', {'main': setup + '$owners[0]->values[0]=7; return 8;', 'model': model}, [
        (None, None, diagnostic),
        ('main', setup + '($owners[0])->values[0]=7; return 8;', diagnostic),
        ('main', local, b'367')])
