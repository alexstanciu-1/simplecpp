"""Host-owned fixed filesystem inputs for PHP/native scanner proofs."""
import os
from pathlib import Path


def prepare_scan_fixture(main: Path, root: Path):
    root.mkdir()
    (root / 'good').mkdir()
    (root / 'good/sub').mkdir()
    for name, content in [('a.phs', b'abc'), ('z.phs', b''), ('note.txt', b'ignored'), ('é.phs', 'é'.encode())]:
        path = root / 'good' / name
        path.write_bytes(content)
        os.utime(path, (1700000000, 1700000000))
    (root / 'linked').symlink_to(root / 'good', target_is_directory=True)
    (root / 'bad').mkdir()
    (root / 'bad/link.txt').symlink_to(root / 'good/a.phs')
    spelling = str(root).replace('\\', '\\\\').replace("'", "\\'")
    main.write_text(main.read_text().replace('/__scpp_scan_fixture__', spelling))
