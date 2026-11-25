"""Python traversal, comparison-chain effects and a cross-flavor call after a provider edit."""
from pathlib import Path
import tempfile

from app_namespace_native import artifacts, invoke, service
from application_vectors_native import measure
from exceptions_native import fresh_build
from extends_native import source_edit
from parent_methods_native import write_app


MAIN = """values: hash<int,int> = {3: 8, 5: 9}
total: int = 0
for k, v in values.items():
    total += k + v
if not 0 < mark(total) < 30:
    return 1
if 30 < mark(total) < mark(999):
    return 2
return 8
"""
PEER = 'function mark(int $value):int { echo "M", $value; return $value; }'


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-python-bridge-') as tmp:
        work = Path(tmp)
        app, out = work / 'app', work / 'out'
        write_app(app, 'py', {'main': MAIN})
        peer = app / 'peer.php'
        source_edit(peer, PEER)
        resident = service(app, out)
        try:
            for revision, (body, expected) in enumerate([
                (PEER, b'M25M25'),
                (PEER.replace('int $value', 'int $value,int $extra'), None),
                (PEER.replace('"M"', '"N"'), b'N25N25'),
            ]):
                before = artifacts(out)
                source_edit(peer, body)
                result = invoke(resident)
                fresh = work / f'fresh-{revision}'
                clean = fresh_build(app, fresh)
                if expected is None:
                    assert 'Argument count mismatch' in result.get('error', ''), result
                    assert result['error'] == clean['error'] and artifacts(out) == before
                else:
                    assert 'error' not in result and 'error' not in clean, (result, clean)
                    assert artifacts(out) == artifacts(fresh)
                    emitted = b'\n'.join(artifacts(out).values())
                    assert emitted.count(b'.iteration_next(') == 1 and b'.iteration_current_value(' not in emitted
                    measure(out, work / f'native-{revision}', expected)
        finally:
            resident.stdin.close()
            resident.wait()
