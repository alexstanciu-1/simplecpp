"""Compile the actual prepared Packet field loop with only the application runtime."""
from pathlib import Path
import subprocess
import tempfile

from app_namespace_native import write_build

tests = Path(__file__).resolve().parent
with tempfile.TemporaryDirectory(prefix='boxed-record-decode-') as directory:
    work = Path(directory)
    cpp = subprocess.check_output(['php', tests/'boxed_record_decode.php'])
    assert b'Packet::decode(::scpp::Decoder&' in cpp and b'scpp/json' not in cpp
    (work/'packet.cpp').write_bytes(cpp)
    probe = work/'probe.cpp'
    probe.write_bytes((tests/'fixtures/boxed_record_decode.cpp').read_bytes())
    subprocess.run(write_build(work, [probe]), cwd=work, check=True)
    subprocess.run([work/'program'], check=True)
