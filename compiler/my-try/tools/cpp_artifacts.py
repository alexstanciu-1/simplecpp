"""Versioned C++ artifact transport shared by host and native fixture consumers."""
import json
from pathlib import Path, PurePosixPath
import re


def decode_artifacts(payload):
    """Validate the complete bundle before allowing any filesystem publication."""
    bundle = json.loads(payload)
    if not isinstance(bundle, dict) or bundle.get('format') != 'scpp-cpp-artifacts-v1':
        raise ValueError('Unsupported C++ artifact format')
    artifacts = bundle.get('artifacts')
    if not isinstance(artifacts, list) or not artifacts:
        raise ValueError('Expected nonempty C++ artifacts')
    identities, paths = set(), set()
    for artifact in artifacts:
        if not isinstance(artifact, dict):
            raise ValueError('Expected a C++ artifact record')
        if any(not isinstance(artifact.get(key), str) for key in ('id', 'kind', 'path', 'text')):
            raise ValueError('C++ artifact fields must be strings')
        identity, path = artifact['id'], artifact['path']
        parts = path.split('/')
        if (not identity or identity in identities or path in paths
                or any(part in ('', '.', '..') for part in parts)
                or not re.fullmatch(r'[A-Za-z0-9_./-]+', path)):
            raise ValueError('Duplicate identity/path or unsafe C++ artifact path')
        if artifact['kind'] not in ('header', 'translation_unit'):
            raise ValueError('Unsupported C++ artifact kind')
        suffix = PurePosixPath(path).suffix
        if suffix != ('.hpp' if artifact['kind'] == 'header' else '.cpp'):
            raise ValueError('C++ artifact kind/path mismatch')
        identities.add(identity)
        paths.add(path)
    # A file may not also be a parent directory of another file.
    for path in paths:
        if any(str(parent) in paths for parent in PurePosixPath(path).parents):
            raise ValueError('Conflicting C++ artifact paths')
    if not any(artifact['kind'] == 'translation_unit' for artifact in artifacts):
        raise ValueError('C++ bundle has no translation units')
    return artifacts


def single_unit_text(payload):
    """Guard legacy source-instrumentation probes; never silently discard files."""
    artifacts = decode_artifacts(payload)
    if len(artifacts) != 1 or artifacts[0]['kind'] != 'translation_unit':
        raise ValueError('Single-unit probe cannot consume a partitioned C++ bundle')
    return artifacts[0]['text']


def probe_text(payload):
    """Match the editable PHP test transport without flattening generated files."""
    artifacts = decode_artifacts(payload)
    if len(artifacts) == 1:
        return single_unit_text(payload)
    return ''.join('// SCPP-ARTIFACT ' + json.dumps({key: artifact[key] for key in ('id', 'kind', 'path')},
                   ensure_ascii=True, separators=(',', ':')).replace('/', '\\/') + '\n'
                   + artifact['text'] + '// SCPP-END-ARTIFACT\n' for artifact in artifacts)


def probe_entry(text):
    """Let explicit function-try probes observe failures outside generated main."""
    if 'int main(int argc, char** argv) try' not in text:
        return text
    # Remove only the marked production boundary, preserving source handlers.
    text = text.replace('// SCPP-ENTRY-TRY\n\ttry {\n', '')
    return re.sub(r'^// SCPP-ENTRY-CATCH\n.*?^// SCPP-ENTRY-END\n',
                  '', text, flags=re.M | re.S)


def probe_bundle(text, *, instrumented=False):
    """Recover files after test-only textual instrumentation, without combining definitions."""
    pattern = re.compile(r'^// SCPP-ARTIFACT (\{[^\n]*\})\n(.*?)^// SCPP-END-ARTIFACT\n', re.M | re.S)
    matches = list(pattern.finditer(text))
    if not matches:
        entry_text = probe_entry(text) if instrumented else text
        if entry_text != text:
            return json.dumps({'format': 'scpp-cpp-artifacts-v1', 'artifacts': [
                {'id': 'entry', 'kind': 'translation_unit', 'path': 'entry.cpp', 'text': entry_text}]})
        return None
    prefix = text[:matches[0].start()]
    suffix = text[matches[-1].end():]
    artifacts = []
    previous = matches[0].start()
    for match in matches:
        if text[previous:match.start()].strip():
            raise ValueError('Instrumentation crossed artifact boundaries')
        artifact = json.loads(match[1])
        artifact['text'] = prefix + match[2]
        artifacts.append(artifact)
        previous = match.end()
    entry = next(artifact for artifact in artifacts if artifact['id'] == 'entry')
    if instrumented or suffix.strip():
        # Test assertions inserted into or appended after main may name declarations
        # unused by the source entry. Only the probe entry gets these dependencies;
        # production bundles and uninstrumented transport roundtrips stay unchanged.
        headers = ''.join('#include "' + artifact['path'] + '"\n' for artifact in artifacts
                          if artifact['kind'] == 'header')
        entry['text'] = headers + entry['text'] + suffix
    if instrumented:
        entry['text'] = probe_entry(entry['text'])
    return json.dumps({'format': 'scpp-cpp-artifacts-v1', 'artifacts': artifacts})


def materialize_artifacts(payload, directory):
    """Write all artifacts into a fresh private fixture directory, not a project cache."""
    artifacts = decode_artifacts(payload)
    root = Path(directory).resolve()
    root.mkdir(parents=True, exist_ok=False)
    sources = []
    for artifact in artifacts:
        path = root / artifact['path']
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_bytes(artifact['text'].encode('utf-8'))
        if artifact['kind'] == 'translation_unit':
            sources.append(path)
    return sources
