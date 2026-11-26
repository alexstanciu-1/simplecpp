"""Compare a bounded native closure dependency lifecycle with retained production compilation."""
from pathlib import Path
import shutil
import tempfile

from translated_preparation_recovery import ROOT, artifacts, fresh_build, invoke, measure, service, source_edit


def verify(work):
    app, out = work/'app', work/'out'
    app.mkdir()
    (app/'module.json').write_text('{}')
    (app/'main.php').write_text('return 8;')
    caller = app/'helper.php'
    caller.write_text('function helper(int &$n):int { $f=function &(int &$x):int {leaf($x);return $x;};return $f($n);}')
    leaf = app/'leaf.php'
    source = 'function leaf(int &$x):int {return 1;}'
    source_edit(leaf, source)
    process = service(app, out)
    try:
        assert 'error' not in invoke(process)
        good = artifacts(out), (out/'manifest.json').read_bytes()
        # The closure's symbol watch must reach the enclosing body on a signature edit.
        source_edit(leaf, source.replace('int &$x', 'int &$x, int $extra'))
        failure = invoke(process).get('error', '')
        assert 'helper.php:' in failure and 'Argument count mismatch' in failure, failure
        assert good == (artifacts(out), (out/'manifest.json').read_bytes())
        source_edit(leaf, source)
        assert 'error' not in invoke(process)
        good = artifacts(out), (out/'manifest.json').read_bytes()
        source_edit(leaf, source.replace('1', 'helper($x)'))
        result = invoke(process)
        diagnostic = 'Recursive reference return is unsupported: closure'
        assert diagnostic in result.get('error', ''), result
        assert good == (artifacts(out), (out/'manifest.json').read_bytes())
        source_edit(leaf, source)
        assert 'error' not in invoke(process)
        caller.unlink()
        assert 'error' not in invoke(process)
        fresh = work/'fresh'
        assert 'error' not in fresh_build(app, fresh)
        assert artifacts(out) == artifacts(fresh)
    finally:
        process.stdin.close()
        assert process.wait() == 0

    translated = work/'translated'
    translated.mkdir()
    (translated/'module.json').write_text('{}')
    example = ROOT/'compiler/examples/closure_dependencies'
    shutil.copyfile(example/'graph.php.scpp', translated/'graph.php')
    shutil.copyfile(example/'proof.php.scpp', translated/'main.php')
    generated = work/'generated'
    result = fresh_build(translated, generated)
    assert 'error' not in result, result
    measure(generated, work/'native', diagnostic.encode())


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-closure-edges-') as temp:
        verify(Path(temp))
