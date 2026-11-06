"""Run byte/uint8 alias and incremental recovery fixtures through the instrumented native build proof."""
import argparse
from pathlib import Path
import tempfile
from strings_native import prove

if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    if args.output:
        prove(args.output.resolve(), 'byte_alias.php', check_copy=False)
    else:
        with tempfile.TemporaryDirectory(prefix='scpp-byte-alias-native-') as directory:
            prove(Path(directory), 'byte_alias.php', check_copy=False)
