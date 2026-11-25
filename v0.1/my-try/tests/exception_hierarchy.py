"""Exception hierarchy and transport proof in ordinary and prelowered output."""
import argparse
import json
import sys
from pathlib import Path
APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks
from cpp_fixture import CppFixtureCompiler
p = argparse.ArgumentParser(description=__doc__)
p.add_argument('--results', type=Path, required=True)
a = p.parse_args()
out = a.results.resolve()
out.mkdir(parents=True, exist_ok=False)
source = r'''use Exception as RootFailure;
class Failure extends RootFailure {
 public int $code=7;
 public string $detail="failure";
 public function getMessage(): string { return $this->detail; }
 public function rootMessage(): string { return $this->get_message(); }
}
class Child extends Failure {}
class Other extends Exception {}
function base(Child $e): Exception { return $e; }
function relay(Exception $e): Exception { try { throw $e; } catch (Exception $caught) { return $caught; } }
$e = new Child();
try { try { throw relay(base($e)); } catch (Other $other) { echo "bad"; } }
catch (Failure $caught) { $caught->code=19; echo $caught->getMessage(), ":"; }
echo $e->code, ":", $e->rootMessage(), ":";
try { try { throw $e; } catch (Failure $caught) { $caught->code=23; throw $caught; } }
catch (Child $caught) { echo $caught->code, ":"; }
function scalar(): string { try { try { return "saved"; } finally { echo "inner:"; } } finally { echo "outer:"; } }
echo scalar(), ":";
try { try { throw $e; } finally { echo "finally:"; } } catch (Child $caught) { echo $caught->code, ":"; }
try { try { throw $e; } finally { throw new Other(); } } catch (Other $caught) { echo "replaced:"; }
try { throw Exception::make("native"); } catch (Exception $caught) { echo $caught->get_message(); }
class Log { public static int $count=0; }
class Mortal extends Exception { public function __destruct() { Log::$count++; } }
function lifetime(): void {
 $keep Exception;
 try { throw new Mortal(); } catch (Exception $caught) { $keep=$caught; }
 echo ":", Log::$count;
 $keep=null;
 echo ":", Log::$count;
}
lifetime();
function once(int &$count): Child { $count++; return new Child(); }
$count=0;
try { throw once($count); } catch (Failure $caught) { echo ":", $count; }
$bytes="a\0b";
$binary=Exception::make($bytes);
echo ":", $binary->get_message() === $bytes;
'''
expected = 'failure:19:Exception:23:inner:outer:saved:finally:23:replaced:native:0:1:1:1'
cases = {
    'hierarchy': (source, 0, expected, ''),
    'absent': ('class Failure extends Exception {} try { $e Failure; throw $e; } catch (Exception $e) { echo "bad"; }',
               1, '', 'Cannot throw an absent Exception'),
    'foreign': ('try { try { $v vector<int> = []; echo $v[0]; } catch (Exception $e) { echo "bad"; } } finally { echo "finally"; }',
                1, 'finally', ''),
}
with CommandRunner(out / 'logs', ROOT) as runner:
    def runtime_contract():
        compiler = CppFixtureCompiler(runner, ROOT, out, pch=False, return_type_errors=True)
        executable = out / 'runtime-contract'
        compiler.compile('runtime-contract-build', APP / 'tests/fixtures/exception_runtime.cpp', executable)
        runner.run('runtime-contract-run', [executable])
    def check(case, mode):
        name = case + '-' + mode
        program, code, expected_output, diagnostic = cases[case]
        folder = out / name
        (folder / 'source').mkdir(parents=True)
        (folder / 'source/main.phs').write_text(program)
        runner.run(name + '-publish', ['php8.5', APP / 'tests/async_tasks_publish.php', folder, ROOT / 'runtime/metadata/modules', 'compiler', mode])
        manifest = json.loads((folder / 'published/.scpp-output.manifest').read_text())
        assert manifest['application_templates'] == ('allowed' if mode == 'ordinary' else 'forbidden')
        build = json.loads(runner.run(name + '-build', ['php8.5', APP / 'tools/build_cpp.php', folder / 'published', folder / 'cache'], timeout=300))
        actual = runner.run(name + '-execute', [build['executable']], expected=code).decode()
        assert actual == expected_output, (actual, expected_output)
        assert diagnostic in (out / 'logs' / (name + '-execute.stderr')).read_text()
    run_tasks([Task(case + '-' + mode, lambda case=case, mode=mode: check(case, mode))
               for case in cases for mode in ['ordinary', 'concrete']] +
              [Task('runtime-contract', runtime_contract), Task('contracts', lambda: runner.run('contracts',
                ['php8.5', APP / 'tests/exception_hierarchy_contracts.php', out / 'contracts']))])
(out / 'summary.json').write_text(json.dumps({'passed': True, 'expected': expected}, indent=2) + '\n')
