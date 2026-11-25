"""Run direct_lowering fixtures through the same instrumented native build proof as string locals."""
import argparse
from pathlib import Path
import tempfile
from strings_native import prove

if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    if args.output:
        prove(args.output.resolve(), 'direct_lowering.php', check_copy=False)
    else:
        with tempfile.TemporaryDirectory(prefix='scpp-direct_lowering-native-') as directory:
            prove(Path(directory), 'direct_lowering.php', check_copy=False)
