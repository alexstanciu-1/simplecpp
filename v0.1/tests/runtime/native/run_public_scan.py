import pathlib
import subprocess
import sys
import tempfile
with tempfile.TemporaryDirectory(prefix='scpp-scan-') as directory:
    root = pathlib.Path(directory)
    for name in ['b', 'long_filename_for_allocation_parity_proof', 'a']:
        (root/name).write_text('fixture')
    subprocess.run([sys.argv[1], directory], check=True)
