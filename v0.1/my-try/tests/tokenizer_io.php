<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

function tokenio_check(bool $ok, string $message): void
{
	if (!$ok) { throw new \LogicException($message); }
}
function tokenio_compile(string $source): string
{
	Compiler_Lifecycle::reset();
	$module = new module('/tokenizer_io', '/tokenizer_io', '/tokenizer_io');
	$file = new file();
	$file->path = 'main.phs';
	$file->content = $source;
	Source_Registry::add($module, $file);
	Model::$modules->add($module->name, $module);
	(new Compiler(new cpp_lowering_options(false)))->exec_cpp();
	return CPP_Probe::text(Model::$cpp_files);
}
function tokenio_recovery(string $valid, string $invalid, bool $prelower = false): void
{
	$directory = sys_get_temp_dir().'/tokenio_recovery_'.bin2hex(random_bytes(6));
	mkdir($directory);
	$path = $directory.'/main.phs';
	try {
		file_put_contents($path,$valid);
		Compiler_Lifecycle::reset();
		$compiler = new Compiler(new cpp_lowering_options($prelower));
		$compiler->init([$directory]);
		$compiler->exec_cpp();
		$fresh = CPP_Probe::text(Model::$cpp_files);
		file_put_contents($path,$invalid);
		foreach ([[$path],[]] as $changes) {
			$failed = false;
			try { $compiler->update_cpp($changes); }
			catch (\RuntimeException $exception) { $failed = true; }
			tokenio_check($failed && Model::$cpp_files->is_empty() && Model::$prepared_files->is_empty(),
				'Invalid provider edit published stale output');
		}
		file_put_contents($path,$valid);
		$compiler->update_cpp([$path]);
		tokenio_check(CPP_Probe::text(Model::$cpp_files)===$fresh,'Provider repair differs from fresh');
		$compiler->update_cpp([]);
		tokenio_check(CPP_Probe::text(Model::$cpp_files)===$fresh,'Provider no-op differs');
	} finally { unlink($path); rmdir($directory); }
}
$out = $argv[1];
$root = dirname(__DIR__, 3);
$baseline = ($argv[2] ?? '') === '--baseline';
$tokenizer_only = ($argv[2] ?? '') === '--tokenizer-only';
$catalog_directory = $argv[3] ?? $root.'/runtime/metadata/modules';
$modules = $baseline ? ['filesystem'] : ['tokenizer', 'io', 'filesystem'];
if (isset($argv[3])) { $modules[] = 'transport_probe'; }
Compiler_Lifecycle::configure_runtime(Runtime_Catalog_Loader::load($catalog_directory, $modules));
if (($argv[2] ?? '') === '--publish') {
	$publication = new Output_Publication($out.'/published');
	try {
		$compiler = new Compiler();
		$compiler->init([$out.'/source']);
		$compiler->select_entry('main','main.phs');
		$compiler->exec_cpp($publication);
	} finally { $publication->close(); }
	exit(0);
}
if ($baseline) {
	$failures = [];
	foreach (['$x=phs_tokenize_buffer("a");', '$x=jss_tokenize_buffer("a");',
		'$h resource_handle;', 'io_open("x","rb");', '$h=io_open("x","rb");io_close($h);'] as $source) {
		$error = '';
		try { tokenio_compile($source); }
		catch (\RuntimeException $exception) { $error = $exception->getMessage(); }
		tokenio_check($error !== '', 'Expected absent API');
		$failures[] = ['source'=>$source,'diagnostic'=>$error];
	}
	file_put_contents($out.'/baseline.json', json_encode($failures, JSON_PRETTY_PRINT)."\n");
	exit(0);
}
$executions = [];
foreach ([
	['tokenizer', 'tests/php/tokenizer/strict_level_01/strict_tokenizer_001_typed_buffer_accessors.phs',
		'tests/php/tokenizer/strict_level_01/strict_tokenizer_001_typed_buffer_accessors.test-info.json'],
	['io_surface', 'compiler/my-try/tests/compatibility_variants/s02/strict_io_001_io_surface.phs',
		'tests/php/io/strict_level_01/strict_io_001_io_surface.test-info.json'],
	['io_failure', 'compiler/my-try/tests/compatibility_variants/s02/strict_io_002_io_open_failure.phs',
		'tests/php/io/strict_level_01/strict_io_002_io_open_failure.test-info.json'],
] as [$name,$source_path,$info_path]) {
	if ($tokenizer_only && $name !== 'tokenizer') { continue; }
	$source = file_get_contents($root.'/'.$source_path);
	file_put_contents($out.'/'.$name.'.cpp', tokenio_compile($source));
	$info = json_decode(file_get_contents($root.'/'.$info_path), true, 512, JSON_THROW_ON_ERROR);
	$executions[] = ['name'=>$name,'stdout'=>$info['expect']['run']['stdout'],'source'=>$source_path];
}
$tokenizer = <<<'PHS'
function phs(const string &$s):token_buffer{return phs_tokenize($s);}
function phsbuffer(const string &$s):token_buffer{return phs_tokenize_buffer($s);}
function jss(const string &$s):token_buffer{return jss_tokenize($s);}
function jssbuffer(const string &$s):token_buffer{return jss_tokenize_buffer($s);}
function counttokens(const token_buffer &$b):int{return token_buffer_count($b);}
function kind(const token_buffer &$b,int $n):int{return token_buffer_kind_id($b,$n);}
function start(const token_buffer &$b,int $n):int{return token_buffer_start_offset($b,$n);}
function length(const token_buffer &$b,int $n):int{return token_buffer_length($b,$n);}
function line(const token_buffer &$b,int $n):int{return token_buffer_line($b,$n);}
function column(const token_buffer &$b,int $n):int{return token_buffer_column($b,$n);}
function flags(const token_buffer &$b,int $n):int{return token_buffer_flags($b,$n);}
function readable(const token_buffer &$b):mixed{return token_buffer_to_mixed($b);}
function index(token_buffer &$b,int &$trace):int{$trace++;$b=phs_tokenize("replacement");return 1;}
function snapshot(token_buffer &$b,int &$trace):int{return token_buffer_start_offset($b,index($b,$trace));}
PHS;
$cpp = str_replace('int main(int argc, char** argv)', 'int unused_entry(int argc, char** argv)', tokenio_compile($tokenizer));
$cpp .= <<<'CPP'

