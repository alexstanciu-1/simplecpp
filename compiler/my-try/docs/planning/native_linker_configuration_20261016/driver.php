<?php
namespace scpp\compiler;
$request = json_read(fs_read_text('request.json'));
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
 $compiler->cpp($publication);
} finally { $publication->close(); }
fs_output_write($marks, '05-cpp-ready', 'cpp-ready');
$config = new native_ninja_config($request->member('clang')->text(), $request->member('runtime')->text(), $request->member('build')->text());
$config->linker = $request->member('linker')->text();
$config->linker_threads = $request->member('linker_threads')->integer();
$config->reuse = $request->member('reuse')->boolean();
$config->jobs = 8;
if ($request->member('parallel12')->boolean()) { $config->jobs = 12; }
$result = (new Native_Ninja_Build($config))->build($request->member('published')->text());
fs_output_write($marks, '06-build-done', 'build-done');
echo $result->output;
echo "EXECUTABLE=", $result->executable, "\n";
