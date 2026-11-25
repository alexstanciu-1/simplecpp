"""Run common value-passing and incremental recovery fixtures through the instrumented native build proof."""
import argparse
from pathlib import Path
import tempfile
from strings_native import prove

if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    if args.output:
        prove(args.output.resolve(), 'value_passing.php', check_copy=False)
    else:
        with tempfile.TemporaryDirectory(prefix='scpp-value-passing-native-') as directory:
            prove(Path(directory), 'value_passing.php', check_copy=False)