#include <iostream>
int main() {
	using S=scpp::string_t;using I=scpp::int_t<>;
	for(auto factory:{function_phs,function_phsbuffer,function_jss,function_jssbuffer}) {
		S input{"foo 12\nbar"};auto b=factory(input);input=S{"changed"};
		if(function_counttokens(b).native_value()!=4)return 1;
		if(function_kind(b,I{1}).native_value()!=3 || function_start(b,I{1}).native_value()!=4
			|| function_length(b,I{1}).native_value()!=2 || function_line(b,I{2}).native_value()!=2
			|| function_column(b,I{2}).native_value()!=1 || function_flags(b,I{2}).native_value()!=3)return 2;
		auto m=function_readable(b);
		if(m.kind()!=scpp::mixed_t::kind_t::table_v)return 3;
		auto* table=m.table_if();
		if(table->at(S{"token_count"}).int_value().native_value()!=4)return 4;
		auto* starts=table->at(S{"start_offsets"}).table_if();
		if(starts->at(I{2}).int_value().native_value()!=7)return 5;
		auto longb=factory(S{std::string(70000,'a')});
		if(function_length(longb,I{0}).native_value()!=70000||function_flags(longb,I{0}).native_value()!=4)return 6;
		for(auto accessor:{function_kind,function_start,function_length,function_line,function_column,function_flags}) {
			for(int i:{-1,4}) {bool caught=false;try{accessor(b,I{i});}catch(const std::out_of_range&){caught=true;}if(!caught)return 7;}
		}
		I trace{0};if(function_snapshot(b,trace).native_value()!=4||trace.native_value()!=1||function_counttokens(b).native_value()!=2)return 8;
	}
	std::cout<<"typed tokens; owned mixed adapter; lifetimes; errors; snapshots\n";
}
CPP;
file_put_contents($out.'/tokenizer_controls.cpp',$cpp);
$executions[] = ['name'=>'tokenizer_controls','stdout'=>"typed tokens; owned mixed adapter; lifetimes; errors; snapshots\n"];
$tokenizer_rejections = [];
foreach (['phs_tokenize(1);','jss_tokenize_buffer();','token_buffer_count(1);',
	'token_buffer_kind_id(phs_tokenize("x"),"0");',
	'$b=new token_buffer();','$b=phs_tokenize("x");$b->source_length=1;'] as $bad) {
	$error = '';
	try { tokenio_compile($bad); }
	catch (\RuntimeException $exception) { $error = $exception->getMessage(); }
	tokenio_check($error!=='' && Model::$cpp_files->is_empty(),'Invalid tokenizer source admitted');
	$tokenizer_rejections[] = ['source'=>$bad,'diagnostic'=>$error];
}
tokenio_recovery('$b=phs_tokenize("foo");echo token_buffer_count($b);',
	'$b=phs_tokenize(1);echo token_buffer_count($b);');
