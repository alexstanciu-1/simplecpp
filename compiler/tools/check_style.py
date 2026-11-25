"""Check mechanical PHP writing rules; ownership meaning and purpose remain review responsibilities."""
import json
import os
import re
from pathlib import Path
import subprocess
import sys

ROOT = Path(__file__).resolve().parents[1]
PHP = os.environ.get('PHP_EXECUTABLE', 'php')
# PHP's lexer keeps source examples, interpolated strings and heredocs opaque to layout checks.
LEXER = r'''
$out = [];
foreach (array_slice($argv, 1) as $file) {
    $raw = token_get_all(file_get_contents($file), TOKEN_PARSE);
    $items = [];
    $line = 1;
    $gap = '';
    for ($i = 0; $i < count($raw); $i++) {
        $token = $raw[$i];
        $kind = is_array($token) ? token_name($token[0]) : $token;
        $text = is_array($token) ? $token[1] : $token;
        if ($kind === 'T_WHITESPACE') {
            $gap .= $text;
            $line += substr_count($text, "\n");
            continue;
        }
        if ($kind === 'T_START_HEREDOC' || $kind === '"') {
            $end = $kind === '"' ? '"' : 'T_END_HEREDOC';
            while (++$i < count($raw)) {
                $next = $raw[$i];
                $text .= is_array($next) ? $next[1] : $next;
                if ((is_array($next) ? token_name($next[0]) : $next) === $end) { break; }
            }
            $kind = 'LITERAL';
        }
        $items[] = [$kind, $text, $gap, $line];
        $line += substr_count($text, "\n");
        $gap = '';
    }
    $out[$file] = $items;
}
echo json_encode($out, JSON_THROW_ON_ERROR);
'''
MODIFIERS = {'T_PUBLIC', 'T_PRIVATE', 'T_PROTECTED', 'T_STATIC', 'T_FINAL', 'T_ABSTRACT', 'T_READONLY'}
COMMENTS = {'T_DOC_COMMENT', 'T_COMMENT'}


GROUPS = ('CONSTANTS', 'STATIC PROPERTIES', 'PROPERTIES', 'PREP FACTS', 'CONSTRUCTOR', 'DESTRUCTOR', 'METHODS', 'STATIC METHODS')
MARKERS = {f'/** {name} */' for name in GROUPS}


def short_prose_comment(text: str) -> str | None:
    """Return a one-line spelling for short prose, preserving structured documentation."""
    if '\n' not in text or not text.startswith(('/*', '/**')):
        return None
    opener = '/**' if text.startswith('/**') else '/*'
    lines = [re.sub(r'^\s*\* ?', '', line).strip() for line in text[len(opener):-2].splitlines()]
    while lines and not lines[0]:
        lines.pop(0)
    while lines and not lines[-1]:
        lines.pop()
    if not lines or any(not line or line.startswith(('```', '@', '$', '- ', '* ')) for line in lines):
        return None
    compact = opener + ' ' + ' '.join(lines) + ' */'
    return compact if len(compact) < 120 else None


def class_members(tokens: list[list], pairs: dict[int, int]) -> list[tuple]:
    """Locate direct class members, keeping their documentation and treating trait uses as a preamble."""
    classes = []
    for i, token in enumerate(tokens):
        if token[0] != 'T_CLASS' or (i and tokens[i - 1][1] == '::'):
            continue
        opening = next(j for j in range(i + 1, len(tokens)) if tokens[j][1] == '{')
        closing = pairs[opening]
        members = []
        start = opening + 1
        while start < closing:
            j = start
            header = []
            while j < closing:
                kind, text = tokens[j][:2]
                if text == ';':
                    end = j + 1
                    break
                if text == '{':
                    end = pairs[j] + 1
                    break
                header.append(tokens[j])
                j = pairs[j] + 1 if text in ('(', '[') else j + 1
            else:
                raise ValueError('Unterminated class member')
            kinds = {t[0] for t in header}
            if 'T_USE' in kinds:
                group, count = -1, 0
            elif 'T_FUNCTION' in kinds:
                f = next(k for k, t in enumerate(header) if t[0] == 'T_FUNCTION')
                name = header[f + 1][1]
                group = GROUPS.index('CONSTRUCTOR' if name == '__construct' else 'DESTRUCTOR' if name == '__destruct'
                                     else 'STATIC METHODS' if 'T_STATIC' in kinds else 'METHODS')
                count = 1
            elif 'T_CONST' in kinds:
                group = 0
                count = sum(t[1] == '=' for t in header)
            else:
                prep_fact = any(t[0] == 'T_DOC_COMMENT' and re.search(
                    r'\b(?:Prepared (?:fact|cache)|Preparation (?:state|statistics)):', t[1]) for t in header)
                group = GROUPS.index('STATIC PROPERTIES' if 'T_STATIC' in kinds else 'PREP FACTS' if prep_fact else 'PROPERTIES')
                count = sum(t[0] == 'T_VARIABLE' for t in header)
            members.append((start, end, group, count))
            start = end
        classes.append((opening, closing, members))
    return classes


