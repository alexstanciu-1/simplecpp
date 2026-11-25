import os,sys,time
log,lang,mode,clang,target=sys.argv[1:]
with open(log,'a') as f:f.write(lang+'\n')
if mode=='fail':sys.stderr.write('selected tool failed');sys.exit(5)
if mode=='timeout':time.sleep(10)
if mode=='bad_header' and lang=='c++':
 print('target triple = "wrong"');print('target datalayout = "wrong"');sys.exit(0)
if mode=='mismatch' and lang=='ir':
 source=sys.stdin.read()
 for line in source.splitlines():
  if line.startswith('target '):print(line)
 print('@primitive_size_1 = constant i64 99')
 print('@primitive_alignment_1 = constant i64 99')
 sys.exit(0)
args=[clang,'-target',target,'-x',lang,'-O2','-S','-emit-llvm','-','-o','-']
if lang=='c++':args+=['-std=c++23']
os.execv(clang,args)
