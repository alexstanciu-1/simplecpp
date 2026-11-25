"""Compare focused/full identity includes and verify compatibility overload lookup."""
import argparse
from pathlib import Path
import subprocess
import tempfile
import time
root=Path(__file__).resolve().parents[3]
parser=argparse.ArgumentParser(description=__doc__)
parser.add_argument('--core',type=Path,required=True)
parser.add_argument('--sanitize',action='store_true')
args=parser.parse_args()
flags=['-std=c++23','-O1','-I'+str(root/'runtime/include'),'-I'+str(root/'runtime/public')]
if args.sanitize:flags+=['-fsanitize=address,undefined','-fno-omit-frame-pointer']
code=(root/'tests/runtime/native/test_shared_identity_header.cpp').read_text()
with tempfile.TemporaryDirectory(prefix='scpp-shared-boundary-') as directory:
    work=Path(directory)
    for mode in ['full','focused']:
        source=code.replace('operators/identity/shared_identity.hpp','operators/identity/strict_identity.hpp') if mode=='full' else code
        file=work/(mode+'.cpp');file.write_text(source)
        dependencies=subprocess.check_output(['clang++',*flags,'-M',str(file)],text=True)
        if mode=='focused':
            for forbidden in ['scpp/generated/operators.hpp','scpp/mixed_t.hpp','scpp/hash_t.hpp','operators/identity/strict_identity.hpp']:
                assert forbidden not in dependencies,forbidden
        obj=work/(mode+'.o');start=time.perf_counter()
        subprocess.run(['clang++',*flags,'-c',str(file),'-o',str(obj)],check=True)
        elapsed=time.perf_counter()-start
        executable=work/mode
        subprocess.run(['clang++',*flags,str(obj),str(args.core.resolve()),'-pthread','-ldl','-o',str(executable)],check=True)
        subprocess.run([str(executable)],check=True)
        print(f'{mode}: compile={elapsed:.4f}s object={obj.stat().st_size} bytes')
    # Existing broad-header test also exercises nullable normalization, unrelated
    # fallback and scalar overloads after the shared overload moved to its owner.
    legacy=work/'legacy'
    subprocess.run(['clang++',*flags,str(root/'tests/runtime/native/test_strict_identity_shared.cpp'),
                    str(args.core.resolve()),'-pthread','-ldl','-o',str(legacy)],check=True)
    subprocess.run([str(legacy)],check=True)
    print('PASS: focused dependency boundary, adjusted identity and existing broad/nullable/scalar compatibility')
