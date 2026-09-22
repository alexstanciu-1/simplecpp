"""Prove the adopted update context through PHP, local conversion and native PHP++."""
import argparse
import importlib.util
import json
from pathlib import Path
import shutil
import subprocess
import tempfile
import time

ROOT = Path(__file__).resolve().parents[3]
EXPECTED = 'initial=0\nshared=1\nnext=0\nprevious=1\nrebound=0\nretained=1\ndefault=1\n0:0\ntoken-shared=1\n7:3\nindependent=1\ncreated=1\nfinished=1\n'

EXPECTED += 'a/b\na/b\n/x\n/x\na/\n0\n0\n1\n1\n0\n0\n0\n0\n0\n1\n0\n1\n0\n1\n1\n1\n0\n0\n0\n0\n1\n1\n0\n0\n0\n0\n'

EXPECTED += '0\n1\ncdef::\nbyte=1\n'

EXPECTED += '4:2:2\n'

EXPECTED += 'node-default=1\n0:0:0:0\n12:9:3\n10:2\nnode-independent=1\n'

EXPECTED += '1:2:3:4\n5:6:7\n8:9\n1:2:0\n2:3\n4:5:0\n6:7\n8:0\n9:10\n11:12:13\nreference-absent=1\nreference-present=1\n6\n'

EXPECTED += 'reference-taken=1\nreference-kind=1\n'

EXPECTED += 'cursor-default=1\n0:0:0:0\n21:20:1\noperator=1\n10:90\n30:31:0\n0:2\n'

EXPECTED += '1:/source.phs:100:4\nsnapshot-shared=1\nsnapshot-version=1\nsnapshot-bytes=1\n1\n0:0:0\n4:4\n4:9\n1:2\n'

EXPECTED += 'snapshot-distinct=1\n'

EXPECTED += '0\n1\n0\n0\n1\n1\n0\n0\n1\n0\n0\n1\n0\n0\n1\n0\n0\n1\n0\n0\n1\n0\n0\n1\n0\n0\n1\n0\n0\n1\n0\n0\n1\n0\n0\n0\n195:169:-1:-1\n'

EXPECTED += 'Backend configuration requires explicit backend, target, layout, ABI and runtime identities\nbackend:runtime\nouter:9\ncause-shared=1\ncause:7\nrethrow-shared=1\n'

EXPECTED += 'handler escape\nrange\n0:no-cause\n'

EXPECTED += 'tool-ir=1\ntool-path=1\ntool-alias=1\ntool-distinct=1\ntool-config=1\ntool-config-version=1\n0:0\n/bin/link tool:link-v1:link-v2\n'

EXPECTED += 'sub/é file.phs:100:7\n42:/root/sub/é file.phs:100:7\nread-alias=1\nread-distinct=1\n42:101:9:100:7\n0:0:0\n'

EXPECTED += 'folder-recursive=1\nempty-present=1\n0:recursive\nselected-present=1\n3:4:/root:sub:1:é.phs:2\nfolder-selected=1\n1:2\nfolder-reset=1\nbuffer-absent=1\nstate-unchanged=1\npending=1\nbuffer-present=1\nbuffer-identity=1\nstate-deleted=1\n-1:pending\n'

EXPECTED += '37:12:0:0:0\nentry-shared\ntombstone-retained\nowner-new:changed-new:clean-shared\nbuffer-present:buffer-shared\nold-pending:accepted-clear:0:100\nold-unchanged\nrepeat-shared\nUnknown source file ID\nInvalid source folder index\nEntry file is removed\nDuplicate source path: /src/a.phs\n37\nInvalid or duplicate source file ID\n"\\u00e9\\ud83d\\ude00\\/\\"\\\\\\n"\nCannot export sources: Malformed UTF-8 characters, possibly incorrectly encoded\n'
EXPECTED = EXPECTED.replace("Cannot export sources:", '{"folders":[{"path":"src","resolved_path":"\\/src"},{"path":"extra","resolved_path":"","file_names":[]}],"files":[{"id":37,"top_folder_index":0,"path":"\\/src\\/a.phs","relative_path":"a.phs","mtime":0,"size":0,"change_state":"unchanged","needs_recompile":false},{"id":12,"top_folder_index":0,"path":"\\/src\\/b.phs","relative_path":"","mtime":0,"size":0,"change_state":"unchanged","needs_recompile":false},{"id":99,"top_folder_index":-1,"path":"\\/src\\/gone.phs","relative_path":"","mtime":0,"size":0,"change_state":"deleted","needs_recompile":false}],"removed_file_ids":[],"entry_file_id":37}\n{"folders":[],"files":[],"removed_file_ids":[],"entry_file_id":0}\n' + "Cannot export sources:")


