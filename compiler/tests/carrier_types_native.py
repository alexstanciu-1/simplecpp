"""Instrument declaration-only carrier signatures; no carrier values are constructed or operated on."""
import argparse
from pathlib import Path
import tempfile
from strings_native import prove

if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    if args.output:
        prove(args.output.resolve(), 'carrier_types.php', check_copy=False)
    else:
        with tempfile.TemporaryDirectory(prefix='scpp-carrier-types-native-') as directory:
            prove(Path(directory), 'carrier_types.php', check_copy=False)
