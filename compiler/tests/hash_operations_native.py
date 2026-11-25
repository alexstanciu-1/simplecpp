"""Verify hash operations and incremental recovery with instrumented native builds."""
import argparse
from pathlib import Path
import tempfile
from strings_native import prove

if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    if args.output:
        prove(args.output.resolve(), 'hash_operations.php', check_copy=False)
    else:
        with tempfile.TemporaryDirectory(prefix='scpp-hash-operations-native-') as directory:
            prove(Path(directory), 'hash_operations.php', check_copy=False)
