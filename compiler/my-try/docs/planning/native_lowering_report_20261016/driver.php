<?php
namespace scpp\compiler;
$request = json_read(fs_read_text('request.json'));
$optimization = $request->member('optimization')->integer();
$policy_level = $request->member('policy_level')->integer();
$policy = new cpp_lowering_policy($policy_level);
$marks = fs_output_root($request->member('marks')->text());
fs_output_write($marks, '01-start', 'start');
$publication = new Output_Publication($request->member('published')->text());
try {
$modules /** vector<string> */ = ['core', 'strings', 'datetime', 'filesystem', 'json'];
Compiler_Lifecycle::configure_runtime(Runtime_Catalog_Loader::load($request->member('catalogs')->text(), $modules));
$compiler = new Compiler();
$compiler->jobs = 12;
$paths /** vector<string> */ = [$request->member('source')->text()];
$compiler->init($paths);
$compiler->select_entry('main', 'main.phs');
fs_output_write($marks, '02-setup', 'setup');
$changed /** vector<string> */ = [];
$compiler->sync($changed);
fs_output_write($marks, '03-sync', 'sync');
$compiler->prepare(true);
fs_output_write($marks, '04-prepare', 'prepare');
 $compiler->cpp($publication, $policy);
} finally { $publication->close(); }
fs_output_write($marks, '05-cpp-ready', 'cpp-ready');
if ($request->member('report')->boolean()) {
 fs_output_write($marks, 'lowering-report.json', CPP_Lowering_Report::encode(Model::$cpp_files, Model::$cpp_output_program, $policy));
 fs_output_write($marks, 'lowering-explain.json', CPP_Lowering_Report::encode(Model::$cpp_files, Model::$cpp_output_program, $policy, true));
}
fs_output_write($marks, 'artifacts.json', CPP_Output::encode(Model::$cpp_files));
fs_output_write($marks, '06-report-done', 'report-done');
echo "PASS\n";
