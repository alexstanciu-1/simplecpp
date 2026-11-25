<?php
namespace scpp\compiler;

final class Split_Contract_Proof
{
	public static function run(): void
	{
		$invalid_0 = new runtime_cpp_build_spec();
		$values_0 /** vector<string> */ = ['NAME'];
		$invalid_0->definitions = $values_0;
		self::reject($invalid_0, 'Runtime build definition requires NAME=VALUE');
		$invalid_1 = new runtime_cpp_build_spec();
		$values_1 /** vector<string> */ = ['NAME='];
		$invalid_1->definitions = $values_1;
		self::reject($invalid_1, 'Runtime build definition requires NAME=VALUE');
		$invalid_2 = new runtime_cpp_build_spec();
		$values_2 /** vector<string> */ = ['=1'];
		$invalid_2->definitions = $values_2;
		self::reject($invalid_2, 'Runtime build definition requires NAME=VALUE');
		$invalid_3 = new runtime_cpp_build_spec();
		$values_3 /** vector<string> */ = ['NAME=1=2'];
		$invalid_3->definitions = $values_3;
		self::reject($invalid_3, 'Runtime build definition requires NAME=VALUE');
		$invalid_4 = new runtime_cpp_build_spec();
		$values_4 /** vector<string> */ = ['='];
		$invalid_4->definitions = $values_4;
		self::reject($invalid_4, 'Runtime build definition requires NAME=VALUE');
		$invalid_5 = new runtime_cpp_build_spec();
		$values_5 /** vector<string> */ = ['=='];
		$invalid_5->definitions = $values_5;
		self::reject($invalid_5, 'Runtime build definition requires NAME=VALUE');
		$invalid_6 = new runtime_cpp_build_spec();
		$values_6 /** vector<string> */ = ['NAME=='];
		$invalid_6->definitions = $values_6;
		self::reject($invalid_6, 'Runtime build definition requires NAME=VALUE');
		$invalid_7 = new runtime_cpp_build_spec();
		$values_7 /** vector<string> */ = ['/file.cpp'];
		$invalid_7->sources = $values_7;
		self::reject($invalid_7, 'Invalid relative runtime provider path');
		$invalid_8 = new runtime_cpp_build_spec();
		$values_8 /** vector<string> */ = ['folder//file.cpp'];
		$invalid_8->sources = $values_8;
		self::reject($invalid_8, 'Invalid relative runtime provider path');
		$invalid_9 = new runtime_cpp_build_spec();
		$values_9 /** vector<string> */ = ['./file.cpp'];
		$invalid_9->sources = $values_9;
		self::reject($invalid_9, 'Invalid relative runtime provider path');
		$invalid_10 = new runtime_cpp_build_spec();
		$values_10 /** vector<string> */ = ['folder/../file.cpp'];
		$invalid_10->sources = $values_10;
		self::reject($invalid_10, 'Invalid relative runtime provider path');
		$invalid_11 = new runtime_cpp_build_spec();
		$values_11 /** vector<string> */ = ['folder/'];
		$invalid_11->sources = $values_11;
		self::reject($invalid_11, 'Runtime provider source requires .cpp');
		$invalid_12 = new runtime_cpp_build_spec();
		$values_12 /** vector<string> */ = ['SCPP_LANGUAGE_TARGET_PHP=0'];
		$invalid_12->definitions = $values_12;
		self::reject($invalid_12, 'Runtime module cannot override the language target');
		$invalid_13 = new runtime_cpp_build_spec();
		$values_13 /** vector<string> */ = ['NAME=1', 'NAME=2'];
		$invalid_13->definitions = $values_13;
		self::reject($invalid_13, 'Conflicting runtime build definition: NAME');
		$invalid_14 = new runtime_cpp_build_spec();
		$values_14 /** vector<string> */ = ['NAME=+1'];
		$invalid_14->definitions = $values_14;
		self::reject($invalid_14, 'Unsupported runtime build definition value');
		$valid = new runtime_cpp_build_spec();
		$values_15 /** vector<string> */ = ['nested/provider.cpp', 'second.cpp'];
		$valid->sources = $values_15;
		$values_16 /** vector<string> */ = ['m', 'stdc++'];
		$valid->libraries = $values_16;
		$values_17 /** vector<string> */ = ['NAME=-1', 'TAG=a_b.2', 'NAME=-1'];
		$valid->definitions = $values_17;
		if (Runtime_Build_Contract::encode($valid) !== '{"sources":["nested\/provider.cpp","second.cpp"],"libraries":["m","stdc++"],"definitions":["NAME=-1","TAG=a_b.2","NAME=-1"]}') { throw new \LogicException('Valid encoding changed'); }
		$input = new runtime_catalog_input();
		$modules /** Keyed_Storage<runtime_module_spec> */ = $input->modules;
		$modules->add('dependency', Runtime_Catalog_Reader::decode('{"version": 1, "module": "dependency", "dependencies": [], "functions": [], "cpp": [], "cpp_build": {"sources": ["provider.cpp"], "libraries": ["dependency"], "definitions": ["MODE=1"]}}', 'dependency.json'));
		$modules->add('consumer', Runtime_Catalog_Reader::decode('{"version": 1, "module": "consumer", "dependencies": [], "functions": [], "cpp": [], "cpp_build": {"sources": ["provider.cpp", "consumer.cpp"], "libraries": ["consumer"], "definitions": ["MODE=1"]}}', 'consumer.json'));
		if (Runtime_Build_Contract::encode(CPP_Runtime_Build::requirements($input)) !== '{"sources":["provider.cpp","consumer.cpp"],"libraries":["consumer","dependency"],"definitions":["MODE=1"]}') { throw new \LogicException('Merge order or deduplication changed'); }
		$modules->add('conflict', Runtime_Catalog_Reader::decode('{"version": 1, "module": "conflict", "dependencies": [], "functions": [], "cpp": [], "cpp_build": {"definitions": ["MODE=2"]}}', 'conflict.json'));
		$conflict = false;
		try { CPP_Runtime_Build::requirements($input); }
		catch (\RuntimeException $error) {
			if ($error->getMessage() !== 'Conflicting runtime build definition: MODE') { throw new \LogicException('Wrong merge diagnostic'); }
			$conflict = true;
		}
		if (!$conflict) { throw new \LogicException('Missing merge conflict'); }
	}
	private static function reject(runtime_cpp_build_spec $spec, string $message): void
	{
		$failed = false;
		try { Runtime_Build_Contract::validate($spec); }
		catch (\RuntimeException $error) {
			if ($error->getMessage() !== $message) { throw new \LogicException('Wrong build diagnostic: ' . $error->getMessage()); }
			$failed = true;
		}
		if (!$failed) { throw new \LogicException('Invalid build accepted'); }
	}
}
Split_Contract_Proof::run();
