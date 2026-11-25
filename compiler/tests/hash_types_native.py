"""Instrument declaration-only hash signatures; no hash values are constructed or operated on."""
import argparse
from pathlib import Path
import tempfile
from strings_native import prove

if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    if args.output:
        prove(args.output.resolve(), 'hash_types.php', check_copy=False)
    else:
        with tempfile.TemporaryDirectory(prefix='scpp-hash-types-native-') as directory:
            prove(Path(directory), 'hash_types.php', check_copy=False)
