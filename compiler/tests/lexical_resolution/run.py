"""Lexical binding, template argument roles and source diagnostic proofs."""
import argparse
import hashlib
import json
import os
from pathlib import Path
import shutil
import subprocess
import time

ROOT = Path(__file__).resolve().parents[3]
FILES = ['src/04_analyze/type_model/data/generic.php', 'src/04_analyze/resolve_symbols/data/structures.php', 'src/04_analyze/resolve_symbols/data/traversal.php', 'src/04_analyze/resolve_symbols/data/result.php', 'src/04_analyze/resolve_symbols/handlers/names.php', 'src/04_analyze/resolve_symbols/handlers/declarations.php', 'src/04_analyze/resolve_symbols/handlers/statements.php', 'src/04_analyze/resolve_symbols/handlers/expressions.php', 'src/04_analyze/resolve_symbols/body.php']
DEPENDENCIES = ['src/01_prepare_inputs/read_sources/data/buffer.php', 'src/02_tokenize/structures.php', 'src/02_tokenize/store.php', 'src/02_tokenize/tokenize.php', 'src/03_parse/data/nodes.php', 'src/03_parse/data/tree.php', 'src/03_parse/utilities/binary_syntax.php', 'src/03_parse/data/expression_state.php', 'src/03_parse/handlers/expressions.php', 'src/03_parse/data/result.php', 'src/03_parse/handlers/statements.php', 'src/03_parse/handlers/control_statements.php', 'src/03_parse/handlers/declarations.php', 'src/03_parse/handlers/metaprogramming.php', 'src/03_parse/parse_file.php', 'src/03_parse/data/role_views.php', 'src/03_parse/utilities/metaprogramming_syntax.php', 'src/03_parse/utilities/struct_member_cursor.php', 'src/03_parse/utilities/syntax_access.php', 'src/03_parse/utilities/syntax_comparer.php', 'src/03_parse/data/store.php', 'src/03_parse/select_tasks.php', 'src/03_parse/join.php', 'src/03_parse/main_parse.php', 'src/04_analyze/collect_symbols/data/structures.php', 'src/04_analyze/collect_symbols/data/store.php', 'src/04_analyze/collect_symbols/data/result.php', 'src/04_analyze/collect_symbols/collect.php', 'src/04_analyze/collect_symbols/main_collect_symbols.php', 'src/04_analyze/resolve_types/data/entry_selection.php', 'src/04_analyze/resolve_types/main_prepare_entry.php', 'src/04_analyze/type_model/data/semantic_modes.php', 'src/04_analyze/type_model/data/representations.php', 'src/04_analyze/type_model/data/context.php', 'src/04_analyze/type_model/data/lifecycle_roles.php', 'src/04_analyze/type_model/data/lifecycle.php', 'src/04_analyze/type_model/data/lifetime_contract.php', 'src/04_analyze/type_model/data/lifetime_policy_codec.php', 'src/04_analyze/type_model/data/native_record_layout.php', 'src/04_analyze/type_model/data/resources.php', 'src/04_analyze/type_model/data/definitions.php', 'src/04_analyze/type_model/data/catalog.php', 'src/01_prepare_inputs/load_runtime/utilities/catalog_syntax.php', 'src/01_prepare_inputs/load_runtime/main_load_runtime.php', 'src/04_analyze/resolve_types/data/entry_contract.php', 'src/04_analyze/resolve_symbols/data/declarations.php', 'src/04_analyze/resolve_symbols/utilities/declaration_lookup.php', 'src/04_analyze/resolve_symbols/utilities/function_lookup.php']
LOAD_ORDER = DEPENDENCIES + FILES


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', type=Path, required=True)
    parser.add_argument('--target-checkout', type=Path)
    parser.add_argument('--candidate-revision')
    args = parser.parse_args()
    out = args.results.resolve(); out.mkdir(parents=True, exist_ok=False)
    source = out / 'source'; source.mkdir()
    inputs = out / 'inputs'; inputs.mkdir()
    report = {'passed': False, 'commands': [], 'native_build_attempts': 0}

    def run(label, command, cwd=ROOT):
        start = time.monotonic()
        result = subprocess.run(list(map(str, command)), cwd=cwd, capture_output=True, text=True)
        for kind, value in [('stdout', result.stdout), ('stderr', result.stderr)]:
            (out / f'{label}.{kind}.log').write_text(value)
        if label == 'native-build': report['native_build_attempts'] += 1
        report['commands'].append({'label': label, 'command': list(map(str, command)),
                                  'seconds': round(time.monotonic() - start, 3), 'exit': result.returncode})
        (out / 'summary.json').write_text(json.dumps(report, indent=2) + '\n')
        assert result.returncode == 0, (label, result.stdout, result.stderr)
        return result.stdout

    assert json.loads(run('retained-contract-oracle',['php',Path(__file__).parent/'oracle.php'])) == [False,True,True,False,True]
    shutil.copy2(ROOT/'compiler/reference/pre-rewrite/language/named_types.json',inputs/'catalog.json')
    expected=[True]*94
    calls=['\\lexical_test\\Probe::run();']
    def php_literal(value):
        if isinstance(value,int): return str(value)
        return "'"+value.replace("\\","\\\\").replace("'","\\'")+"'"
    import sys
    sys.path.insert(0,str(Path(__file__).parent))
    from cases import cases
    for index,(content,owner,kind,anchor,occurrence,reason) in enumerate(cases()):
        path=inputs/f'bad-{index}.phs';path.write_text(content)
        start=-1
        for _ in range(occurrence+1): start=content.index(anchor,start+1)
        case_args=[f'inputs/bad-{index}.phs',owner,kind,len(content[:start].encode()),len(anchor.encode()),reason]
        calls.append('\\lexical_test\\Probe::bad('+','.join(php_literal(a) for a in case_args)+');');expected.extend([True]*6)
    definitions="""template<typename Key, typename Value> struct dictionary { public Key $key; public Value $value; }
    template<typename T, int N> struct sample { public T $value; public dictionary<T, plain> $items; }
    template<typename T, T N> struct dependent_value { public T $value; }
    template<typename T> struct recursive { public recursive<T> $next; }
    template<typename T, int N> constexpr function choose($value T): T {
      const LOCAL: int = N + 1;
      if constexpr (LOCAL) { $copy T = $value; return helper<T>($copy); }
      else if consteval { return $value; } else { return $value; }
    }
    template<typename T> function read_member($value T): int { return $value->member; }
    template<typename T> function helper($value T): T { return $value; }
    struct plain { public int32 $value; }
    function identity($value int): int { return $value; }
    $item plain = new plain(); return identity($item->value) + 7;
    """
    (inputs/'templates.phs').write_text(definitions)
    calls.append("\\lexical_test\\Probe::all_good('inputs/templates.phs',10);");expected.extend([True]*11)
    deep='$root int = 42;'+''.join(f'$v{i} int = $root;' for i in range(1000))+'{'*128+'$root = $v999;'+'}'*128+'return $root;'
    (inputs/'deep.phs').write_text(deep)
    calls.append("\\lexical_test\\Probe::deep('inputs/deep.phs');");expected.extend([True]*5)
    for relative in DEPENDENCIES + FILES:
        dest=source/relative;dest.parent.mkdir(parents=True,exist_ok=True)
        shutil.copy2(ROOT/'compiler'/relative,dest)
    shutil.copy2(Path(__file__).parent/'probe.php',source/'probe.php')
    (source/'main.php').write_text('<?php\n'+'\n'.join(calls)+'\n')
    run('imports',['php',ROOT/'tools/php_portability/sync_imports.php',source])
    run('check',['php',ROOT/'tools/php_portability/check.php',source])
    php=['php','-r','foreach(array_slice($argv,1) as $p) { require $p; }',
         ROOT/'tools/php_portability/runtime/bootstrap.php',*[source/f for f in LOAD_ORDER],
         source/'probe.php',source/'main.php']
    def prove(label, command, want):
        actual=[json.loads(line) for line in run(label,command,out).splitlines()]
        assert actual==want,(label,actual,want)
    prove('php',php,expected)
    run('host-purity',php[:-1]+[Path(__file__).parent/'purity.php'],out)
    generated=out/'phpp';conversion=['php',ROOT/'tools/php_portability/convert.php',source,generated]
    assert json.loads(run('convert',conversion))['converted']==len(DEPENDENCIES+FILES)+2
    assert json.loads(run('reuse',conversion))=={'converted':0,'reused':len(DEPENDENCIES+FILES)+2,'removed':0}
    run('runtime',['php',ROOT/'tools/php_portability/install_native_runtime.php',generated,'--json','--filesystem'])
    binary=None
    if args.target_checkout:
        target=json.loads((ROOT/'compiler/tools/portability_target.json').read_text())
        revision=args.candidate_revision or target['verified_commit'];checkout=args.target_checkout.resolve()
        assert len(revision)==40 and all(c in '0123456789abcdef' for c in revision)
        assert run('revision',['git','-C',checkout,'rev-parse','HEAD']).strip()==revision
        assert run('clean',['git','-C',checkout,'status','--porcelain']).strip()==''
        cli=checkout/'bin/scpp.php';run('init',['php',cli,'init','--php-profile=strict'],generated)
        config=json.loads((generated/'prism.json').read_text());config['build']['cxx']='clang++-18'
        config['runtime']['modules']=['json','filesystem'];(generated/'prism.json').write_text(json.dumps(config,indent=2)+'\n')
        run('native-build',['php',cli,'build','--build-runtime'],generated)
        binary=[generated/'.prism/build/main'];prove('native',binary,expected)
        report['target_revision']=revision
        assert run('clean-after',['git','-C',checkout,'status','--porcelain']).strip()==''
    (out/'expected.json').write_text(json.dumps(expected,indent=2)+'\n')
    report.update(passed=True,native=bool(binary),cases=len(expected),lexical_outcomes=len(expected),
                  production_files=FILES,source_sha256={f:hashlib.sha256((source/f).read_bytes()).hexdigest() for f in DEPENDENCIES+FILES})
    (out/'summary.json').write_text(json.dumps(report,indent=2)+'\n')
    print(f'Lexical resolution: {len(expected)} outcomes passed; native={bool(binary)}')

if __name__=='__main__':main()
