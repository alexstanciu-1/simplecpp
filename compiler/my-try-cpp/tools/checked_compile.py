"""Clang launcher: reject active templates in the whole compiler include closure."""
import re
import subprocess
import sys


def main():
    compiler, *args = sys.argv[1:]
    scan = []
    skip = False
    for arg in args:
        if skip:
            skip = False
        elif arg in ("-o", "-MF", "-MT", "-MQ"):
            skip = True
        elif arg not in ("-c", "-MD", "-MMD", "-MP"):
            scan.append(arg)
    result = subprocess.run(
        [compiler, *scan, "-fsyntax-only", "-Xclang", "-dump-tokens"],
        capture_output=True, text=True,
    )
    forbidden = re.search(r"^(template|typename) .*", result.stderr, re.MULTILINE)
    if result.returncode or forbidden:
        print("Compiler template boundary rejected:", file=sys.stderr)
        print(forbidden.group(0) if forbidden else result.stderr, file=sys.stderr)
        return 1
    return subprocess.run([compiler, *args]).returncode


if __name__ == "__main__":
    sys.exit(main())