EXPECTED += 'scan-task-shared\n1:sub/child\nscan-row-shared\nsub/é.phs:101:4\n2:1\n0:0\n'

EXPECTED += '5:old.phs\n6:new.phs\n7:sub/child.phs\n8:1:0:next\nscan-copy:cleared:old-retained\nchanged:added\nUnexpected or duplicate source scan result: 0\nIncomplete source scan batch\n3\n'

EXPECTED += 'Unexpected or duplicate source scan result: 0\nunchanged-row-copy:buffer-retained:clean\nSource file ID space exhausted\n0\n'

EXPECTED += 'snapshot-owner:changed-copy:retained-shared\nbuffer-exact:old-clean:deleted-clear:old-retained\n11:12:13:40\n1\nIncomplete source read batch\nUnexpected, duplicate or stale source snapshot\nUnexpected, duplicate or stale source snapshot\nDuplicate source read task\nStale source read task\nStale retained source snapshot\n'

EXPECTED += 'repeat-shared:deleted-shared\n11:3:0\n'

EXPECTED += '1:11:/root/change:2:2\n2:11:12\n0:pending-retained:input-unmodified\n2:2\n0\nUnsupported source change: moved\nUnsupported source change: moved\n'

EXPECTED += 'token-shared:token-absent\n[{"source_file_id":71,"path":"\\/tokens","tokens":[{"kind":"variable_name","start":3,"length":1,"text":"x"}]}]\n[]\nInvalid or duplicate token buffer\n'

EXPECTED += 'Invalid or duplicate token buffer\n{"source_file_id":71,"path":"\\/tokens","tokens":[{"kind":"variable_name","start":0,"length":2,"text":"\\u00e9"}]}\nMalformed UTF-8 characters, possibly incorrectly encoded\nsource-shared:baseline-independent\n'

EXPECTED += 'join-a:join-b:deleted-absent\n[{"source_file_id":11,"path":"\\/root\\/change","tokens":[]},{"source_file_id":12,"path":"\\/root\\/keep","tokens":[]}]\nnew-token-set:retained-token:previous-unmodified\nIncomplete tokenization batch\nUnexpected, duplicate or stale tokenization result\nUnexpected, duplicate or stale tokenization result\nUnexpected, duplicate or stale tokenization result\nDuplicate or stale tokenization task\nMissing or stale retained token buffer\n'

EXPECTED += '0:2:2\nselected-a:selected-b\n1:new-identity:old-retained\nMissing source snapshot for tokenization\nMissing source snapshot for tokenization\n0\n'

EXPECTED += 'frontend-token-shared:frontend-tree-shared\n{"source_file_id":91,"path":"\\/parse","root_node_id":2,"defined_entities":[3],"entry_body_id":1,"nodes":[{"id":1,"kind":"block","start":0,"length":0,"first_child_id":0,"next_sibling_id":3},{"id":2,"kind":"file_root","start":0,"length":0,"first_child_id":1,"next_sibling_id":0},{"id":3,"kind":"name","start":2,"length":2,"first_child_id":0,"next_sibling_id":0,"text":"\\u00e9"}]}\nInvalid file frontend\nInvalid file frontend\nInvalid file frontend\nInvalid file definition index\nIncomplete file definition index\nfrontend-recovered\n'

EXPECTED += 'Invalid frontend result segment\nIncomplete frontend task batch\nDuplicate, removed or stale frontend result\nfrontend-one:frontend-two:previous-empty\nrepeat-owner:repeat-shared\nretained-frontend\nUnexpected unselected frontend result\nInvalid or duplicate file frontend\n[]\n'

EXPECTED += '2:3\n0:5\n6:10\n15:16\n14:17\n23:24\n27:31\naddition-kind:not-operator\nless-than-name:no-operation\n2:1\nNot a binary operator\n'

EXPECTED += '0:2:2\nparse-first:parse-second\n1:parse-replaced:previous-retained\nMissing or stale tokens for parsing\nMissing or stale tokens for parsing\n1:live-only\n0\n'

EXPECTED += 'logical-tree-equal:spelling-equal\nboth-absent:one-absent\nlength-differs\nkind-differs\nchild-membership-differs\nInvalid previous comparison node\nInvalid current comparison node\nUnsupported syntax kind for comparison: invalid\n2:3:99\n'

