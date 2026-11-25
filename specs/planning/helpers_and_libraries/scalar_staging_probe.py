"""Compare C++ emission forms for pure scalar expressions. Emit raw JSON on stdout."""
import hashlib
import json
from pathlib import Path
import platform
import random
import subprocess
import tempfile
import time


def source(mode):
    functions = []
    for i in range(128):
        a = f"(x + {i + 1}u)"
        b = f"(y ^ {i + 3}u)"
        direct = f"return {a} * {b};"
        staged = f"const unsigned lhs = {a}; const unsigned rhs = {b}; return lhs * rhs;"
        body = {"direct": direct, "locals": staged, "lambda": f"return [&]() {{ {staged} }}();"}[mode]
        functions.append(f'extern "C" unsigned f{i}(unsigned x, unsigned y) {{ {body} }}')
    return "\n".join(functions) + "\n"


def main():
    flags = ["clang++", "-std=c++23", "-O2", "-g0", "-c", "probe.cpp", "-o", "probe.o"]
    modes = ["direct", "locals", "lambda"]
    rng = random.Random(419)
    rows = {mode: {} for mode in modes}
    with tempfile.TemporaryDirectory() as directory:
        work = Path(directory)
        for i in range(-1, 9):
            rng.shuffle(modes)
            for mode in modes:
                cpp = source(mode)
                (work / "probe.cpp").write_text(cpp)
                start = time.perf_counter()
                subprocess.run(flags, cwd=work, check=True)
                elapsed = (time.perf_counter() - start) * 1000
                if i >= 0:
                    rows[mode].setdefault("compile_ms", []).append(round(elapsed, 3))
                    continue
                subprocess.run(["objcopy", "-O", "binary", "-j", ".text", "probe.o", "text.bin"], cwd=work, check=True)
                code = (work / "text.bin").read_bytes()
                rows[mode].update(cpp_bytes=len(cpp), text_bytes=len(code), text_sha256=hashlib.sha256(code).hexdigest())
    print(json.dumps(dict(host=platform.node(), target=platform.machine(), flags=flags,
                          clang=subprocess.check_output(["clang++", "--version"], text=True).splitlines()[0],
                          results=rows), indent=2))


if __name__ == "__main__":
    main()
