<?php
namespace scpp\compiler;
require_once __DIR__.'/operators.php';
$type=$argv[2];$index=(int)$argv[3];
$source='function probe('.$type.' $a,'.$type.' $b):'.$type.'{$v vector<'.$type.'> = [$a];$v[0] .= $b;return $v[0];}';
operator_test_source($source);
$cpp=str_replace('int main(int argc, char** argv)','int unused_entry(int argc, char** argv)',CPP_Probe::text(Model::$cpp_files));
$cpp.="\n#include \"".__DIR__."/vl07_completion_support.hpp\"\nusing namespace completion_probe;\n";
$cpp.='constexpr int F='.$index.';int main(){using S=scpp::string_t;long cases=0;for(auto a:states<F,std::string>())for(auto b:states<F,std::string>()){auto av=make<F,S>(a),bv=make<F,S>(b);auto expected=calculation<mode::append>(a,b);int error=extraction<F,mode::append>(a);if(!error)error=extraction<F,mode::append>(b);int failure=0;try{auto out=function_probe(av,bv);if(error||!result_matches<true>(out,expected.value))return 10;}catch(const std::exception&e){failure=classify(e);}if(failure!=error||!same(av,a)||!same(bv,b))return 11;++cases;}std::cout<<"cases="<<cases<<"\\n";}';
file_put_contents($argv[1].'/indexed.cpp',$cpp);
