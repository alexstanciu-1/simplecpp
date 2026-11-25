#!/usr/bin/env python3
"""Bounded complete-type isolation for CompilerProfileEventRow.

Keep vector<Row> storage and actual row layout; outline the enclosing report's
special members. No pimpl, padding guesses, stale type definitions or runtime edits.
"""
import argparse
import re
from pathlib import Path
from experiment import require_scratch, write_changed

ROW = 'structures/artifacts/compiler_profile_event_row.hpp'
REPORT = 'structures/pipeline/compiler_project_run_report'


def prepare(app):
    require_scratch(app)
    build = app / '.prism/build'
    source = app / '.prism/callables/generated'
    target = app / '.prism/layout/generated'
    report_header = (source / (REPORT + '.hpp')).read_text()
    fields = re.findall(r'^\t[^\n;]+? (\w+) = [^\n]+;$', report_header, re.M)
    assert fields and 'profile_events' in fields
    assert report_header.count('vector_t<CompilerProfileEventRow> profile_events = vector_t<CompilerProfileEventRow>{};') == 1
    report_header = report_header.replace('vector_t<CompilerProfileEventRow> profile_events = vector_t<CompilerProfileEventRow>{};', 'vector_t<CompilerProfileEventRow> profile_events;')
    declarations = '''
    CompilerProjectRunReport();
    ~CompilerProjectRunReport();
    CompilerProjectRunReport(const CompilerProjectRunReport&);
    CompilerProjectRunReport& operator=(const CompilerProjectRunReport&);
    CompilerProjectRunReport(CompilerProjectRunReport&&) noexcept;
    CompilerProjectRunReport& operator=(CompilerProjectRunReport&&) noexcept;
'''
    report_header = report_header.replace('class CompilerProjectRunReport {\npublic:\n', 'class CompilerProjectRunReport {\npublic:\n' + declarations, 1)
    special_members = '''
namespace scpp {
CompilerProjectRunReport::CompilerProjectRunReport() = default;
CompilerProjectRunReport::~CompilerProjectRunReport() = default;
CompilerProjectRunReport::CompilerProjectRunReport(const CompilerProjectRunReport&) = default;
CompilerProjectRunReport& CompilerProjectRunReport::operator=(const CompilerProjectRunReport&) = default;
CompilerProjectRunReport::CompilerProjectRunReport(CompilerProjectRunReport&&) noexcept = default;
CompilerProjectRunReport& CompilerProjectRunReport::operator=(CompilerProjectRunReport&&) noexcept = default;
'''
    # Validate noexcept against every actual member type, not merely the new
    # explicitly-noexcept Report declarations themselves.
    for field in fields:
        special_members += f'static_assert(std::is_nothrow_move_constructible_v<decltype(CompilerProjectRunReport::{field})>);\n'
        special_members += f'static_assert(std::is_nothrow_move_assignable_v<decltype(CompilerProjectRunReport::{field})>);\n'
    special_members += '}\n'
    complete = []
    for path in source.rglob('*'):
        if not path.is_file() or path.suffix not in ['.hpp', '.cpp']:
            continue
        rel = path.relative_to(source)
        text = path.read_text()
        if str(rel) == REPORT + '.hpp':
            text = report_header
        if path.suffix == '.hpp':
            text = re.sub(r'^#include "[^"\n]*compiler_profile_event_row\.hpp"\n', '', text, flags=re.M)
        else:
            needs_row = bool(re.search(r'\bCompilerProfileEventRow\b|->profile_events\b', text))
            if str(rel) == REPORT + '.cpp':
                text += special_members
                needs_row = True
            if needs_row:
                text = '#include "' + ROW + '"\n' + text
                complete.append(str(rel))
        write_changed(target / rel, text)
    ninja = (build / 'latency-callable-parts.ninja').read_text()
    ninja = ninja.replace(str(source), str(target)).replace('../callables/generated', '../layout/generated')
    ninja = ninja.replace('callable-project.pch', 'layout-project.pch')
    ninja = ninja.replace('callables_runtime_pch.hpp', 'layout_runtime_pch.hpp')
    # Independent objects are required because report special-member definitions
    # differ from other experiments; never mix old/new native class definitions.
    ninja = re.sub(r'(?<!\S)callables/', 'layout/', ninja)
    ninja = re.sub(r'(?<!\S)callable_parts/', 'layout_parts/', ninja)
    write_changed(build / 'layout_runtime_pch.hpp', (build / 'app_pch.hpp').read_text())
    write_changed(build / 'latency-layout.ninja', ninja)
    import json
    write_changed(build / 'latency-layout-complete-types.json', json.dumps({'complete_row_implementations': complete, 'outlined_special_members': REPORT, 'member_noexcept_assertions': len(fields) * 2}, indent=2) + '\n')


if __name__ == '__main__':
    p = argparse.ArgumentParser()
    p.add_argument('--app', type=Path, required=True)
    prepare(p.parse_args().app.resolve())
