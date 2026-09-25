#!/usr/bin/env python3
"""Check/apply PHP block layout and check purpose documentation in my-try.

Whitespace-only layout follows docs/code_style.md. Human review owns meaningful
segmentation, comment quality, expression grouping, and method call-flow order.
"""
import argparse
from pathlib import Path
import subprocess

ROOT = Path(__file__).resolve().parents[1]
PHP_TOKENS = r'''
$s = stream_get_contents(STDIN); $out = [];
foreach (token_get_all($s) as $t) {
    $out[] = is_array($t) ? [token_name($t[0]), $t[1]] : [$t, $t];
}
echo json_encode($out, JSON_THROW_ON_ERROR);
'''


def tokens(source):
    import json
    raw = json.loads(subprocess.run(['php', '-r', PHP_TOKENS], input=source,
                                    text=True, capture_output=True, check=True).stdout)
    result, offset, quoted, heredoc = [], 0, False, False
    for kind, text in raw:
        # Interpolated strings contain punctuation tokens that are not code blocks.
        protected = quoted or kind == '"' or heredoc or kind == 'T_START_HEREDOC'
        if kind == 'T_START_HEREDOC':
            heredoc = True
        elif kind == 'T_END_HEREDOC':
            heredoc = False
        if kind == '"':
            quoted = not quoted
        result.append(dict(kind=kind, text=text, start=offset, end=offset + len(text), protected=protected))
        offset += len(text)
    return result


def significant(source):
    return [t for t in tokens(source) if t['kind'] != 'T_WHITESPACE']


def blocks(ts):
    stack, result = [], []
    boundary = 0
    for i, t in enumerate(ts):
        if t['protected'] or t['kind'] in ('T_COMMENT', 'T_DOC_COMMENT'):
            continue
        if t['kind'] == '{':
            header = ts[boundary:i]
            functions = [j for j, v in enumerate(header) if v['kind'] == 'T_FUNCTION']
            named = bool(functions and functions[-1] + 1 < len(header)
                         and header[functions[-1] + 1]['kind'] == 'T_STRING')
            stack.append((i, named, boundary))
            boundary = i + 1
        elif t['kind'] == '}':
            if stack:
                start, named, head = stack.pop()
                result.append((start, i, named, head))
            boundary = i + 1
        elif t['kind'] == ';':
            boundary = i + 1
    return result


def layout(source):
    ts = significant(source)
    pairs = blocks(ts)
    opening = {a: (b, named) for a, b, named, _ in pairs}
    closing = {b for _, b, _, _ in pairs}
    # Choose each gap independently. Preserve existing logical blank lines.
    gaps = [source[:ts[0]['start']]] if ts else ['']
    for i, token in enumerate(ts):
        end = ts[i + 1]['start'] if i + 1 < len(ts) else len(source)
        gap = source[token['end']:end]
        if i in opening or i + 1 in closing:
            gap = '\n'
        elif i in closing:
            next_text = ts[i + 1]['text'] if i + 1 < len(ts) else ''
            if next_text not in (';', ',', ')', ']', '->', '?->'):
                gap = '\n\n' if gap.count('\n') > 1 else '\n'
        gaps.append(gap)
    expanded = gaps[0] + ''.join(t['text'] + gaps[i + 1] for i, t in enumerate(ts))
    ts = significant(expanded)
    pairs = blocks(ts)
    replacements = []
    for a, b, named, _ in pairs:
        code_lines = set()
        for t in ts[a + 1:b]:
            if t['kind'] not in ('T_COMMENT', 'T_DOC_COMMENT'):
                code_lines.add(expanded.count('\n', 0, t['start']))
        previous = ts[a - 1]
        gap = '\n' if named or len(code_lines) > 5 else ' '
        # A line comment requires a newline regardless of block length.
        if previous['kind'] == 'T_COMMENT':
            gap = '\n'
        replacements.append((previous['end'], ts[a]['start'], gap))
    for a, b, text in reversed(sorted(replacements)):
        expanded = expanded[:a] + text + expanded[b:]
    # Reindent code lines by block depth, including nested array literals. String and comment token contents are preserved.
    ts = significant(expanded)
    depth, array_depth, events = 0, 0, {}
    protected_lines = set()
    opens = {a for a, _, _, _ in blocks(ts)}
    closes = {b for _, b, _, _ in blocks(ts)}
    for i, t in enumerate(ts):
        line = expanded.count('\n', 0, t['start'])
        if t['protected']:
            protected_lines.add(line)
            continue
        if i in closes:
            depth -= 1
        if t['kind'] == ']':
            array_depth -= 1
        if line not in events:
            events[line] = depth + array_depth
        if t['kind'] == '[':
            array_depth += 1
        if i in opens:
            depth += 1
    lines = expanded.splitlines(keepends=True)
    for line, depth in events.items():
        if line in protected_lines:
            continue
        # Preserve continuation indentation beyond the structural minimum.
        text = lines[line].lstrip(' \t')
        lines[line] = '\t' * max(depth, 0) + text
    return ''.join(lines)


def missing_docs(source):
    ts = significant(source)
    missing = []
    for a, b, named, head in blocks(ts):
        if not named:
            continue
        lines = {source.count('\n', 0, t['start']) for t in ts[a + 1:b]
                 if t['kind'] not in ('T_COMMENT', 'T_DOC_COMMENT')}
        if len(lines) <= 5:
            continue
        header = ts[head:a]
        function = next(i for i, t in enumerate(header) if t['kind'] == 'T_FUNCTION')
        # A purpose doc precedes the modifiers/signature, not just parameters.
        if not any(t['kind'] == 'T_DOC_COMMENT' for t in header[:function]):
            t = header[function]
            missing.append((source.count('\n', 0, t['start']) + 1, header[function + 1]['text']))
    return missing


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--write', action='store_true', help='Apply whitespace-only block layout')
    args = parser.parse_args()
    failed = False
    paths = sorted(p for p in ROOT.rglob('*.php') if 'build' not in p.relative_to(ROOT).parts)
    paths += sorted(ROOT.glob('tests/*.php.in'))
    for path in paths:
        source = path.read_text()
        expected = layout(source)
        if [(t['kind'], t['text']) for t in significant(source)] != [(t['kind'], t['text']) for t in significant(expected)]:
            raise RuntimeError(f'Layout changed PHP tokens: {path}')
        if source != expected:
            if args.write:
                path.write_text(expected)
                source = expected
            else:
                print(f'{path.relative_to(ROOT)}: block layout differs; run tools/style_check.py --write')
                failed = True
        for line, name in missing_docs(source):
            print(f'{path.relative_to(ROOT)}:{line}: purpose doc required for {name}')
            failed = True
    if not failed:
        print(f'Style: {len(paths)} PHP sources satisfy block layout and long-method documentation checks')
    raise SystemExit(1 if failed else 0)


if __name__ == '__main__':
    main()
