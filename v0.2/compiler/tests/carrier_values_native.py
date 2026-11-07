"""Verify packed carrier conversion execution, failure guards and incremental checkpoints with Clang timings."""
import argparse
from pathlib import Path
import tempfile
from strings_native import prove

if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    if args.output:
        prove(args.output.resolve(), 'carrier_values.php', check_copy=False)
    else:
        with tempfile.TemporaryDirectory(prefix='scpp-carrier-values-native-') as directory:
            prove(Path(directory), 'carrier_values.php', check_copy=False)
