<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
// Test-only allocator observer; production lowering and provider transport are unchanged.
$out = $argv[1];
$mode = $argv[2];
$source = $out . '/source';
mkdir($source, 0777, true);
$header = $out . '/allocations.hpp';
file_put_contents($header, "#pragma once\n#include \"scpp/int_t.hpp\"\nnamespace snapshot_probe { void begin(); scpp::int_t<> end(); }\n");
$schema = ['version'=>1,'module'=>'allocation_probe','dependencies'=>['core'],
	'functions'=>[['id'=>'probe.begin','name'=>'probe_begin','parameters'=>[],'returns'=>'void'],
		['id'=>'probe.end','name'=>'probe_end','parameters'=>[],'returns'=>'int']],
	'cpp'=>[['id'=>'probe.begin','symbol'=>'snapshot_probe::begin','header'=>$header],
		['id'=>'probe.end','symbol'=>'snapshot_probe::end','header'=>$header]]];
$input = new runtime_catalog_input();
foreach (Model::$runtime_metadata->modules as $module) { $input->modules->add($module->name, $module); }
$input->modules->add('allocation_probe', Runtime_Catalog_Reader::decode(json_encode($schema), 'allocation-probe.json'));
Compiler_Lifecycle::configure_runtime($input);
$program = 'function other():string{return "a";} $s string="' . str_repeat('a', 65536) . '";'
	. 'probe_begin();$v=substr_compare($s,$s,0);$n=strlen($s);$alloc=probe_end();echo $v,":",$n,":",$alloc,"\\n";'
	. 'probe_begin();$v=substr_compare($s,other(),0);$alloc=probe_end();echo ($v>0),":",($alloc>0),"\\n";';
file_put_contents($source . '/main.phs', $program);
file_put_contents($source . '/prism.json', '{"runtime":{"languages":{"php":{"profile":"strict"}}}}');
$compiler = new Compiler(new cpp_lowering_options($mode === 'concrete'));
$compiler->init([$source]);
$compiler->exec_cpp();
$support = '#include "' . $header . '"' . "\n" . <<<'CPP'
#include <cstdlib>
#include <new>
namespace { bool counting=false; std::size_t allocations=0; }
void* operator new(std::size_t size) {
 if (counting) ++allocations;
 if (void* memory=std::malloc(size ? size : 1)) return memory;
 throw std::bad_alloc();
}
void* operator new[](std::size_t size) { return ::operator new(size); }
void operator delete(void* memory) noexcept { std::free(memory); }
void operator delete[](void* memory) noexcept { std::free(memory); }
void operator delete(void* memory, std::size_t) noexcept { std::free(memory); }
void operator delete[](void* memory, std::size_t) noexcept { std::free(memory); }
namespace snapshot_probe {
void begin() { allocations=0; counting=true; }
scpp::int_t<> end() { counting=false; return scpp::int_t<>(static_cast<std::int64_t>(allocations)); }
}
CPP;
Model::$cpp_files->append(new cpp_artifact('test:allocations', cpp_artifact_kind::translation_unit, 'allocations.cpp', $support));
$publication = new Output_Publication($out . '/published');
try { $publication->publish(Model::$cpp_files, new runtime_cpp_build_spec()); }
finally { $publication->close(); }