EXPECTED += 'paired-stack-branches\n'

EXPECTED += 'octal-bytes=512\nhex-bytes=256\n10:13:9:11:12:27:36:\n92:110:92:114:92:116:92:118:92:102:92:101:92:36:\n15:122:92:120:71:71:0:55:255:56:\n195:169:240:159:152:128:\n36:49:32:36:45:\n92:113:92:120:\n39:\n34:\nMalformed quoted literal\nMalformed quoted literal\nMalformed quoted literal\nString interpolation is not supported\nString interpolation is not supported\nString interpolation is not supported\nUnicode escape syntax is not supported; use UTF-8 literal bytes\nByte value must be between 0 and 255\nByte value must be between 0 and 255\n'

EXPECTED += 'example:é_name\nprovider-v1:native-id\nowner:3\nfamily-id:4:outer:2:0\ntype-references:identity-order-independent-membership\n'

EXPECTED += 'project-key:/a/é:/out/...\n/:/\n255\nNative project roots must be absolute\nNative project roots must be absolute\nNative project roots must not contain parent traversal\nNative project roots must not contain parent traversal\nNative project roots must be absolute\nNative project requires an explicit project key\nNative project requires an explicit project key\nNative project roots must not contain parent traversal\n'

EXPECTED += '{"path":"","content":"","directory":"","source_folder_paths":[],"entry_path":""}\n{"path":"\\u00e9\\/project.json","content":"{\\n\\t\\"entry\\":\\"a.phs\\"\\n}","directory":"\\/src","source_folder_paths":["src","\\u00e9\\ud83d\\ude00"],"entry_path":"a.phs"}\n{"path":"\\u00e9\\/project.json","content":null,"directory":"\\/src","source_folder_paths":["src","\\u00e9\\ud83d\\ude00"],"source_file_paths":["a.phs"],"entry_path":"a.phs"}\nCannot export project manifest: Malformed UTF-8 characters, possibly incorrectly encoded:0\nMalformed UTF-8 characters, possibly incorrectly encoded:5\n"\\u0000\\u0001\\u0002\\u0003\\u0004\\u0005\\u0006\\u0007\\b\\t\\n\\u000b\\f\\r\\u000e\\u000f\\u0010\\u0011\\u0012\\u0013\\u0014\\u0015\\u0016\\u0017\\u0018\\u0019\\u001a\\u001b\\u001c\\u001d\\u001e\\u001f"\n'

EXPECTED += 'nullable-defaults:0:false:empty\n'

EXPECTED += 'good/a.phs:1700000000:3\ngood/z.phs:1700000000:0\ngood/é.phs:1700000000:2\ndirectory:good/sub\nselected:good/z.phs:0\nselected:good/note.txt:7\nselected:good/a.phs:3\nempty-selection:0:0\nscan-missing\nscan-symlink\nscan-symlink\nmissing-size-retained:77\n'

EXPECTED += 'rp_type_X_simple__cpp_X_size_X_move__constructible\nrp__X___X___X___x2E___X__xC3__xA9_\nrp_type_X_simple__cpp_X_size_X_move__constructible\nrp__x00__x01__x02__x03__x04__x05__x06__x07__x08__x09__x0A__x0B__x0C__x0D__x0E__x0F__x10__x11__x12__x13__x14__x15__x16__x17__x18__x19__x1A__x1B__x1C__x1D__x1E__x1F__x20__x21__x22__x23__x24__x25__x26__x27__x28__x29__x2A__x2B__x2C__x2D__x2E__x2F_0123456789_x3A__x3B__x3C__x3D__x3E__x3F__x40_ABCDEFGHIJKLMNOPQRSTUVWXYZ_x5B__x5C__x5D__x5E____x60_abcdefghijklmnopqrstuvwxyz_x7B__x7C__x7D__x7E__x7F__x80__x81__x82__x83__x84__x85__x86__x87__x88__x89__x8A__x8B__x8C__x8D__x8E__x8F__x90__x91__x92__x93__x94__x95__x96__x97__x98__x99__x9A__x9B__x9C__x9D__x9E__x9F__xA0__xA1__xA2__xA3__xA4__xA5__xA6__xA7__xA8__xA9__xAA__xAB__xAC__xAD__xAE__xAF__xB0__xB1__xB2__xB3__xB4__xB5__xB6__xB7__xB8__xB9__xBA__xBB__xBC__xBD__xBE__xBF__xC0__xC1__xC2__xC3__xC4__xC5__xC6__xC7__xC8__xC9__xCA__xCB__xCC__xCD__xCE__xCF__xD0__xD1__xD2__xD3__xD4__xD5__xD6__xD7__xD8__xD9__xDA__xDB__xDC__xDD__xDE__xDF__xE0__xE1__xE2__xE3__xE4__xE5__xE6__xE7__xE8__xE9__xEA__xEB__xEC__xED__xEE__xEF__xF0__xF1__xF2__xF3__xF4__xF5__xF6__xF7__xF8__xF9__xFA__xFB__xFC__xFD__xFE__xFF_\nSymbol identity requires an ordered component list\n'

