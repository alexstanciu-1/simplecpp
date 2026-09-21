#!/usr/bin/env python3
"""Historical public-helper edit comparisons, with independent object trees."""
import argparse
import json
from pathlib import Path
from callable_surface import prepare
from partitioned_callables import prepare as prepare_parts
from experiment import measure, require_scratch
from run_corpus import regen, warm, verify


def main():
    p = argparse.ArgumentParser()
    p.add_argument('--app', type=Path, required=True)
    p.add_argument('--rounds', type=int, default=3)
    p.add_argument('--combined-only', action='store_true')
    args = p.parse_args()
    app = args.app.resolve()
    require_scratch(app)
    repo = Path(__file__).resolve().parents[2]
    corpus = json.loads((app.parent / 'corpus.json').read_text())
    out = app.parent / 'callable-edit-results'
    out.mkdir(exist_ok=True)
    original_main = (app / 'main.phs').read_text()
    for cls, source, dispatcher in [
        ('compiler_profile_events', 'compile/support/compiler_profile_events.phs', 'stage_name'),
        ('token_kinds', 'compile/frontend_adapter/phs/token_kinds.phs', 'name'),
    ]:
        original = (app / source).read_text()
        for trial in range(args.rounds):
            prepare(app)
            if args.combined_only:
                prepare_parts(app)
                warm(app, 'latency-callable-parts.ninja')
            else:
                warm(app, 'latency-baseline.ninja')
                warm(app, 'latency-callables.ninja')
            name = f'latency_added_{trial}'
            value = 40 + trial
            changed = original.replace(f'final class {cls} {{', f'final class {cls} {{\n\tpublic static function {name}(): uint16 {{ return structure_row_ids::uint16_from_int({value}); }}\n', 1)
            param = '$stageId' if cls == 'compiler_profile_events' else '$kindId'
            needle = f'public static function {dispatcher}(uint16 {param}): string {{'
            assert changed.count(needle) == 1
            changed = changed.replace(needle, needle + f'\n\t\tif ({param} === {cls}::{name}()) {{ return "latency_added_{value}"; }}\n', 1)
            try:
                (app / source).write_text(changed)
                (app / 'main.phs').write_text(original_main + f'\necho "callable_probe=" . {cls}::{dispatcher}({cls}::{name}()) . "\\n";\n')
                regen(repo, app, source)
                regen(repo, app, 'main.phs')
                prepare(app)
                if args.combined_only:
                    prepare_parts(app)
                configs = ['latency-callable-parts.ninja'] if args.combined_only else ['latency-baseline.ninja', 'latency-callables.ninja']
                if trial % 2:
                    configs.reverse()
                for config in configs:
                    label = f'{cls}-r{trial}-' + config.removesuffix('.ninja')
                    measure(app, config, label, out, [])
                    verify(app, corpus, out, label)
                    if f'callable_probe=latency_added_{value}' not in (out / (label + '.run.log')).read_text().splitlines():
                        raise RuntimeError('New method/dispatch witness did not run')
            finally:
                (app / source).write_text(original)
                (app / 'main.phs').write_text(original_main)
                regen(repo, app, source)
                regen(repo, app, 'main.phs')
                prepare(app)
    if args.combined_only:
        prepare_parts(app)
    warm(app, 'latency-callable-parts.ninja' if args.combined_only else 'latency-callables.ninja')
    verify(app, corpus, out, 'restored')


if __name__ == '__main__':
    main()