def audit_groups(path: str, tokens: list[list], pairs: dict[int, int]) -> list[str]:
    """Require class group ordering and spacing, with markers only above both agreed size thresholds."""
    errors = []
    for opening, closing, members in class_members(tokens, pairs):
        groups = {group for _, _, group, _ in members if group >= 0}
        marked = len(groups) > 2 and sum(count for _, _, _, count in members) > 5
        previous = -1
        seen = set()
        for start, end, group, count in members:
            line = tokens[start][3]
            if group < previous:
                errors.append(f'{path}:{line}: class member groups are out of order')
            first = group not in seen
            marker_tokens = [t for t in tokens[start:end] if t[0] == 'T_DOC_COMMENT' and t[1] in MARKERS]
            expected = f'/** {GROUPS[group]} */' if marked and group >= 0 and first else None
            if [t[1] for t in marker_tokens] != ([expected] if expected else []):
                errors.append(f'{path}:{line}: incorrect class group markers for group/member counts')
            if expected and tokens[start][1] != expected:
                errors.append(f'{path}:{line}: group marker must precede member documentation')
            if first and seen and tokens[start][2].count('\n') < 2:
                errors.append(f'{path}:{line}: separate class member groups with a blank line')
            previous = group
            seen.add(group)
    return errors


def audit_phpdoc(path: str, tokens: list[list], pairs: dict[int, int]) -> list[str]:
    """Require IDE-facing tags for collection/resource contracts; local inference is reviewed manually."""
    errors = []

    def document(start: int) -> str:
        previous = start - 1
        while previous >= 0 and tokens[previous][0] in MODIFIERS:
            previous -= 1
        return tokens[previous][1] if previous >= 0 and tokens[previous][0] == 'T_DOC_COMMENT' else ''

    def require(doc: str, tag: str, line: int, name: str = '') -> None:
        pattern = re.escape(tag) + r'\s+[^\n]+'
        if name:
            pattern += re.escape(name) + r'\b'
        if not re.search(pattern, doc):
            errors.append(f'{path}:{line}: collection/resource declaration needs {tag} {name} in its preceding PHPDoc')

    for i, token in enumerate(tokens):
        if token[0] != 'T_FUNCTION' or tokens[i + 1][0] != 'T_STRING':
            continue
        doc = document(i)
        opening = next(j for j in range(i + 1, len(tokens)) if tokens[j][1] == '(')
        closing = pairs[opening]
        for j in range(opening + 1, closing):
            if tokens[j][0] != 'T_VARIABLE':
                continue
            previous = j - 1
            while tokens[previous][1] in ('&', '...'):
                previous -= 1
            resource = tokens[j + 1][0] == 'T_DOC_COMMENT' and 'resource' in tokens[j + 1][1]
            if tokens[previous][0] == 'T_ARRAY' or resource:
                require(doc, '@param', tokens[j][3], tokens[j][1])
        end = closing + 1
        while tokens[end][1] not in ('{', ';'):
            if tokens[end][0] == 'T_ARRAY' or (tokens[end][0] == 'T_DOC_COMMENT' and 'resource' in tokens[end][1]):
                require(doc, '@return', token[3])
            end += 1

    for _, _, members in class_members(tokens, pairs):
        for start, end, group, _ in members:
            if group not in (GROUPS.index('STATIC PROPERTIES'), GROUPS.index('PROPERTIES'), GROUPS.index('PREP FACTS')):
                continue
            header = tokens[start:end]
            variable = next((j for j, t in enumerate(header) if t[0] == 'T_VARIABLE'), None)
            if variable is None or not any(t[0] == 'T_ARRAY' for t in header[:variable]):
                continue
            doc = '\n'.join(t[1] for t in header[:variable] if t[0] == 'T_DOC_COMMENT')
            require(doc, '@var', header[variable][3])
    return errors


