<?php
namespace scpp\compiler;
$runtime_modules /** vector<string> */ = ['core', 'json', 'strings', 'datetime', 'filesystem', 'compiler', 'tasks', 'process'];
Compiler_Lifecycle::configure_runtime(Runtime_Catalog_Loader::load(fs_read_text('runtime.txt'), $runtime_modules));
$timing = fs_output_root(fs_read_text('stages.txt'));
fs_output_write($timing, 'init-start', '');
$directory = fs_read_text('request.txt');
$paths /** vector<string> */ = [$directory];
$compiler = new Compiler(new cpp_lowering_options(!fs_is_file('ordinary.txt')));
$compiler->jobs = (int)fs_read_text('jobs.txt');
$compiler->init($paths);
$compiler->select_entry('main', 'main.phs');
$publication = new Output_Publication(fs_read_text('output.txt'));
try {
 fs_output_write($timing, 'sync-start', '');
 $compiler->sync(/** vector<string> */ []);
 fs_output_write($timing, 'prepare-start', '');
 $compiler->prepare(true);
 fs_output_write($timing, 'emit-start', '');
 $compiler->cpp($publication);
 fs_output_write($timing, 'done', '');
} catch (\Throwable $error) { echo $error->getMessage(), "\n"; throw $error; } finally { $publication->close(); }
echo 'OK';
