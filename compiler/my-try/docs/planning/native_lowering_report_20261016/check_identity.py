from pathlib import Path
import json,hashlib
B=Path(__file__).resolve().parent;R=B.parent/'repo';identity=json.loads((B/'dev002/native-identity.json').read_text());count=0
for path,expected in identity['sources'].items():
 source=B/'driver.php' if path=='main.php' else R/'compiler/my-try'/path
 assert hashlib.sha256(source.read_bytes()).hexdigest()==expected,path
 count+=1
print('Matched',count,'authored sources including the experiment driver')
