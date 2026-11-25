from pathlib import Path
root=Path('/home/alexv/__AI/simple_cpp/simple_cpp_01');app=root/'compiler/my-try';out=Path('/tmp/bootstrap-layout-cycle-01');out.mkdir(exist_ok=False)
boot=(app/'boot.php').read_text().replace('dirname(__DIR__, 2)',repr(str(root))).replace('__DIR__',repr(str(app)))
s=(app/'05_backend/cpp/specializations.php').read_text()
needle="throw new \\RuntimeException('Concrete layout dependencies contain a generated header cycle');"
probe='''$audit = ['known'=>$known, 'depths'=>$depths, 'artifacts'=>[], 'entries'=>[]];
foreach ($artifacts as $artifact) {
 if (isset($known[$artifact->file_name]) && !isset($depths[$artifact->file_name])) {
  $audit['artifacts'][] = ['header'=>$artifact->file_name, 'dependencies'=>$artifact->header_dependencies, 'text'=>$artifact->text];
 }
}
foreach ($entries as $entry) {
 if (!isset($depths[$entry->header])) {
  $audit['entries'][] = ['name'=>$entry->name,'header'=>$entry->header,'member_owner'=>$entry->member_owner,'dependencies'=>$entry->layout_dependencies,'layout'=>$entry->layout_statements];
 }
}
file_put_contents('/tmp/bootstrap-layout-cycle-01/graph.json', json_encode($audit, JSON_PRETTY_PRINT));
'''
assert s.count(needle)==1
(out/'specializations.php').write_text(s.replace(needle,probe+needle))
boot=boot.replace(str(app)+"' . '/05_backend/cpp/specializations.php",str(out)+"' . '/specializations.php")
(out/'boot.php').write_text(boot)
driver=(app/'tools/bootstrap_probe.php').read_text().replace("require $argv[1] . '/boot.php';", "require '/tmp/bootstrap-layout-cycle-01/boot.php';")
(out/'driver.php').write_text(driver)
import sys
sys.path.insert(0,str(app/'tools'))
from test_runner import CommandRunner,Task,run_tasks
with CommandRunner(out/'logs',root) as runner:
 run_tasks([Task('audit',lambda:runner.run('audit',['/usr/bin/time','-v','-o',out/'bootstrap.time','php8.5','-d','opcache.enable_cli=0','-d','xdebug.mode=off','-d','memory_limit=4G',out/'driver.php',app,'/tmp/compiler-bootstrap-construction-facts-01/phpp',out/'bootstrap.json'],expected=1,timeout=900))],jobs=1)