if ($tokenizer_only) {
	file_put_contents($out.'/checks.json',json_encode(['executions'=>$executions,
		'rejections'=>$tokenizer_rejections,'recovery_cycles'=>1],JSON_PRETTY_PRINT)."\n");
	exit(0);
}
$io = <<<'PHS'
function openfile(const string &$path,const string &$mode):result_or_false<resource_handle>{return io_open($path,$mode);}
function tellfile(const resource_handle &$h):result_or_false<int>{return io_tell($h);}
function closefile(const resource_handle &$h):bool{return io_close($h);}
function linefile(const resource_handle &$h):result_or_false<string>{return io_read_line($h);}
function boundedline(const resource_handle &$h,int $n):result_or_false<string>{return io_read_line($h,$n);}
function seekfile(const resource_handle &$h,int $n):?int{return io_seek($h,$n);}
function seekfrom(const resource_handle &$h,int $n,int $origin):?int{return io_seek($h,$n,$origin);}
function data(resource_handle &$h,string $replacement,int &$trace):string{$trace++;$h=io_open($replacement,"wb+");return "old";}
function snapshot(resource_handle &$h,string $replacement,int &$trace):result_or_false<int>{return io_write($h,data($h,$replacement,$trace));}
function body(string $path):void {
	$h resource_handle=io_open($path,"wb+");
	$n int=io_write($h,"a\0b\nlast");
	if($n!==8){throw Exception::make("write");}
	if(!io_flush($h)||!io_rewind($h)){throw Exception::make("flush");}
	$s string=io_read($h,4);
	if($s!=="a\0b\n"){throw Exception::make("read");}
	$p int=io_tell($h);
	if($p!==4){throw Exception::make("tell");}
	$empty string=io_read($h,0);
	if($empty!==""){throw Exception::make("zero");}
	$end=io_seek($h,0,2);
	$s=io_read($h,1);
	if($s!==""||!io_eof($h)){throw Exception::make("eof");}
}
function unwind(string $path):void{$h resource_handle=io_open($path,"rb");throw Exception::make("unwind");}
PHS;
$cpp = str_replace('int main(int argc, char** argv)', 'int unused_entry(int argc, char** argv)', tokenio_compile($io));
$cpp .= <<<'CPP'

#include "core/stdio.hpp"
#include <filesystem>
#include <fcntl.h>
#include <iostream>
int main() {
	using S=scpp::string_t;using I=scpp::int_t<>;
	function_body(S{"bytes.bin"});
	auto h=function_openfile(S{"bytes.bin"},S{"rb"}).value();auto alias=h;
	if(function_linefile(h).value().native_value()!=std::string("a\0b\n",4))return 1;
	if(function_tellfile(alias).value().native_value()!=4)return 2;
	if(function_seekfile(h,I{0}).value().native_value()!=0)return 3;
	if(function_boundedline(h,I{4}).value().native_value()!=std::string("a\0b",3))return 4;
	if(function_seekfile(h,I{-1}).has_value().native_value())return 5;
	if(!function_seekfrom(h,I{0},I{SEEK_END}).has_value().native_value())return 6;
	if(!function_linefile(h).is_false().native_value())return 7;
	if(!function_closefile(alias).native_value())return 8;
	bool caught=false;try{function_tellfile(h);}catch(const std::exception&){caught=true;}if(!caught)return 9;
	h=function_openfile(S{"old.bin"},S{"wb+"}).value();alias=h;I trace{0};
	if(function_snapshot(h,S{"new.bin"},trace).value().native_value()!=3||trace.native_value()!=1)return 10;
	if(function_tellfile(alias).value().native_value()!=3||function_tellfile(h).value().native_value()!=0)return 11;
	function_closefile(alias);function_closefile(h);
	auto descriptors=[](){return std::distance(std::filesystem::directory_iterator("/proc/self/fd"),std::filesystem::directory_iterator{});};
	auto before=descriptors();
	for(int i=0;i<12;++i){try{function_unwind(S{"bytes.bin"});}catch(...){}}
	if(descriptors()!=before)return 12;
	for(auto p:{"bytes.bin","old.bin","new.bin"})std::filesystem::remove(p);
	std::cout<<"IO bytes; shared state; failure carriers; snapshots; unwind\n";
}
CPP;
file_put_contents($out.'/io_controls.cpp',$cpp);
$executions[] = ['name'=>'io_controls','stdout'=>"IO bytes; shared state; failure carriers; snapshots; unwind\n"];
$rejections=$tokenizer_rejections;
foreach (['token_buffer_count(1);','token_buffer_kind_id(phs_tokenize("x"),"0");',
	'io_read("wrong",1);','io_open("x",1);','io_seek(io_open("x","rb"),1.0);',
	'$b token_buffer=io_open("x","rb");','io_close(phs_tokenize("x"));',
	'$h resource_handle=new resource_handle();'] as $bad) {
	$error='';
	try{tokenio_compile($bad);}catch(\RuntimeException $exception){$error=$exception->getMessage();}
	tokenio_check($error!=='' && Model::$cpp_files->is_empty(),'Invalid provider call admitted');
	$rejections[]=['source'=>$bad,'diagnostic'=>$error];
}
tokenio_recovery('$h resource_handle;io_tell($h);','$h token_buffer;io_tell($h);');
tokenio_recovery('$r ?int=7;$v=0;take($v,$r);echo $v;',
	'$r ?int=7;$v=0.0;take($v,$r);echo $v;', true);
file_put_contents($out.'/checks.json',json_encode(['executions'=>$executions,
	'rejections'=>$rejections,'recovery_cycles'=>3],JSON_PRETTY_PRINT)."\n");
