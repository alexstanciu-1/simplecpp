"""Instrument only the final one-byte append in the real lexical reader."""
from pathlib import Path
import subprocess
import sys

reader, out = map(Path, sys.argv[1:3])
flags = sys.argv[3:]
out.mkdir(parents=True, exist_ok=True)
text = reader.read_text()
append = 'out += utf8_from_codepoint(high);'
assert text.count(append) == 1
text = text.replace(append, '''
                    const auto decoded = utf8_from_codepoint(high);
                    const auto before = out.capacity();
                    const auto address = reinterpret_cast<std::uintptr_t>(out.data());
                    const auto start_append = Clock::now();
                    out += decoded;
                    const auto end_append = Clock::now();
                    append_time += end_append - start_append;
                    ++observations;
                    growths += before != out.capacity();
                    moved += address != reinterpret_cast<std::uintptr_t>(out.data());
                    capacity_before = before;
                    capacity_after = out.capacity();''')
(out/'reader.hpp').write_text(text)
subprocess.run(['clang++', '-std=c++23', '-stdlib=libc++', '-O3', '-DNDEBUG', *flags, '-I', out,
                Path(__file__).with_name('probe.cpp'), '-o', out/'probe'], check=True)
