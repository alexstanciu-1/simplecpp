"""Entry API stays standalone and lightweight while checking the consuming compiler target."""
from pathlib import Path
import re
import subprocess
import tempfile

root = Path(__file__).resolve().parents[1]
with tempfile.TemporaryDirectory(prefix='scpp-entry-api-') as directory:
    source = Path(directory)/'entry.cpp'
    source.write_text('#include <scpp/entry.hpp>\nSCPP_REQUIRE_ENTRY_TARGET(scpp::detail::native_int_digits());\nint main() { return scpp::entry_exit_checked(8); }\n')
    flags = ['clang++','-std=c++23','-stdlib=libc++','-I',str(root/'runtime/generated/public')]
    def run(*args):
        return subprocess.run([*flags,*map(str,args)],capture_output=True,text=True,timeout=60)
    result = run('-E','-P',source)
    assert result.returncode == 0 and not re.search(r'\b(template|typename)\b',result.stdout),result.stderr
    deps = run('-M',source)
    assert deps.returncode == 0
    for forbidden in ['/limits ', '/stdexcept', '/string', '/system_error']:
        assert forbidden not in deps.stdout, deps.stdout
    binary = Path(directory)/'entry'
    result = run(source,root/'build/clang/libscpp_runtime.a','-o',binary)
    assert result.returncode == 0,result.stderr
    assert subprocess.run([binary],timeout=5).returncode == 8
    source.write_text('#include <scpp/entry.hpp>\nSCPP_REQUIRE_ENTRY_TARGET(1);\n')
    result = run('-fsyntax-only',source)
    assert result.returncode != 0 and 'Entry target does not match' in result.stderr,result.stderr
print('Entry public API: isolated include, template-free closure, runtime link and compile-time target rejection passed')
