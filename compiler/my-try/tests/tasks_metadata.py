"""Tasks handle/control metadata proof; batch factories are test-only, not task admission."""
import argparse
import json
import shutil
import sys
from pathlib import Path

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks

p = argparse.ArgumentParser(description=__doc__)
p.add_argument('--results', type=Path, required=True)
a = p.parse_args()
out = a.results.resolve()
out.mkdir(parents=True, exist_ok=False)
metadata = out / 'metadata'
shutil.copytree(ROOT / 'runtime/metadata/modules', metadata)
module = {'version': 1, 'module': 'tasks_probe', 'dependencies': ['tasks'], 'functions': [], 'cpp': []}
for name, result, params in [('batch', 'task_batch', []), ('context', 'task_context', []), ('error', 'task_error', []), ('context_status', 'string', [('value', 'task_context')])]:
    module['functions'].append({'id': 'probe.' + name, 'name': 'probe_' + name, 'parameters': [{'name': n, 'type': t, 'passing': 'value'} for n, t in params], 'returns': result})
    module['cpp'].append({'id': 'probe.' + name, 'symbol': 'tasks_probe::' + name, 'header': str(APP / 'tests/fixtures/tasks/provider.hpp')})
(metadata / 'tasks_probe.json').write_text(json.dumps(module, indent=2) + '\n')
cases = {
    'batch': ('$b=probe_batch();$r=task_join($b);$again=task_join($b);echo $r[0],$again[0],task_done($b);task_cancel($b);$p=task_progress($b);echo $p->total(),$p->completed(),$p->queued(),$p->active(),$p->errors(),$p->stop_requested();echo task_status($b)===$p->status();', '42421110001'),
    'context': ('$c=probe_context();task_set_status($c,"ready");echo probe_context_status($c);', 'ready'),
    'error': ('$e=probe_error();echo $e->message,":",$e->kind,":",$e->key,":",$e->worker_id,":",$e->timeout,":",$e->source_file,":",$e->source_line;', 'failed:exception:3:2::probe.phs:7'),
    'controls': ('task_set_worker_pool_size(2);task_set_worker_pool_size(0);task_set_publish_try_lock(true);task_set_publish_try_lock(false);echo task_publish_lock_wait_us(),task_publish_lock_hold_us(),task_publish_callback_us(),task_publish_batch_count(),task_publish_published_count(),task_publish_max_batch_size(),task_publish_failed_try_lock_count(),task_publish_deferred_flush_count();', '00000000'),
}
with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
    def check(name, source, expected):
        folder = out / name
        (folder / 'source').mkdir(parents=True)
        (folder / 'source/main.phs').write_text(source)
        runner.run(name + '-publish', ['php', APP / 'tests/async_tasks_publish.php', folder, metadata, 'tasks_probe'])
        assert json.loads((folder / 'published/.scpp-output.manifest').read_text())['application_templates'] == 'forbidden'
        for temperature in ['cold', 'warm']:
            build = json.loads(runner.run(name + '-' + temperature, ['php', APP / 'tools/build_cpp.php', folder / 'published', folder / 'cache'], timeout=300))
            assert runner.run(name + '-' + temperature + '-run', [build['executable']]) == expected.encode()
            if temperature == 'warm':
                assert build['compiled'] == build['linked'] == 0
    tasks = [Task(name, lambda n=name, s=source, e=expected: check(n, s, e)) for name, (source, expected) in cases.items()]
    tasks.append(Task('contracts', lambda: runner.run('contracts', ['php', APP / 'tests/tasks_contracts.php', metadata, out / 'contract-source'])))
    run_tasks(tasks)
(out / 'summary.json').write_text(json.dumps({'passed': True, 'cases': list(cases)}, indent=2) + '\n')
