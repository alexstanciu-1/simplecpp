"""Executable-relative source locations and PHP byte helpers from a relocated application."""
import json
import os
from pathlib import Path
import subprocess
import tempfile

ROOT = Path(__file__).resolve().parents[2]
with tempfile.TemporaryDirectory() as temporary:
    work = Path(temporary)
    package = work / "first package"
    source = package / "src"
    source.mkdir(parents=True)
    (package / "module.json").write_text(json.dumps({"name": "relocation", "entry": "src/main.phs"}))
    (source / "main.phs").write_text('echo __FILE__, "\\n", helper();')
    nested = source / "nested"
    nested.mkdir()
    helper = nested / "other.phs"
    body = r'''function helper():string {
        if (ord("") !== 0 || ord(chr(-1)) !== 255 || ord(chr(256)) !== 0) { return "byte"; }
        if (strrev("a\0" . chr(255)) !== chr(255) . "\0a") { return "reverse"; }
        if (str_rot13("NaZz-\0" . chr(255)) !== "AnMm-\0" . chr(255)) { return "rotate"; }
        if (chop(" a \n") !== " a" || chop("xx..", ".") !== "xx") { return "trim"; }
        return __DIR__ . "|" . __file__;
    }'''
    helper.write_text(body)
    native = package / 'bin'
    bundle = work / 'cpp'
    config = work / 'toolchain.json'
    config.write_text(json.dumps({'cxx': 'clang++', 'flags': ['-std=c++23', '-stdlib=libc++',
        '-I', str(ROOT/'runtime/generated/public')], 'cache': str(ROOT/'build/clang/native-qualification')}))
    cmd = ['php', ROOT/'compiler/main.php', 'build', package, ROOT/'build/clang/native-layout/facts.json',
           bundle, native, config]
    env = dict(os.environ, XDEBUG_MODE='off')
    def compile_project():
        return subprocess.run(cmd, capture_output=True, text=True, env=env)
    def verify(root):
        actual = subprocess.check_output([work/'launcher'], cwd=work, text=True)
        assert actual == f"{root}/src/main.phs\n{root}/src/nested|{root}/src/nested/other.phs", actual
    built = compile_project()
    assert built.returncode == 0, built.stderr
    (work/'launcher').symlink_to(native/'program')
    verify(package)
    helper.write_text(body.replace('__file__', '__MISSING__'))
    assert 'Unknown constant' in compile_project().stderr
    helper.write_text(body)
    built = compile_project()
    assert built.returncode == 0, built.stderr
    verify(package)
    relocated = work / 'moved package'
    package.rename(relocated)
    (work/'launcher').unlink()
    (work/'launcher').symlink_to(relocated/'bin/program')
    verify(relocated)
    generated = ''.join(unit.read_text() for unit in bundle.rglob('*.cpp'))
    assert str(package) not in generated and '"src/nested/other.phs"' in generated