def audit(path: str, tokens: list[list]) -> list[str]:
    """Check declarations and structural block layout without interpreting embedded source literals."""
    errors = []
    pairs = {}
    stack = []
    ignored = set()
    start = 0
    for i, (kind, text, gap, line) in enumerate(tokens):
        if text in ('(', '[', '{'):
            stack.append(i)
            header = tokens[start:i]
            if text == '{' and any(t[0] == 'T_USE' for t in header) and not any(
                    t[0] in ('T_FUNCTION', 'T_CLASS') for t in header):
                ignored.add(i)
        elif text in (')', ']', '}'):
            opening = stack.pop()
            pairs[opening] = i
            pairs[i] = opening
            if opening in ignored:
                ignored.add(i)
        if text in (';', '{', '}'):
            start = i + 1
        if '\n' in gap and ' ' in gap.rsplit('\n', 1)[1]:
            errors.append(f'{path}:{line}: indent PHP code with tabs')

    for i, (kind, text, gap, line) in enumerate(tokens):
        if kind in COMMENTS and short_prose_comment(text) is not None:
            errors.append(f'{path}:{line}: prose comments shorter than 120 characters belong on one line')
        if text == '{' and i not in ignored:
            end = pairs[i]
            if '\n' not in tokens[i + 1][2] or '\n' not in tokens[end][2]:
                errors.append(f'{path}:{line}: block bodies and closing braces must be multiline')
        if kind == 'T_FUNCTION' and tokens[i + 1][0] == 'T_STRING':
            end = next(j for j in range(i, len(tokens)) if tokens[j][1] in ('{', ';'))
            if tokens[end][1] == ';':
                continue
            if '\n' not in tokens[end][2]:
                errors.append(f'{path}:{line}: named function opening brace must be on its own line')
            body_lines = {t[3] for t in tokens[end + 1:pairs[end]] if t[0] not in COMMENTS}
            preceding = i - 1
            while tokens[preceding][0] in MODIFIERS:
                preceding -= 1
            if len(body_lines) > 5 and (tokens[preceding][0] != 'T_DOC_COMMENT'
                                      or tokens[preceding][1] in MARKERS):
                errors.append(f'{path}:{line}: function longer than five lines needs a purpose doc comment')
        if kind in ('T_CLASS', 'T_INTERFACE', 'T_TRAIT', 'T_ENUM'):
            if i and tokens[i - 1][1] == '::':
                continue
            preceding = i - 1
            while tokens[preceding][0] in MODIFIERS:
                preceding -= 1
            if tokens[preceding][0] != 'T_DOC_COMMENT':
                errors.append(f'{path}:{line}: type declaration needs a role doc comment')
        if kind == 'T_DOC_COMMENT' and i and '\n' not in gap and not tokens[i - 1][1].endswith('\n'):
            annotation = text[3:-2].strip()
            if ';' in annotation or re.search(r'[\w>}\]]\s+[A-Za-z_\\]', annotation):
                errors.append(f'{path}:{line}: inline annotation must contain only a type; move prose above its declaration or statement')
    errors.extend(audit_groups(path, tokens, pairs))
    errors.extend(audit_phpdoc(path, tokens, pairs))
    return errors


def main() -> int:
    """Audit all handwritten PHP compiler files, or explicit files supplied for focused checks."""
    files = sys.argv[1:] or sorted(str(p) for p in ROOT.rglob('*.php') if 'generated' not in p.parts)
    result = subprocess.run([PHP, '-r', LEXER, '--', *files], check=True, capture_output=True, text=True)
    errors = [error for path, tokens in json.loads(result.stdout).items() for error in audit(path, tokens)]
    if errors:
        print('\n'.join(errors), file=sys.stderr)
        return 1
    print(f'Compiler PHP writing checks passed: {len(files)} files')
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
