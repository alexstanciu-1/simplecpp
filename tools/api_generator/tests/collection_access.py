"""Vary provider guarantees independently and execute the accepted programs."""
import json
from pathlib import Path
import subprocess
import sys
import tempfile

from collection_arguments import EMIT, GENERATOR, METADATA, ROOT

sys.path.insert(0, str(ROOT / 'tools/compiler_panel'))
from native_build import write_build


def prove(work):
    layout = ROOT / 'build/clang/native-layout'
    generated = work / 'compiler'
    boot = work / 'boot.php'
    bootstrap = (ROOT / 'compiler/boot.php').read_text().replace(
        "__DIR__ . '/generated/", repr(str(generated) + '/') + " . '")
    boot.write_text(bootstrap.replace('__DIR__', repr(str(ROOT / 'compiler'))))
    metadata = json.loads(METADATA.read_text())
    # This test publication exposes the string provider as a checked byte sequence.
    metadata['string']['access_contracts'] = ['readonly_slot', 'python_index_read']
    metadata['vector']['access_contracts'].remove('python_index_read')
    metadata['hash']['access_contracts'].remove('readonly_slot')
    metadata['vector']['access_contracts'].remove('php_container')
    metadata['boxed']['access_contracts'].remove('php_runtime_kind')
    fixture = work / 'metadata.json'
    fixture.write_text(json.dumps(metadata))
    subprocess.run(['php', GENERATOR, 'emit-compiler', fixture, ROOT / 'runtime/metadata/publication.json',
                    layout / 'facts.json', layout / 'context.json', generated], check=True)

    def emit(flavor, source, error=None):
        result = subprocess.run(['php', '-r', EMIT, boot, flavor, source], capture_output=True, text=True)
        if error:
            assert result.returncode and error in result.stderr, result
            return
        assert result.returncode == 0, result.stderr
        build = work / flavor
        build.mkdir()
        unit = build / 'main.cpp'
        unit.write_text(result.stdout)
        subprocess.run(write_build(build, [unit]), cwd=build, check=True)
        assert subprocess.run([build / 'program']).returncode == 8

    emit('php', '''
        function read(const string &$view, string &$writer): int {
            $writer="12345678"; return strlen($view);
        }
        function first(const vector<int> &$v):int {return $v[0];}
        $v vector<int>=[7]; if(first($v)!==7){return 1;}
        $fixed fixed_array<byte,1>=[1]; $h hash<int,int>=[];
        if(!is_array($fixed) || is_int($fixed) || !is_array($h)){return 2;}
        $s="x"; return read($s,$s);
    ''')
    emit('php', 'function f(const hash<int,int> &$h):int{return count($h);} return 8;',
         'Const references require a borrowable collection')
    emit('php', 'function f(const string &$s):int{$s="changed";return 8;} return 8;',
         'Cannot write through read-only reference')
    emit('php', '$v vector<int>=[]; return is_array($v) ? 1 : 0;', 'requires a type with a PHP kind')
    emit('php', '$d dynamic=[]; return is_array($d) ? 1 : 0;', 'requires a type with a PHP kind')
    emit('py', 'items: vector<int> = [8]\nreturn items[0]', 'Provider does not support Python indexed reads')
    emit('py', 'h: hash<int,int> = {3: 8}\nd: dynamic = [7]\ns: string = "XA"\nbyte_value: int = s[1]\nif d[0] != 7 or byte_value != 65:\n    return 1\nreturn h[3]')


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-collection-access-') as directory:
        prove(Path(directory))
