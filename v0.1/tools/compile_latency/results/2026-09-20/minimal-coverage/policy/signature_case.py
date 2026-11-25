"""A real profiling signature edit, shared by layout and scheduling probes."""
import re


def make_case(app):
    source = 'compile/support/compiler_profile_events.phs'
    originals = {str(path.relative_to(app)): path.read_text() for path in app.rglob('*.phs')
                 if '.prism' not in path.parts and 'compiler_profile_events::elapsed_us_since(' in path.read_text()}
    originals['main.phs'] = (app / 'main.phs').read_text()
    edited, counts = {}, {}
    for key, original in originals.items():
        changed, count = re.subn(r'compiler_profile_events::elapsed_us_since\((\$\w+)\)', r'compiler_profile_events::elapsed_us_since(\1, structure_row_ids::uint32_from_int(0))', original)
        counts[key] = count
        if key == source:
            needle = 'public static function elapsed_us_since(uint64 $startedUs): uint32 {'
            assert changed.count(needle) == 1
            changed = changed.replace(needle, 'public static function elapsed_us_since(uint64 $startedUs, uint32 $minimumUs): uint32 {')
            needle = '$elapsedUs int = (int)$finishedUs - (int)$startedUs;'
            assert changed.count(needle) == 1
            changed = changed.replace(needle, needle + '\n\t\tif ($elapsedUs < (int)$minimumUs) { return $minimumUs; }')
        if key == 'main.phs':
            changed += '\necho "signature_probe=" . (string)compiler_profile_events::elapsed_us_since(dt_monotonic_us(), structure_row_ids::uint32_from_int(123456789)) . "\\n";\n'
        edited[key] = changed
    return originals, edited, counts
