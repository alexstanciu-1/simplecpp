"""Measure the focused native error header against its former aggregate owner."""
from pathlib import Path
import json
import subprocess
import tempfile
import time
root = Path(__file__).resolve().parents[3]
rows = []
with tempfile.TemporaryDirectory(prefix='scpp-native-error-cost-') as directory:
    stage = Path(directory)
    for name, header in [('focused','scpp/native_value_errors.hpp'),('aggregate','core/string_support.hpp')]:
        path=stage/(name+'.cpp')
        path.write_text('#include "'+header+'"\nvoid reject() { throw scpp::ValueError("invalid value"); }\n')
        start=time.perf_counter()
        subprocess.run(['clang++','-std=c++23','-O2','-I'+str(root/'runtime/include'),'-c',str(path),'-o',str(stage/(name+'.o'))],check=True)
        rows.append({'case':name,'compile_seconds':time.perf_counter()-start})
    dependencies=subprocess.check_output(['clang++','-std=c++23','-I'+str(root/'runtime/include'),'-M','-x','c++','-'],input='#include "operators/arithmetic/integer_shift.hpp"\n',text=True)
    assert 'core/string_support.hpp' not in dependencies and 'scpp/int_t.hpp' not in dependencies
print(json.dumps(rows,indent=2))
print('PASS: shift validation does not include string helpers or integer templates')