def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--target-checkout', type=Path, required=True)
    parser.add_argument('--results', type=Path, required=True)
    parser.add_argument('--candidate-revision', help='Explicit full candidate commit for pre-adoption proof; does not change the target pin')
    args = parser.parse_args()
    results = args.results.resolve()
    if results.exists():
        raise SystemExit('Use a fresh evidence directory.')
    target = json.loads((ROOT / 'compiler/tools/portability_target.json').read_text())
    if args.candidate_revision:
        if len(args.candidate_revision) != 40 or any(c not in '0123456789abcdef' for c in args.candidate_revision):
            parser.error('Candidate revision must be a full lowercase commit hash')
        target['verified_commit'] = args.candidate_revision
    checkout = args.target_checkout.resolve()
    def git(*parts):
        return subprocess.check_output(['git', *parts], cwd=checkout, text=True).strip()
    assert git('rev-parse', 'HEAD') == target['verified_commit']
    assert git('status', '--porcelain') == ''
    results.mkdir(parents=True)
    work = Path(tempfile.mkdtemp(prefix='scpp-context-port-'))
    source, output = work / 'php', work / 'phpp'
    source.mkdir()
    report = {'workspace': str(work), 'target_revision': target['verified_commit'], 'commands': []}

    def run(label, cmd, cwd=ROOT, ok=True):
        started = time.monotonic()
        proc = subprocess.run(cmd, cwd=cwd, text=True, capture_output=True)
        (results / (label + '.stdout.log')).write_text(proc.stdout)
        (results / (label + '.stderr.log')).write_text(proc.stderr)
        report['commands'].append({'label': label, 'command': [str(x) for x in cmd], 'cwd': str(cwd), 'exit_code': proc.returncode, 'seconds': round(time.monotonic() - started, 3)})
        (results / 'summary.json').write_text(json.dumps(report, indent=2) + '\n')
        assert (proc.returncode == 0) == ok, (label, proc.stdout, proc.stderr)
        return proc

    # The component stays owned in compiler/; staging is a disposable source set,
    # not a parallel maintained implementation or an expansion of directory CLI scope.
    selection = json.loads((ROOT / 'compiler/portability.json').read_text())
    assert selection['schema_version'] == 1 and selection['source_root'] == '.'
    for relative in selection['files']:
        p = Path(relative)
        assert not p.is_absolute() and '..' not in p.parts and p.suffix == '.php'
        dst = source / p
        dst.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(ROOT / 'compiler' / p, dst)
    shutil.copy2(Path(__file__).parent / 'main.php', source / 'main.php')
    fixture_spec = importlib.util.spec_from_file_location('filesystem_fixture', ROOT / 'tests/portability/filesystem_fixture.py')
    fixture_module = importlib.util.module_from_spec(fixture_spec)
    fixture_spec.loader.exec_module(fixture_module)
    fixture_module.prepare_scan_fixture(source / 'main.php', work / 'filesystem')
    tools = ROOT / 'tools/php_portability'
    run('imports', ['php', str(tools / 'sync_imports.php'), str(source), '--check'])
    php = run('php', ['php', '-r', 'foreach (array_slice($argv, 1) as $file) { require $file; }',
        str(tools / 'runtime/bootstrap.php'),
        *[str(ROOT / 'compiler' / relative) for relative in selection['files']], str(source / 'main.php')])
    assert php.stdout == EXPECTED
    readonly = run('php-readonly', ['php', '-r',
        'require $argv[1]; $view = new parse\\function_parts(1,2,3,4); '
        'try { $view->name_id=9; } catch (Error $error) { echo "PHP readonly enforced"; exit(0); } exit(1);',
        str(ROOT / 'compiler/bootstrap.php')])
    assert readonly.stdout == 'PHP readonly enforced'
    contract = run('step-contract', ['php', '-r',
        'require $argv[1]; $out = []; foreach (["Step_Result", "Step_Store", "Step", "Runnable_Step", "Store_Providing_Step"] as $name) { '
        '$r = new ReflectionClass("compile" . chr(92) . $name); $methods = []; foreach ($r->getMethods() as $m) { '
        '$methods[$m->getName()] = [(string)$m->getReturnType(), $m->getNumberOfParameters(), $m->isPublic()]; } '
        '$out[$name] = [$r->isInterface(), $methods]; } echo json_encode($out);',
        str(ROOT / 'compiler/src/compile/step.php')])
    actual = json.loads(contract.stdout)
    expected_methods = {
        'Step_Result': [], 'Step_Store': [],
        'Step': {'init': ['void', 0, True], 'finalize': ['void', 0, True],
                 'result': ['compile\\Step_Result', 0, True], 'status': ['compile\\step_status', 0, True],
                 'supports_run': ['bool', 0, True]},
        'Runnable_Step': {'run': ['void', 0, True]},
        'Store_Providing_Step': {'store': ['compile\\Step_Store', 0, True]},
    }
    assert actual == {name: [True, methods] for name, methods in expected_methods.items()}, actual
    convert = ['php', str(tools / 'convert.php'), str(source), str(output)]
    assert json.loads(run('convert', convert).stdout)['converted'] == len(selection['files']) + 1
    generated = output / 'src/compile/state.phs'
    stamp = generated.stat().st_mtime_ns
    assert json.loads(run('reuse', convert).stdout) == {'converted': 0, 'reused': len(selection['files']) + 1, 'removed': 0}
    assert generated.stat().st_mtime_ns == stamp
    assert 'namespace compile;' in generated.read_text() and 'declare(' not in generated.read_text()
    before = (output / '.scpp-portability.json').read_bytes()
    rejects = [
        'class Bad { public function __construct(public readonly array $items) {} }',
        'class Bad { public function __construct($n) {} }',
        'class Bad { public array $values = []; }',
        'class Bad { public array $values /** vector<mixed> */ = []; }',
        'class Bad { public array $values /** vector<int> */ = [1]; }',
        r'echo "\xA9";',
        r'echo "\251";',
        r'echo "a\0b";',
        r'echo "\u{e9}";',
        r'class Bad { public string $bytes = "\xA9"; }',
        'class Bad { public static function f($x): bool { return true; } }',
        'class Bad { public static function f(string $x = "x"): bool { return true; } }',
        'class Bad { public static function f(string &$x): bool { return true; } }',
        'class Bad { public static function f(): mixed { return true; } }',
        '$x = str_starts_with("x");',
        'interface Bad { public function f($arg): bool; }',
        'interface Bad { public function f(): bool {} }',
        'interface Bad extends Other {}',
        'interface Bad { public function f(): mixed; }',
        'enum Bad { case a = 1; }',
        'enum Bad: int { case a; }',
        'enum Bad: string { case a = "a"; }',
        'enum Bad: int { case a = 1 + 2; }',
        'enum Bad: int { case a = -1; }',
        'enum Bad: int { public function f(): bool { return true; } }',
        '$a = Kind::$dynamic;',
        'class Bad { public Kind $kind = Kind::method(); }',
        'class Bad { public function method(): float { return 1.0; } }',
        'class Bad { public static bool $value = false; }',
        'class Bad { private bool $value = 1; }',
        'class Bad { public ?bool $value = 1; }',
        'class Bad { public bool $value; }',
        'class Bad extends Other {}',
        '$name = "x"; $object->$name = true;',
        '$object->$method();',
        'try {} catch (\\Exception|\\Error $error) {}',
        '$object = new $name();',
    ]
    block = (source / 'main.php').read_text().split('// </scpp-imports>')[0] + '// </scpp-imports>\n'
    for i, body in enumerate(rejects):
        (source / 'bad.php').write_text(block + body + '\n')
        failure = run('reject-' + str(i), convert, ok=False)
        assert 'bad.php:' in failure.stderr
        assert (output / '.scpp-portability.json').read_bytes() == before
    (source / 'bad.php').unlink()
    # Existing compiler proof exercises real request decisions after the import edit.
    spec = importlib.util.spec_from_file_location('compiler_tests', ROOT / 'compiler/tests/run.py')
    compiler_tests = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(compiler_tests)
    for name in ['compile/join_contracts.php', 'compile/compile_driver.php', 'compile/incremental_policy.php', '02_tokenize/tokenization.php', '02_tokenize/variable_tokens.php', '02_tokenize/lexical_updates.php', '01_prepare_inputs/read_sources/source_paths.php', '01_prepare_inputs/read_sources/source_discovery.php', '01_prepare_inputs/read_sources/source_scan_tasks.php', '01_prepare_inputs/read_sources/source_snapshots.php', '03_parse/parsing.php', '03_parse/parameter_parsing.php', '03_parse/struct_parsing.php', '03_parse/frontend_storage.php', '03_parse/parse_updates.php', '03_parse/metaprogramming_parsing.php', 'features/integer_conversions.php', '04_analyze/type_model/semantic_calls.php', '01_prepare_inputs/read_manifest/manifest_export.php', '01_prepare_inputs/read_manifest/project_manifest.php', '01_prepare_inputs/read_manifest/manifest_recovery.php']:
        result = compiler_tests.run_fixture(name, 180)
        label = Path(name).stem
        (results / (label + '.stdout.log')).write_text(result.stdout)
        (results / (label + '.stderr.log')).write_text(result.stderr)
        assert result.returncode == 0, (name, result.stderr)
    report['compiler_fixtures'] = ['compile/join_contracts.php', 'compile/compile_driver.php', 'compile/incremental_policy.php', '02_tokenize/tokenization.php', '02_tokenize/variable_tokens.php', '02_tokenize/lexical_updates.php', '01_prepare_inputs/read_sources/source_paths.php', '01_prepare_inputs/read_sources/source_discovery.php', '01_prepare_inputs/read_sources/source_scan_tasks.php', '01_prepare_inputs/read_sources/source_snapshots.php', '03_parse/parsing.php', '03_parse/parameter_parsing.php', '03_parse/struct_parsing.php', '03_parse/frontend_storage.php', '03_parse/parse_updates.php', '03_parse/metaprogramming_parsing.php', 'features/integer_conversions.php', '04_analyze/type_model/semantic_calls.php', '01_prepare_inputs/read_manifest/manifest_export.php', '01_prepare_inputs/read_manifest/project_manifest.php', '01_prepare_inputs/read_manifest/manifest_recovery.php']
    cli = str(checkout / target['cli'])
    run('init', ['php', cli, 'init', '--php-profile=strict'], cwd=output)
    config = json.loads((output / 'prism.json').read_text())
    config['build']['cxx'] = 'clang++-18'
    config['runtime']['modules'] = ['filesystem']
    (output / 'prism.json').write_text(json.dumps(config, indent=2) + '\n')
    # init creates main.phs only if absent; verify converter-owned entry survives.
    assert (output / 'main.phs').read_text().find('new \\compile\\Update_Context()') >= 0
    install = ['php', str(tools / 'install_native_runtime.php'), str(output), '--filesystem']
    assert json.loads(run('native-runtime-install', install).stdout) == {'updated': 1}
    runtime_stamp = (output / 'scpp_framework/exceptions.phs').stat().st_mtime_ns
    assert json.loads(run('native-runtime-reuse', install).stdout) == {'updated': 0}
    assert (output / 'scpp_framework/exceptions.phs').stat().st_mtime_ns == runtime_stamp
    native = run('native', ['php', cli, 'run', '--build-runtime'], cwd=output)
    assert native.stdout.endswith(EXPECTED), native.stdout
    report.update(passed=True, expected_stdout=EXPECTED, target_clean=git('status', '--porcelain') == '')
    assert report['target_clean']
    (results / 'summary.json').write_text(json.dumps(report, indent=2) + '\n')
    shutil.copy2(generated, results / 'state.phs')
    for relative in selection['files']:
        generated_path = Path(relative).with_suffix('.phs')
        saved = results / 'generated' / generated_path
        saved.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(output / generated_path, saved)
    (results / 'scpp_framework').mkdir(exist_ok=True)
    for artifact in (output / 'scpp_framework').glob('*.phs'):
        shutil.copy2(artifact, results / 'scpp_framework' / artifact.name)
    shutil.copy2(output / '.scpp-native-runtime.json', results / 'native-runtime-manifest.json')
    shutil.copy2(__file__, results / 'runner.py')
    shutil.copy2(source / 'main.php', results / 'main.php')
    print('Adopted compiler components: PHP/native identity and independent-update behavior passed; compiler fixtures and rejection checks passed.')


if __name__ == '__main__':
    main()
