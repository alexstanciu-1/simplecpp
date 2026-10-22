<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';
$directory=$argv[1];mkdir($directory);$path=$directory.'/main.phs';
$valid='try { throw Exception::make("ok"); } catch (Exception $e) { echo $e->get_message(); }';
file_put_contents($path,$valid);
$compiler=new Compiler();$compiler->init([$directory]);$compiler->select_entry('main','main.phs');$compiler->exec_cpp();
$initial=CPP_Probe::text(Model::$cpp_files);
foreach(['throw 3;','try {} catch (int $e) {}','class Exception {} throw new Exception();','try {} catch (Exception $e) {} echo $e;'] as $bad){
 file_put_contents($path,$bad);$rejected=false;
 try{$compiler->update_cpp([$path]);}catch(\RuntimeException $e){$rejected=true;}
 if(!$rejected || !Model::$cpp_files->is_empty()){throw new \LogicException('Invalid exception edit accepted');}
 file_put_contents($path,$valid);$compiler->update_cpp([$path]);
 if(CPP_Probe::text(Model::$cpp_files)!==$initial){throw new \LogicException('Exception repair differs');}
 $compiler->update_cpp([]);
 if(CPP_Probe::text(Model::$cpp_files)!==$initial){throw new \LogicException('Exception replay differs');}
}
foreach(['try { try { throw Exception::make("nested"); } catch(Exception $e) { throw $e; } } catch(Exception $e) { echo $e->get_message(); }','echo 1;',$valid] as $edit){
 file_put_contents($path,$edit);$compiler->update_cpp([$path]);$incremental=CPP_Probe::text(Model::$cpp_files);
 Compiler_Lifecycle::reset();$fresh=new Compiler();$fresh->init([$directory]);$fresh->select_entry('main','main.phs');$fresh->exec_cpp();
 if(CPP_Probe::text(Model::$cpp_files)!==$incremental){throw new \LogicException('Exception edit differs from fresh');}
 $compiler=$fresh;
}
echo "Exception admission, rejection, repair, removal and fresh parity passed\n";
