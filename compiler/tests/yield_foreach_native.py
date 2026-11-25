"""Build and run suspended foreach fixtures with native phase timings."""
import argparse
from pathlib import Path
import tempfile
from strings_native import prove

if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    if args.output:
        prove(args.output.resolve(), 'yield_foreach.php', check_copy=False)
    else:
        with tempfile.TemporaryDirectory(prefix='scpp-yield-foreach-') as directory:
            prove(Path(directory), 'yield_foreach.php', check_copy=False)
