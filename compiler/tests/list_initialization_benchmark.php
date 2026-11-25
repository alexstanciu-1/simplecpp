<?php

declare(strict_types=1);
require $argv[1];
use scpp\compiler\{Source_Frontend, source_file, flavor, entry_target, Cpp_Emitter};

$rows = [];
foreach ([flavor::cpp, flavor::php, flavor::js] as $flavor) {
	foreach (['int3' => ['int', 3], 'int512' => ['int', 512], 'heap16' => ['string', 16]] as $case => [$element, $count]) {
		foreach (['bulk', 'append'] as $mode) {
			$source = '';
			for ($site = 0; $site < 12; ++$site) {
				$values = [];
				for ($i = 0; $i < $count; ++$i) $values[] = $element === 'int' ? (string)($i + $site) : '"heap-backed literal input number ' . $i . ' site ' . $site . '\\0end"';
				$name = $flavor === flavor::php ? '$v' : 'v';
				$literal = $mode === 'bulk' ? '[' . implode(',', $values) . ']' : '[]';
				$body = match ($flavor) {
					flavor::cpp => "vector<$element> v = $literal;", flavor::php => "$name vector<$element> = $literal;",
					flavor::js => "let v: vector<$element> = $literal;",
				};
				if ($mode === 'append') foreach ($values as $value) $body .= $name . '[] = ' . $value . ';';
				$body .= "return $name;";
				$signature = $flavor === flavor::cpp ? "vector<$element> build$site()" : "function build$site(): vector<$element>";
				$source .= $signature . '{' . $body . '}';
			}
			$source .= 'return 8;';
			$start = hrtime(true);
			$entry = (new Source_Frontend())->prepare(new source_file('bench', $source, $flavor), new entry_target(31));
			$prepared = hrtime(true);
			$cpp = (new Cpp_Emitter())->emit($entry);
			$end = hrtime(true);
			$rows[$flavor->name . '-' . $case . '-' . $mode] = ['cpp' => $cpp, 'prepare_ms' => ($prepared - $start) / 1e6,
				'emit_ms' => ($end - $prepared) / 1e6, 'flavor' => $flavor->name, 'case' => $case, 'element' => $element,
				'count' => $count, 'source_bytes' => strlen($source)];
		}
	}
}
file_put_contents($argv[2], json_encode($rows, JSON_THROW_ON_ERROR));
