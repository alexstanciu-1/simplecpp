"""Compare an incremental native call slot with the published PHP binding and production compiler."""
import json
from pathlib import Path
import shutil
import subprocess
import tempfile

from translated_preparation_recovery import TRANSLATION, ROOT, artifacts, fresh_build, function_text, invoke, measure, service, source_edit


def check(work):
    app, out = work / 'app', work / 'out'
    app.mkdir()
    (app / 'module.json').write_text('{}')
    (app / 'main.php').write_text('return probe();')
    probe = 'function probe():int {return strlen("abc");}'
    (app / 'probe.php').write_text(probe)
    (app / 'quiet.php').write_text('function quiet():int {return 5;}')
    offset = probe.index('strlen')
    shadow = app / 'strlen.php'
    process = service(app, out)
    try:
        assert 'error' not in invoke(process)
        builtin = function_text(out, 'probe')
        source_edit(shadow, 'function strlen(string $s):int {return 9;}')
        edit = invoke(process)
        assert 'error' not in edit and all(Path(path).stem != 'quiet' for path in edit['emitted_sources']), edit
        unit = next((out / path).read_bytes() for src, path in json.loads((out/'manifest.json').read_text())['sources'].items()
                    if Path(src).stem == 'strlen')
        start = unit.index(b'::scpp::int64 strlen_(')
        definition = unit[start:unit.index(b'\n}\n', start) + 3]
        # A unit may include a forward declaration before its definition.
        definition = definition[definition.rindex(b'::scpp::int64 strlen_('):]
        source = definition + function_text(out, 'probe')
        before = artifacts(out), (out/'manifest.json').read_bytes()
        source_edit(shadow, 'function strlen(string $s, int $extra):int {return 9;}')
        error = invoke(process).get('error', '')
        assert f'probe.php:1:{offset+1}: Argument count mismatch' in error, error
        assert before == (artifacts(out), (out/'manifest.json').read_bytes())
        shadow.unlink()
        assert 'error' not in invoke(process)
        restored = function_text(out, 'probe')
        fresh = work/'fresh'
        assert 'error' not in fresh_build(app, fresh)
        assert restored == builtin and artifacts(fresh) == artifacts(out)
    finally:
        process.stdin.close()
        assert process.wait() == 0

    php = r'''require $argv[1]; $registry = new \scpp\compiler\type_availability();
$d = $registry->runtime_function("func:strlen", \scpp\compiler\flavor::php);
echo json_encode([$d->name(), $d->required_parameters, $d->parameters[0]->name, $d->return_type_def()->name, $d->symbol, $d->header]);'''
    name, count, param, result, symbol, header = json.loads(subprocess.check_output(['php', '-r', php, ROOT/'compiler/boot.php']))
    assert (name, count, param, result) == ('strlen', 1, 'string', 'int64'), (name, count, param, result)
    translated = work/'translated'
    translated.mkdir()
    (translated/'module.json').write_text('{}')
    shutil.copyfile(TRANSLATION, translated/'preparation.php')
    shutil.copyfile(TRANSLATION.with_name('runtime_descriptor.php.scpp'), translated/'proof.php')
    (translated/'main.php').write_text(f'''
$contract = new Return_Syntax("publication", "{name}", "", "", 0);
$contract->required_parameters = {count};
$contract->parameter_type = "{param}";
$descriptor = new Runtime_Descriptor($contract, {json.dumps(symbol)});
check_descriptor($descriptor, 'scpp::string{{"abc", 3}}', "[[maybe_unused]] ::scpp::string var_s_0", {offset});
return 8;
''')
    generated = work/'generated'
    report = fresh_build(translated, generated)
    assert 'error' not in report, report
    measure(generated, work/'native', builtin + source + restored)
    translated_run = subprocess.run([work/'native/program'], capture_output=True)
    assert translated_run.returncode == 8 and not translated_run.stderr
    bodies = translated_run.stdout
    assert bodies == builtin + source + restored
    first, last = len(builtin), len(builtin) + len(source)
    cpp = work/'probe.cpp'
    cpp.write_bytes(f'#include <{header}>\n'.encode() +
                    b'namespace initial {\n' + bodies[:first] + b'}\nnamespace shadow {\n' + bodies[first:last] +
                    b'}\nnamespace recovered {\n' + bodies[last:] +
                    b'}\nint main(){return initial::probe_()==3 && shadow::probe_()==9 && recovered::probe_()==3 ? 0 : 1;}\n')
    exe = work/'probe'
    subprocess.run(['clang++', '-std=c++23', '-stdlib=libc++', '-I', ROOT/'runtime/generated/public', cpp,
                    ROOT/'build/clang/libscpp_runtime.a', '-o', exe], check=True)
    subprocess.run([exe], check=True)


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-runtime-descriptor-') as tmp:
        check(Path(tmp))
