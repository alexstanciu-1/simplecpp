from pathlib import Path
import subprocess,time
while not Path("native-pool-acceptance/result.json").exists(): time.sleep(1)
with open("performance-native08.log","w") as log:
 subprocess.run(["python3","rebuild_performance_native_lines.py"],stdout=log,stderr=subprocess.STDOUT,check=True)
for label in ["32-lines","32-lines-warm"]:
 subprocess.run(["python3","native_build_lines_measure.py","32",label],check=True)
subprocess.run(["python3","native_pool_failure.py"],check=True)
subprocess.run(["python3","native_build_lines_self.py","32","32-lines-self"],check=True)
subprocess.run(["python3","native_pool_lines_acceptance.py"],check=True)
