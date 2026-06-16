<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

use Scpp\S2S\Jss\JssTranspiler;
use Scpp\S2S\Jss\JssParser;
use Scpp\S2S\Jss\JssSummaryExtractor;
use Scpp\S2S\Jss\JssTokenizer;
use Scpp\S2S\Stan\StanFrontendClassifier;
use Scpp\S2S\Stan\StanSemanticPass;
use Scpp\S2S\Stan\StanSymbolIndexBuilder;

final class ScppJssFrontendFirstSliceTest
{
	private string $root;

	public function run(): void
	{
		$this->root = normalize_path(sys_get_temp_dir() . '/scpp_jss_frontend_' . getmypid() . '_' . bin2hex(random_bytes(4)));
		$this->mkdir($this->root);
		try {
			$this->testTinyJssTranspilesToPhs();
			$this->testJssAsyncAwaitTranspilesToPhsAsyncSurface();
			$this->testJssCommentsAreIgnoredByFrontend();
			$this->testJssSummaryProvidesStanInterfaceFacts();
			$this->testStanClassifiesJssFrontendRequests();
			$this->testJssEmitterConsumesStanClassifications();
			$this->testStanClassifiesJssClassConstants();
			$this->testJssClassifiedEmissionSupportsReservedHelperFamilies();
			$this->testJssTakeSupportsResultWrapperHelperFlow();
			$this->testStanClassifiesJssTakeContracts();
			$this->testJssClassifiedEmissionUsesStanTakeDiagnostics();
			$this->testStanClassifiesJssHelperModuleAvailability();
			$this->testJssEmitterConsumesStanIdentifierClassifications();
			$this->testJssEmitterConsumesStanBinaryPlusClassifications();
			$this->testJssSummaryCarriesParameterTypesForBinaryPlus();
			$this->testJssSummaryCarriesForeachValueTypesForBinaryPlus();
			$this->testJssSummaryCarriesForInitTypesForBinaryPlus();
			$this->testJssSummaryInfersSimpleLocalTypesForBinaryPlus();
			$this->testJssClassifiedEmissionUsesDynamicPlusBeforeStringConcat();
			$this->testJssClassifiedEmissionConsumesFunctionCalleeClassifications();
			$this->testJssClassifiedEmissionRejectsUnresolvedFunctionCall();
			$this->testJssEmitterRejectsNonFunctionIdentifierCallWhenRequired();
			$this->testJssClassifiedEmissionClassifiesSameFileFunctionBinaryPlus();
			$this->testJssClassifiedEmissionRejectsKnownInvalidBinaryPlus();
			$this->testJssClassifiedEmissionRejectsInvalidStaticMethod();
			$this->testJssClassifiedEmissionRejectsInvalidClassMemberRead();
			$this->testJssEmitterRejectsMissingMemberClassificationWhenRequired();
			$this->testJssClassifiedEmissionKeepsLocalIdentifiersLocal();
			$this->testJssClassifiedEmissionRejectsUnresolvedIdentifier();
			$this->testJssEmitterRejectsMissingIdentifierClassificationWhenRequired();
			$this->testJssBuildSourceFeedsStanSummaryPath();
			$this->testJssParserAllowsLooseEqualityTemporarily();
			$this->testJssParserRejectsDynamicThisAndPrototype();
			$this->testJssParserRejectsJavaScriptModuleSyntax();
			$this->testJssNamespaceBlockTranspilesToExistingNamespaceSemantics();
			$this->testJssParserBlocksReservedHelperRootsFromUserNamespaces();
			$this->testJssParserSupportsPublicClassMembersOnly();
			$this->testJssParserRequiresFunctionAndMethodReturnTypes();
			$this->testJssParserEnforcesConstructorShape();
			$this->testJssParserRejectsLocalConstUntilSemanticsExist();
			$this->testJssNullCoalesceTranspilesForStrictFirstSlice();
			$this->testJssParserRejectsNullCoalesceChains();
			$this->testJssTernaryTranspilesForStrictFirstSlice();
			$this->testJssParserRejectsTernaryChains();
			$this->testJssParserRejectsMoreUnsupportedSyntaxWithLocations();
			$this->testJssTemplateLiteralSupportsMemberInterpolation();
			$this->testJssTemplateLiteralSupportsStaticMemberInterpolation();
			$this->testJssSemanticValidatorAcceptsBoolConditions();
			$this->testJssSemanticValidatorRejectsTruthyConditions();
			$this->testJssSemanticValidatorRejectsTruthyLogicalOperands();
			$this->testJssSemanticValidatorSupportsUnaryMinus();
			$this->testJssSemanticValidatorValidatesForeachSources();
			$this->testJssSemanticValidatorRejectsUnsafeTypeDefaults();
			echo "PASS: scpp jss frontend first slice\n";
		} finally {
			$this->removeTree($this->root);
		}
	}

	private function testTinyJssTranspilesToPhs(): void
	{
		$source = implode("\n", [
			'let name: string = "Ada";',
			'let suffix: string = " Lovelace";',
			'print(name + suffix);',
			'',
		]);
		$phs = (new JssTranspiler())->transpileToPhs($source);
		$this->assertSame(
			implode("\n", [
				'$name string = "Ada";',
				'$suffix string = " Lovelace";',
				'echo $name . $suffix;',
				'',
			]),
			$phs,
			'tiny JSS should emit predictable strict PHS'
		);
	}

	private function testJssCommentsAreIgnoredByFrontend(): void
	{
		$source = implode("\n", [
			'// leading line comment',
			'let name: string = "Ada"; /* inline block comment */',
			'/* multi-line',
			'   block comment */',
			'print(name, "\n"); // trailing line comment',
			'',
		]);
		$phs = (new JssTranspiler())->transpileToPhsWithStanClassifications($source, 'main.jss');
		$this->assertSame(
			implode("\n", [
				'$name string = "Ada";',
				'echo $name, "\n";',
				'',
			]),
			$phs,
			'JSS frontend should ignore line and block comments while preserving the emitted program'
		);
	}

	private function testJssAsyncAwaitTranspilesToPhsAsyncSurface(): void
	{
		$source = implode("\n", [
			'async function computeValue(): int {',
			'    await async_sleep_ms(1);',
			'    return 42;',
			'}',
			'',
			'let value: int = await computeValue();',
			'print(value);',
			'',
		]);
		$phs = (new JssTranspiler())->transpileToPhs($source);
		$this->assertSame(
			implode("\n", [
				'/** @async */',
				'function computeValue(): int {',
				"\t" . 'async_sleep_ms(1);',
				"\t" . 'return 42;',
				'}',
				'$value int = async_wait(computeValue());',
				'echo $value;',
				'',
			]),
			$phs,
			'JSS async/await should emit the PHS async core surface'
		);
	}

	private function testJssSummaryProvidesStanInterfaceFacts(): void
	{
		$source = implode("\n", [
			'class Box {',
			'    static count: int = 3;',
			'    static value(): int {',
			'        return Box.count;',
			'    }',
			'}',
			'',
			'const BASE = 20;',
			'let label: string = "n=";',
			'print(label + Box.value());',
			'',
		]);
		$program = (new JssParser())->parse((new JssTokenizer())->tokenize($source));
		$summary = (new JssSummaryExtractor())->summarize($program, 'main.jss');

		$this->assertSame('jss', $summary['frontend']['language'] ?? null, 'JSS summary should carry frontend language metadata');
		$this->assertSame(3, $summary['frontend']['summary_version'] ?? null, 'JSS summary should use the structured v3 summary builder');
		$this->assertSame('Box', $summary['root_classes'][0]['name'] ?? null, 'JSS summary should expose root class declarations');
		$this->assertSame(1, $summary['root_classes'][0]['line'] ?? null, 'JSS summary should carry class declaration source line');
		$this->assertSame('count', $summary['root_classes'][0]['properties'][0]['name'] ?? null, 'JSS summary should expose class properties');
		$this->assertSame(2, $summary['root_classes'][0]['properties'][0]['line'] ?? null, 'JSS summary should carry property source line');
		$this->assertSame(true, $summary['root_classes'][0]['properties'][0]['is_static'] ?? null, 'JSS summary should expose static property facts');
		$this->assertSame('BASE', $summary['root_constants'][0]['name'] ?? null, 'JSS summary should expose root constants');
		$this->assertSame(8, $summary['root_constants'][0]['line'] ?? null, 'JSS summary should carry constant source line');

		$kinds = array_map(
			static fn (array $request): string => (string) ($request['kind'] ?? ''),
			$summary['frontend_classification_requests'] ?? []
		);
		$this->assertSame(true, in_array('member_access', $kinds, true), 'JSS summary should request member access classification');
		$this->assertSame(true, in_array('binary_plus', $kinds, true), 'JSS summary should request binary plus classification');
		$rangedRequests = array_values(array_filter(
			$summary['frontend_classification_requests'] ?? [],
			static fn (array $request): bool => isset($request['path'], $request['range']['line'], $request['range']['column'])
		));
		$this->assertSame(true, $rangedRequests !== [], 'JSS classification requests should carry source path/range metadata');
	}

	private function testStanClassifiesJssFrontendRequests(): void
	{
		$source = implode("\n", [
			'class Box {',
			'    static count: int = 3;',
			'    static value(): int {',
			'        return 7;',
			'    }',
			'}',
			'',
			'print(Box.count, Box.value());',
			'',
		]);
		$program = (new JssParser())->parse((new JssTokenizer())->tokenize($source));
		$summary = (new JssSummaryExtractor())->summarize($program, 'main.jss');
		$fileSummaries = ['main.jss' => $summary];
		$symbolIndex = (new StanSymbolIndexBuilder())->build($fileSummaries);
		$classifications = (new StanFrontendClassifier())->classify($fileSummaries, $symbolIndex);
		$kinds = array_map(
			static fn (array $classification): string => (string) ($classification['kind'] ?? ''),
			array_values($classifications)
		);

		$this->assertSame(true, in_array('static_property', $kinds, true), 'STAN should classify known JSS static property access');
		$this->assertSame(true, in_array('static_method', $kinds, true), 'STAN should classify known JSS static method access');

		$semanticResult = (new StanSemanticPass())->analyze($fileSummaries, $this->root);
		$semanticKinds = array_map(
			static fn (array $classification): string => (string) ($classification['kind'] ?? ''),
			array_values($semanticResult['frontend_classifications'] ?? [])
		);
		$this->assertSame(true, in_array('static_property', $semanticKinds, true), 'STAN semantic pass should expose frontend static property classifications');

		$unresolvedProgram = (new JssParser())->parse((new JssTokenizer())->tokenize('print(MISSING);' . "\n"));
		$unresolvedSummary = (new JssSummaryExtractor())->summarize($unresolvedProgram, 'missing.jss');
		$unresolvedClassifications = (new StanFrontendClassifier())->classify(['missing.jss' => $unresolvedSummary], (new StanSymbolIndexBuilder())->build(['missing.jss' => $unresolvedSummary]));
		$unresolved = $this->findClassificationByName($unresolvedClassifications, 'MISSING');
		$this->assertSame('unresolved_identifier', $unresolved['kind'] ?? null, 'STAN should classify unknown identifiers as unresolved');
		$this->assertSame('JSS identifier `MISSING` could not be resolved by STAN.', $unresolved['diagnostics'][0]['message'] ?? null, 'STAN unresolved identifier classifications should carry diagnostics');
	}

	private function testJssEmitterConsumesStanClassifications(): void
	{
		$source = implode("\n", [
			'class Box {',
			'    static count: int = 3;',
			'    static value(): int {',
			'        return 7;',
			'    }',
			'}',
			'',
			'print(Box.count, Box.value(), "\\n");',
			'',
		]);
		$phs = (new JssTranspiler())->transpileToPhsWithStanClassifications($source, 'main.jss');
		$this->assertSame(
			implode("\n", [
				'class Box {',
				"\t" . 'public static int $count = 3;',
				"\t" . 'public static function value(): int {',
				"\t\t" . 'return 7;',
				"\t" . '}',
				'}',
				'echo Box::$count, Box::value(), "\\n";',
				'',
			]),
			$phs,
			'JSS emission should be able to consume STAN member classifications'
		);
	}

	private function testStanClassifiesJssClassConstants(): void
	{
		$source = implode("\n", [
			'class Box {',
			'    const CODE = 23;',
			'}',
			'',
			'print(Box.CODE, "\\n");',
			'',
		]);
		$program = (new JssParser())->parse((new JssTokenizer())->tokenize($source));
		$summary = (new JssSummaryExtractor())->summarize($program, 'main.jss');
		$this->assertSame('CODE', $summary['root_classes'][0]['constants'][0]['name'] ?? null, 'JSS summary should expose class constants');

		$fileSummaries = ['main.jss' => $summary];
		$classifications = (new StanFrontendClassifier())->classify($fileSummaries, (new StanSymbolIndexBuilder())->build($fileSummaries));
		$kinds = array_map(
			static fn (array $classification): string => (string) ($classification['kind'] ?? ''),
			array_values($classifications)
		);
		$this->assertSame(true, in_array('class_constant', $kinds, true), 'STAN should classify known JSS class constant access');

		$phs = (new JssTranspiler())->transpileToPhsWithStanClassifications($source, 'main.jss');
		$this->assertSame(
			implode("\n", [
				'class Box {',
				"\t" . 'public const CODE = 23;',
				'}',
				'echo Box::CODE, "\\n";',
				'',
			]),
			$phs,
			'JSS emission should consume STAN class constant classifications'
		);
	}

	private function testJssClassifiedEmissionSupportsReservedHelperFamilies(): void
	{
		$source = implode("\n", [
			'print(fs.get("a.txt"), fs.mkdir("tmp"), json.decode("{}"), "\\n");',
			'',
		]);
		$program = (new JssParser())->parse((new JssTokenizer())->tokenize($source));
		$summary = (new JssSummaryExtractor())->summarize($program, 'main.jss');
		$classifications = (new StanFrontendClassifier())->classify(['main.jss' => $summary], (new StanSymbolIndexBuilder())->build(['main.jss' => $summary]));
		$this->assertSame('result<string>', $this->findClassificationByTarget($classifications, 'fs_get')['return_type'] ?? null, 'STAN helper classification should expose fs_get return contract truth');
		$this->assertSame('bool', $this->findClassificationByTarget($classifications, 'fs_mkdir')['return_type'] ?? null, 'STAN helper classification should expose plain bool fs mutator return contract truth');
		$this->assertSame('dynamic', $this->findClassificationByTarget($classifications, 'json_decode')['return_type'] ?? null, 'STAN helper classification should expose json_decode dynamic return contract truth');

		$phs = (new JssTranspiler())->transpileToPhsWithStanClassifications($source, 'main.jss');
		$this->assertSame(
			implode("\n", [
				'echo fs_get("a.txt"), fs_mkdir("tmp"), json_decode("{}"), "\\n";',
				'',
			]),
			$phs,
			'JSS should lower reserved helper-family calls directly to strict helper names'
		);
	}

	private function testJssTakeSupportsResultWrapperHelperFlow(): void
	{
		$source = implode("\n", [
			'function show(path: string): void {',
			'    let text: string = "";',
			'    let err: error;',
			'    if (!take(text, err, fs.get(path))) {',
			'        return;',
			'    }',
			'    print(text, "\n");',
			'}',
			'',
		]);
		$phs = (new JssTranspiler())->transpileToPhsWithStanClassifications($source, 'main.jss');
		$this->assertSame(
			implode("\n", [
				'function show(string $path): void {',
				"\t" . '$text string = "";',
				"\t" . '$err error;',
				"\t" . 'if (!take($text, $err, fs_get($path))) {',
				"\t\t" . 'return;',
				"\t" . '}',
				"\t" . 'echo $text, "\n";',
				'}',
				'',
			]),
			$phs,
			'JSS should support the existing take(result<T>) flow with reserved helper-family lowering'
		);
	}

	private function testStanClassifiesJssTakeContracts(): void
	{
		$source = implode("\n", [
			'function show(path: string): void {',
			'    let text: string = "";',
			'    let err: error;',
			'    if (!take(text, err, fs.get(path))) {',
			'        return;',
			'    }',
			'}',
			'',
		]);
		$program = (new JssParser())->parse((new JssTokenizer())->tokenize($source));
		$summary = (new JssSummaryExtractor())->summarize($program, 'main.jss');
		$takeRequests = array_values(array_filter(
			$summary['frontend_classification_requests'] ?? [],
			static fn (array $request): bool => ($request['kind'] ?? '') === 'take_contract'
		));
		$this->assertSame(1, count($takeRequests), 'JSS summary should ask STAN to classify take(...) contracts');
		$this->assertSame('fs_get', $takeRequests[0]['source_call_target'] ?? null, 'JSS take request should carry the normalized helper source target');
		$this->assertSame('result<string>', $takeRequests[0]['source_type'] ?? null, 'JSS take request should carry the helper source wrapper type');
		$this->assertSame('text', $takeRequests[0]['outputs'][0]['name'] ?? null, 'JSS take request should carry the first output name');
		$this->assertSame('string', $takeRequests[0]['outputs'][0]['type'] ?? null, 'JSS take request should carry the first output type');
		$this->assertSame('err', $takeRequests[0]['outputs'][1]['name'] ?? null, 'JSS take request should carry the error output name');
		$this->assertSame('error', $takeRequests[0]['outputs'][1]['type'] ?? null, 'JSS take request should carry the error output type');

		$classifications = (new StanFrontendClassifier())->classify(['main.jss' => $summary], (new StanSymbolIndexBuilder())->build(['main.jss' => $summary]));
		$take = $this->findClassificationByKind($classifications, 'take_contract');
		$this->assertSame('result', $take['family'] ?? null, 'STAN should classify result<T> take contracts');
		$this->assertSame('fs_get', $take['source_call_target'] ?? null, 'STAN should preserve the normalized helper source target');
		$this->assertSame('result<string>', $take['source_type'] ?? null, 'STAN should derive take source type from runtime helper catalog truth');
		$this->assertSame(['string', 'error'], $take['output_types'] ?? null, 'STAN should return canonical take output types');
		$this->assertSame([], $take['diagnostics'] ?? null, 'valid take contracts should not produce diagnostics');

		$badSource = implode("\n", [
			'function show(path: string): void {',
			'    let text: int = 0;',
			'    let err: error;',
			'    if (!take(text, err, fs.get(path))) {',
			'        return;',
			'    }',
			'}',
			'',
		]);
		$badProgram = (new JssParser())->parse((new JssTokenizer())->tokenize($badSource));
		$badSummary = (new JssSummaryExtractor())->summarize($badProgram, 'bad.jss');
		$badClassifications = (new StanFrontendClassifier())->classify(['bad.jss' => $badSummary], (new StanSymbolIndexBuilder())->build(['bad.jss' => $badSummary]));
		$badTake = $this->findClassificationByKind($badClassifications, 'invalid_take_contract');
		$this->assertSame('`take(...)` expects output `text` to have type `string` but found `int`.', $badTake['diagnostics'][0]['message'] ?? null, 'STAN should own the canonical take output type mismatch diagnostic');

		$semanticResult = (new StanSemanticPass())->analyze(['bad.jss' => $badSummary], $this->root);
		$this->assertSame('frontend_take_contract', $semanticResult['frontend_diagnostics'][0]['code'] ?? null, 'STAN semantic result should expose take contract failures as frontend diagnostics');
		$this->assertSame('`take(...)` expects output `text` to have type `string` but found `int`.', $semanticResult['frontend_diagnostics'][0]['message'] ?? null, 'STAN frontend diagnostics should preserve canonical take messages');
	}

	private function testJssClassifiedEmissionUsesStanTakeDiagnostics(): void
	{
		$source = implode("\n", [
			'function show(path: string): void {',
			'    let text: int = 0;',
			'    let err: error;',
			'    if (!take(text, err, fs.get(path))) {',
			'        return;',
			'    }',
			'}',
			'',
		]);
		$this->assertClassifiedTranspileFails(
			$source,
			'`take(...)` expects output `text` to have type `string` but found `int`.',
			'classified JSS emission should surface the STAN-owned take output type diagnostic',
			'main.jss:4:10'
		);
	}

	private function testStanClassifiesJssHelperModuleAvailability(): void
	{
		$source = implode("\n", [
			'function show(path: string): void {',
			'    print(fs.get(path), "\\n");',
			'}',
			'',
		]);
		$program = (new JssParser())->parse((new JssTokenizer())->tokenize($source));
		$summary = (new JssSummaryExtractor())->summarize($program, 'main.jss');
		$symbolIndex = (new StanSymbolIndexBuilder())->build(['main.jss' => $summary]);

		$classifications = (new StanFrontendClassifier())->classify(['main.jss' => $summary], $symbolIndex, ['json', 'datetime']);
		$helper = $this->findClassificationByTarget($classifications, 'fs_get');
		$this->assertSame('unavailable_runtime_module', $helper['kind'] ?? null, 'STAN should reject helper calls whose required runtime module is inactive');
		$this->assertSame('filesystem', $helper['required_module'] ?? null, 'STAN should report the required runtime module for helper calls');
		$this->assertSame('Runtime helper `fs_get()` requires module `filesystem` in the active project runtime config.', $helper['diagnostics'][0]['message'] ?? null, 'STAN should own helper module availability diagnostics');

		$semanticResult = (new StanSemanticPass())->analyze(['main.jss' => $summary], $this->root, ['json', 'datetime']);
		$moduleDiagnostics = array_values(array_filter(
			$semanticResult['frontend_diagnostics'] ?? [],
			static fn (array $diagnostic): bool => ($diagnostic['message'] ?? null) === 'Runtime helper `fs_get()` requires module `filesystem` in the active project runtime config.'
		));
		$this->assertSame(1, count($moduleDiagnostics), 'STAN semantic result should expose helper module diagnostics as frontend diagnostics');
		$this->assertSame('frontend_member_access', $moduleDiagnostics[0]['code'] ?? null, 'STAN should attach helper module diagnostics to the helper member-access request');

		$enabledClassifications = (new StanFrontendClassifier())->classify(['main.jss' => $summary], $symbolIndex, ['json', 'datetime', 'filesystem']);
		$enabledHelper = $this->findClassificationByTarget($enabledClassifications, 'fs_get');
		$this->assertSame('function', $enabledHelper['kind'] ?? null, 'STAN should accept helper calls when their required runtime module is active');
		$this->assertSame('result<string>', $enabledHelper['return_type'] ?? null, 'accepted helper calls should still expose runtime return type truth');
	}

	private function testJssEmitterConsumesStanIdentifierClassifications(): void
	{
		$source = implode("\n", [
			'const LIMIT = 9;',
			'print(LIMIT + 2, "\\n");',
			'',
		]);
		$phs = (new JssTranspiler())->transpileToPhsWithStanClassifications($source, 'main.jss');
		$this->assertSame(
			implode("\n", [
				'const LIMIT = 9;',
				'echo LIMIT + 2, "\\n";',
				'',
			]),
			$phs,
			'JSS emission should be able to consume STAN identifier classifications for constants'
		);
	}

	private function testJssEmitterConsumesStanBinaryPlusClassifications(): void
	{
		$source = implode("\n", [
			'let name: string = "Ada";',
			'let suffix: string = " Lovelace";',
			'let count: int = 2;',
			'print(name + suffix, count + 3, "\\n");',
			'',
		]);
		$phs = (new JssTranspiler())->transpileToPhsWithStanClassifications($source, 'main.jss');
		$this->assertSame(
			implode("\n", [
				'$name string = "Ada";',
				'$suffix string = " Lovelace";',
				'$count int = 2;',
				'echo $name . $suffix, $count + 3, "\\n";',
				'',
			]),
			$phs,
			'JSS emission should be able to consume STAN binary plus classifications'
		);
	}

	private function testJssSummaryCarriesParameterTypesForBinaryPlus(): void
	{
		$source = implode("\n", [
			'function bump(value: int): int {',
			'    return value + 1;',
			'}',
			'',
		]);
		$phs = (new JssTranspiler())->transpileToPhsWithStanClassifications($source, 'main.jss');
		$this->assertSame(
			implode("\n", [
				'function bump(int $value): int {',
				"\t" . 'return $value + 1;',
				'}',
				'',
			]),
			$phs,
			'JSS summary should carry parameter type hints into binary plus classification'
		);
	}

	private function testJssSummaryCarriesForeachValueTypesForBinaryPlus(): void
	{
		$source = implode("\n", [
			'let items: vector<string> = ["Ada"];',
			'let suffix: string = " Lovelace";',
			'for (let item: string of items) {',
			'    print(item + suffix, "\\n");',
			'}',
			'',
		]);
		$phs = (new JssTranspiler())->transpileToPhsWithStanClassifications($source, 'main.jss');
		$this->assertSame(
			implode("\n", [
				'$items vector<string> = ["Ada"];',
				'$suffix string = " Lovelace";',
				'foreach ($items as $item string) {',
				"\t" . 'echo $item . $suffix, "\\n";',
				'}',
				'',
			]),
			$phs,
			'JSS summary should carry typed foreach value hints into binary plus classification'
		);
	}

	private function testJssSummaryCarriesForInitTypesForBinaryPlus(): void
	{
		$source = implode("\n", [
			'for (let i: int = 0; i < 2; i++) {',
			'    print(i + 1, "\\n");',
			'}',
			'',
		]);
		$phs = (new JssTranspiler())->transpileToPhsWithStanClassifications($source, 'main.jss');
		$this->assertSame(
			implode("\n", [
				'for ($i int = 0; $i < 2; $i++) {',
				"\t" . 'echo $i + 1, "\\n";',
				'}',
				'',
			]),
			$phs,
			'JSS summary should carry typed for initializer hints into binary plus classification'
		);
	}

	private function testJssSummaryInfersSimpleLocalTypesForBinaryPlus(): void
	{
		$source = implode("\n", [
			'let name = "Ada";',
			'let suffix = " Lovelace";',
			'let total = 1;',
			'total = 2;',
			'print(name + suffix, total + 3, "\\n");',
			'',
		]);
		$phs = (new JssTranspiler())->transpileToPhsWithStanClassifications($source, 'main.jss');
		$this->assertSame(
			implode("\n", [
				'$name = "Ada";',
				'$suffix = " Lovelace";',
				'$total = 1;',
				'$total = 2;',
				'echo $name . $suffix, $total + 3, "\\n";',
				'',
			]),
			$phs,
			'JSS summary should infer simple local and assignment type hints for binary plus classification'
		);
	}

	private function testJssClassifiedEmissionUsesDynamicPlusBeforeStringConcat(): void
	{
		$source = implode("\n", [
			'let value: mixed = "Ada";',
			'let suffix: string = " Lovelace";',
			'print(value + suffix, "\\n");',
			'',
		]);
		$phs = (new JssTranspiler())->transpileToPhsWithStanClassifications($source, 'main.jss');
		$this->assertSame(
			implode("\n", [
				'$value mixed = "Ada";',
				'$suffix string = " Lovelace";',
				'echo js_plus($value, $suffix), "\\n";',
				'',
			]),
			$phs,
			'classified JSS emission should route mixed/string plus through js_plus instead of static concat'
		);
	}

	private function testJssClassifiedEmissionConsumesFunctionCalleeClassifications(): void
	{
		$source = implode("\n", [
			'function make_value(): int {',
			'    return 7;',
			'}',
			'',
			'print(make_value(), "\\n");',
			'',
		]);
		$phs = (new JssTranspiler())->transpileToPhsWithStanClassifications($source, 'main.jss');
		$this->assertSame(
			implode("\n", [
				'function make_value(): int {',
				"\t" . 'return 7;',
				'}',
				'echo make_value(), "\\n";',
				'',
			]),
			$phs,
			'classified JSS emission should consume STAN function callee classifications'
		);
	}

	private function testJssClassifiedEmissionRejectsUnresolvedFunctionCall(): void
	{
		$source = implode("\n", [
			'print(missing_value(), "\\n");',
			'',
		]);
		try {
			(new JssTranspiler())->transpileToPhsWithStanClassifications($source, 'main.jss');
		} catch (RuntimeException $exception) {
			$this->assertContains('JSS identifier `missing_value` could not be resolved by STAN.', $exception->getMessage(), 'classified JSS emission should fail unresolved function callees');
			$this->assertContains('at main.jss:1:7.', $exception->getMessage(), 'unresolved function callees should include JSS source locations');
			return;
		}
		throw new RuntimeException('classified JSS emission should reject unresolved function callees');
	}

	private function testJssEmitterRejectsNonFunctionIdentifierCallWhenRequired(): void
	{
		$source = implode("\n", [
			'const LIMIT = 9;',
			'print(LIMIT(), "\\n");',
			'',
		]);
		$classifications = [
			[
				'request_kind' => 'identifier_role',
				'name' => 'LIMIT',
				'kind' => 'constant',
				'path' => 'main.jss',
				'line' => 2,
				'column' => 7,
				'diagnostics' => [],
			],
		];
		try {
			(new JssTranspiler())->transpileToPhsWithProvidedClassifications($source, 'main.jss', $classifications);
		} catch (RuntimeException $exception) {
			$this->assertContains('JSS call target `LIMIT` was classified as `constant`, not a function.', $exception->getMessage(), 'required classified emission should reject non-function identifier callees');
			$this->assertContains('at main.jss:2:7.', $exception->getMessage(), 'non-function identifier callees should include classification source locations');
			return;
		}
		throw new RuntimeException('required classified emission should reject non-function identifier callees');
	}

	private function testJssClassifiedEmissionClassifiesSameFileFunctionBinaryPlus(): void
	{
		$source = implode("\n", [
			'function suffix(): string {',
			'    return "x";',
			'}',
			'',
			'let value = suffix();',
			'print(value + 1, "\\n");',
			'',
		]);
		$output = (new JssTranspiler())->transpileToPhsWithStanClassifications($source, 'main.jss');
		$this->assertContains('echo $value . 1, "\\n";', $output, 'classified JSS emission should use same-file function return types for `+` sites');
	}

	private function testJssClassifiedEmissionRejectsKnownInvalidBinaryPlus(): void
	{
		$source = implode("\n", [
			'let flag: bool = true;',
			'print(flag + 1, "\\n");',
			'',
		]);
		try {
			(new JssTranspiler())->transpileToPhsWithStanClassifications($source, 'main.jss');
		} catch (\Throwable $exception) {
			$this->assertContains('JSS `+` requires numeric operands, a `mixed`/`dynamic` boundary, or one static string operand plus a known printable type', $exception->getMessage(), 'classified JSS emission should fail known invalid `+` operands');
			return;
		}
		throw new RuntimeException('classified JSS emission should reject known invalid `+` operands');
	}

	private function testJssClassifiedEmissionRejectsInvalidStaticMethod(): void
	{
		$source = implode("\n", [
			'class Box {',
			'    static value(): int {',
			'        return 7;',
			'    }',
			'}',
			'',
			'print(Box.missing(), "\\n");',
			'',
		]);
		try {
			(new JssTranspiler())->transpileToPhsWithStanClassifications($source, 'main.jss');
		} catch (RuntimeException $exception) {
			$this->assertContains('Static method `Box.missing()` could not be resolved.', $exception->getMessage(), 'classified JSS emission should fail invalid static method sites');
			return;
		}
		throw new RuntimeException('classified JSS emission should reject invalid static method sites');
	}

	private function testJssClassifiedEmissionRejectsInvalidClassMemberRead(): void
	{
		$source = implode("\n", [
			'class Box {',
			'    const CODE = 23;',
			'}',
			'',
			'print(Box.missing, "\\n");',
			'',
		]);
		try {
			(new JssTranspiler())->transpileToPhsWithStanClassifications($source, 'main.jss');
		} catch (RuntimeException $exception) {
			$this->assertContains('Class member `Box.missing` could not be resolved as a static property or class constant.', $exception->getMessage(), 'classified JSS emission should fail invalid class member reads');
			return;
		}
		throw new RuntimeException('classified JSS emission should reject invalid class member reads');
	}

	private function testJssEmitterRejectsMissingMemberClassificationWhenRequired(): void
	{
		$source = implode("\n", [
			'let box: Box = new Box();',
			'print(box.value, "\\n");',
			'',
		]);
		$program = (new JssParser())->parse((new JssTokenizer())->tokenize($source));
		try {
			(new \Scpp\S2S\Jss\JssEmitter())->emit($program, [], true);
		} catch (RuntimeException $exception) {
			$this->assertContains('JSS member access `box.value` has no STAN classification.', $exception->getMessage(), 'required classified emission should fail missing member classifications');
			$this->assertContains('at 2:7.', $exception->getMessage(), 'missing member classifications should include JSS source locations');
			return;
		}
		throw new RuntimeException('required classified emission should reject missing member classifications');
	}

	private function testJssClassifiedEmissionKeepsLocalIdentifiersLocal(): void
	{
		$source = implode("\n", [
			'function show(value: int): void {',
			'    print(value);',
			'}',
			'',
			'let local = 3;',
			'print(local, "\\n");',
			'',
		]);
		$phs = (new JssTranspiler())->transpileToPhsWithStanClassifications($source, 'main.jss');
		$this->assertSame(
			implode("\n", [
				'function show(int $value): void {',
				"\t" . 'echo $value;',
				'}',
				'$local = 3;',
				'echo $local, "\\n";',
				'',
			]),
			$phs,
			'classified JSS emission should keep params and untyped locals as local identifiers'
		);
	}

	private function testJssClassifiedEmissionRejectsUnresolvedIdentifier(): void
	{
		$source = implode("\n", [
			'let value: int = 3;',
			'print(value, MISSING, "\\n");',
			'',
		]);
		try {
			(new JssTranspiler())->transpileToPhsWithStanClassifications($source, 'main.jss');
		} catch (RuntimeException $exception) {
			$this->assertContains('JSS identifier `MISSING` could not be resolved by STAN.', $exception->getMessage(), 'classified JSS emission should fail unresolved non-local identifiers');
			$this->assertContains('at main.jss:2:14.', $exception->getMessage(), 'unresolved identifiers should include JSS source locations');
			return;
		}
		throw new RuntimeException('classified JSS emission should reject unresolved non-local identifiers');
	}

	private function testJssEmitterRejectsMissingIdentifierClassificationWhenRequired(): void
	{
		$source = implode("\n", [
			'print(MISSING, "\\n");',
			'',
		]);
		$program = (new JssParser())->parse((new JssTokenizer())->tokenize($source));
		try {
			(new \Scpp\S2S\Jss\JssEmitter())->emit($program, [], true);
		} catch (RuntimeException $exception) {
			$this->assertContains('JSS identifier `MISSING` has no STAN classification.', $exception->getMessage(), 'required classified emission should fail missing identifier classifications');
			$this->assertContains('at 1:7.', $exception->getMessage(), 'missing identifier classifications should include JSS source locations');
			return;
		}
		throw new RuntimeException('required classified emission should reject missing identifier classifications');
	}

	private function testJssBuildSourceFeedsStanSummaryPath(): void
	{
		$project = $this->root . '/project';
		$this->mkdir($project);
		$this->write($project . '/main.jss', 'let value: mixed = 4;' . "\n" . 'print(value + 2);' . "\n");
		$this->write($project . '/helper.phs', 'function helper(): int { return 1; }' . "\n");

		$buildSources = array_map(
			static fn (string $path): string => normalize_config_path(relative_path($project, $path)),
			collect_project_php_files($project)
		);
		sort($buildSources, SORT_STRING);
		$this->assertSame(['helper.phs', 'main.jss'], $buildSources, 'build source collection should include .jss files');

		$stanSources = array_map(
			static fn (string $path): string => normalize_config_path(relative_path($project, $path)),
			collect_project_stan_source_files($project)
		);
		sort($stanSources, SORT_STRING);
		$this->assertSame(['helper.phs', 'main.jss'], $stanSources, 'STAN source collection should include .jss files');

		$session = new \Scpp\S2S\Stan\StanWorkspaceSession();
		$this->write($project . '/prism.json', json_encode([
			'runtime' => ['languages' => ['php' => ['profile' => 'strict']]],
			'entry' => 'main.jss',
		], JSON_PRETTY_PRINT) . "\n");
		$snapshot = $session->createBridgeSnapshot($project, $project . '/prism.json', []);
		$classifications = $snapshot['semantic_result']['frontend_classifications'] ?? [];
		$dynamicPlus = null;
		foreach (is_array($classifications) ? $classifications : [] as $classification) {
			if (is_array($classification) && ($classification['kind'] ?? null) === 'dynamic_plus') {
				$dynamicPlus = $classification;
				break;
			}
		}
		if (!is_array($dynamicPlus)) {
			throw new RuntimeException('Expected project STAN session to classify JSS dynamic plus.');
		}
		$this->assertSame(normalize_path($project . '/main.jss'), normalize_path((string) ($dynamicPlus['path'] ?? '')), 'JSS classification should carry source path');
		$this->assertSame(2, (int) ($dynamicPlus['line'] ?? 0), 'JSS classification should carry source line');
	}

	private function testJssParserAllowsLooseEqualityTemporarily(): void
	{
		$equalPhs = (new JssTranspiler())->transpileToPhs('if (value == 1) { print("ok"); }' . "\n");
		$this->assertContains('if ($value == 1) {', $equalPhs, 'JSS should temporarily allow `==` until equality semantics are reviewed');
		$notEqualPhs = (new JssTranspiler())->transpileToPhs('if (value != 1) { print("ok"); }' . "\n");
		$this->assertContains('if ($value != 1) {', $notEqualPhs, 'JSS should temporarily allow `!=` until equality semantics are reviewed');
	}

	private function testJssParserRejectsDynamicThisAndPrototype(): void
	{
		$this->assertParseFails(
			'print(this.value);' . "\n",
			'JSS dynamic `this` binding is not supported',
			'JSS should reject JavaScript dynamic this binding',
			'at 1:7.'
		);
		$this->assertParseFails(
			'class Box { static bad(): void { print(this.value); } }' . "\n",
			'JSS dynamic `this` binding is not supported',
			'JSS should reject this inside static methods',
			'at 1:40.'
		);
		$this->assertParseFails(
			'print(Box.prototype.value);' . "\n",
			'JSS prototype access is not supported',
			'JSS should reject JavaScript prototype access',
			'at 1:11.'
		);
		$this->assertParseFails(
			'print(prototype);' . "\n",
			'JSS prototype objects are not supported',
			'JSS should reject bare prototype objects',
			'at 1:7.'
		);
	}

	private function testJssParserRejectsJavaScriptModuleSyntax(): void
	{
		$this->assertParseFails(
			'import Box from "box";' . "\n",
			'JSS JavaScript module syntax `import` is not supported',
			'JSS should reject JavaScript import syntax with a static-use diagnostic',
			'at 1:1.'
		);
		$this->assertParseFails(
			'export function value(): int { return 1; }' . "\n",
			'JSS JavaScript module syntax `export` is not supported',
			'JSS should reject JavaScript export syntax with a static-use diagnostic',
			'at 1:1.'
		);
	}

	private function testJssNamespaceBlockTranspilesToExistingNamespaceSemantics(): void
	{
		$source = implode("\n", [
			'namespace Demo {',
			'    class Box {',
			'        static value(): int {',
			'            return 7;',
			'        }',
			'    }',
			'}',
			'',
			'namespace App {',
			'    print(Demo.Box.value(), "\\n");',
			'}',
			'',
		]);
		$phs = (new JssTranspiler())->transpileToPhsWithStanClassifications($source, 'main.jss');
		$this->assertSame(
			implode("\n", [
				'namespace Demo;',
				'class Box {',
				"\t" . 'public static function value(): int {',
				"\t\t" . 'return 7;',
				"\t" . '}',
				'}',
				'namespace App;',
				'echo \\Demo\\Box::value(), "\\n";',
				'',
			]),
			$phs,
			'JSS namespace blocks should lower to the existing semicolon-style namespace flow'
		);

		$program = (new JssParser())->parse((new JssTokenizer())->tokenize($source));
		$summary = (new JssSummaryExtractor())->summarize($program, 'main.jss');
		$this->assertSame('Demo', $summary['namespaces'][0]['name'] ?? null, 'JSS summary should record namespace blocks');
		$this->assertSame('App', $summary['namespaces'][1]['name'] ?? null, 'JSS summary should record later namespace blocks');
	}

	private function testJssParserBlocksReservedHelperRootsFromUserNamespaces(): void
	{
		$this->assertParseFails(
			'namespace fs { function local(): void { } }' . "\n",
			'JSS namespace root `fs` is reserved for helper-family calls',
			'JSS should block user namespaces from reusing the fs helper root',
			'at 1:1.'
		);
		$this->assertParseFails(
			'use function fs.get;' . "\n",
			'JSS reserved helper root `fs` is not imported through `use`',
			'JSS should block use-import syntax for reserved helper roots',
			'at 1:1.'
		);
		$this->assertParseFails(
			'use App.Tools as json;' . "\n",
			'JSS alias `json` is reserved for helper-family calls',
			'JSS should block aliases that would reuse reserved helper roots',
			'at 1:1.'
		);
	}

	private function testJssParserSupportsPublicClassMembersOnly(): void
	{
		$source = implode("\n", [
			'class Box {',
			'    public value: int = 1;',
			'    public getValue(): int { return this.value; }',
			'    public static count: int = 2;',
			'    public static getCount(): int { return Box.count; }',
			'}',
			'',
		]);
		$phs = (new JssTranspiler())->transpileToPhs($source);
		$this->assertContains('public int $value = 1;', $phs, 'JSS should lower explicit public class fields as public PHS properties');
		$this->assertContains('public function getValue(): int {', $phs, 'JSS should lower explicit public methods as public PHS methods');
		$this->assertContains('public static int $count = 2;', $phs, 'JSS should lower explicit public static fields as public static PHS properties');
		$this->assertContains('public static function getCount(): int {', $phs, 'JSS should lower explicit public static methods as public static PHS methods');

		$this->assertParseFails(
			'class Box { private value: int = 1; }' . "\n",
			'JSS class member modifier `private` is not supported',
			'JSS should reject private keyword visibility for now',
			'at 1:13.'
		);
		$this->assertParseFails(
			'class Box { protected value: int = 1; }' . "\n",
			'JSS class member modifier `protected` is not supported',
			'JSS should reject protected keyword visibility for now',
			'at 1:13.'
		);
	}

	private function testJssParserRequiresFunctionAndMethodReturnTypes(): void
	{
		$source = implode("\n", [
			'function show(value: int): void {',
			'    print(value, "\\n");',
			'}',
			'class Box {',
			'    value(): int { return 1; }',
			'}',
			'',
		]);
		$phs = (new JssTranspiler())->transpileToPhs($source);
		$this->assertContains('function show(int $value): void {', $phs, 'JSS should lower explicit void function return types');
		$this->assertContains('public function value(): int {', $phs, 'JSS should lower explicit method return types');

		$this->assertParseFails(
			'function show(value: int) { print(value); }' . "\n",
			'Expected explicit return type after JSS function parameters',
			'JSS should require function return types',
			'at 1:27.'
		);
		$this->assertParseFails(
			'class Box { value() { return 1; } }' . "\n",
			'Expected explicit return type after JSS method parameters',
			'JSS should require method return types',
			'at 1:21.'
		);
	}

	private function testJssParserEnforcesConstructorShape(): void
	{
		$source = implode("\n", [
			'class Box {',
			'    value: int;',
			'    constructor(value: int) { this.value = value; }',
			'}',
			'',
		]);
		$phs = (new JssTranspiler())->transpileToPhs($source);
		$this->assertContains('public function __construct(int $value) {', $phs, 'JSS constructors should lower to PHS __construct methods');

		$this->assertParseFails(
			'class Box { constructor(): void { } }' . "\n",
			'JSS constructors do not declare return types',
			'JSS should reject constructor return type annotations',
			'at 1:26.'
		);
		$this->assertParseFails(
			'class Box { constructor() { } constructor(value: int) { } }' . "\n",
			'JSS classes may declare only one constructor',
			'JSS should reject duplicate constructors',
			'at 1:31.'
		);
		$this->assertParseFails(
			'class Box { __construct(): void { } }' . "\n",
			'JSS constructors must use `constructor(...)`, not `__construct(...)`',
			'JSS should reject direct __construct method spelling',
			'at 1:13.'
		);
	}

	private function testJssParserRejectsLocalConstUntilSemanticsExist(): void
	{
		$phs = (new JssTranspiler())->transpileToPhs('const LIMIT = 9;' . "\n" . 'print(LIMIT);' . "\n");
		$this->assertContains('const LIMIT = 9;', $phs, 'JSS should keep top-level constants');

		$this->assertParseFails(
			'function show(): void { const value = 1; }' . "\n",
			'JSS local `const` declarations are not supported',
			'JSS should reject block-local const declarations',
			'at 1:25.'
		);
		$this->assertParseFails(
			'let value: int = 1;' . "\n" . 'const other: int = value;' . "\n",
			'JSS local `const` declarations are not supported',
			'JSS should reject typed local const declarations',
			'at 2:1.'
		);
		$this->assertParseFails(
			'for (const i: int = 0; i < 1; i++) { print(i); }' . "\n",
			'JSS local `const` declarations are not supported',
			'JSS should reject const in classic for initializers',
			'at 1:6.'
		);
		$this->assertParseFails(
			'let items: vector<int> = [1];' . "\n" . 'for (const item of items) { print(item); }' . "\n",
			'JSS `for...of` const loop locals are not supported',
			'JSS should reject const for-of locals',
			'at 2:6.'
		);
	}

	private function testJssNullCoalesceTranspilesForStrictFirstSlice(): void
	{
		$source = implode("\n", [
			'function pick(value: ?int): int {',
			'    return value ?? 10;',
			'}',
			'',
			'function takeMixed(value: mixed): mixed {',
			'    return value ?? 0;',
			'}',
			'',
		]);
		$phs = (new JssTranspiler())->transpileToPhs($source);
		$this->assertContains('return $value ?? 10;', $phs, 'JSS should lower nullable null coalescing directly to PHS');
		$this->assertContains('return $value ?? 0;', $phs, 'JSS should allow null coalescing at explicit mixed/dynamic boundaries');

		$this->assertTranspileFails(
			'let value: int = 1;' . "\n" . 'print(value ?? 2);' . "\n",
			'JSS `??` requires a nullable or explicit `mixed`/`dynamic` left operand',
			'JSS should reject null coalescing on known non-nullable left operands',
			'at 2:7.'
		);
		$this->assertTranspileFails(
			'let value: ?int = null;' . "\n" . 'print(value ?? "nope");' . "\n",
			'JSS `??` fallback must match the nullable value type in the current subset',
			'JSS should reject mismatched null coalescing fallback types',
			'at 2:7.'
		);
	}

	private function testJssParserRejectsNullCoalesceChains(): void
	{
		$this->assertParseFails(
			'let value: ?int = null;' . "\n" . 'print(value ?? 1 ?? 2);' . "\n",
			'JSS null coalescing currently supports only a single `lhs ?? rhs` site',
			'JSS should reject chained null coalescing in the first slice',
			'at 2:18.'
		);
	}

	private function testJssTernaryTranspilesForStrictFirstSlice(): void
	{
		$source = implode("\n", [
			'function maybeName(flag: bool): ?string {',
			'    let value: ?string = flag ? "ok" : null;',
			'    return value;',
			'}',
			'',
		]);
		$phs = (new JssTranspiler())->transpileToPhs($source);
		$this->assertContains('$value ?string = ($flag ? "ok" : null);', $phs, 'JSS should lower bool-only ternary expressions directly to PHS');

		$this->assertTranspileFails(
			'let name: string = "Ada";' . "\n" . 'print(name ? 1 : 0);' . "\n",
			'JSS conditions require `bool`',
			'JSS should reject ternary truthiness conditions',
			'at 2:7.'
		);
		$this->assertTranspileFails(
			'let flag: bool = true;' . "\n" . 'print(flag ? 1 : "nope");' . "\n",
			'JSS ternary branches must resolve to the same type or a `T` / `null` pair in the current subset',
			'JSS should reject mismatched ternary branch types',
			'at 2:7.'
		);
	}

	private function testJssParserRejectsTernaryChains(): void
	{
		$this->assertParseFails(
			'let flag: bool = true;' . "\n" . 'print(flag ? 1 : false ? 2 : 3);' . "\n",
			'JSS ternary currently supports only a single `cond ? a : b` site',
			'JSS should reject chained ternary expressions in the first slice',
			'at 2:18.'
		);
	}

	private function testJssParserRejectsMoreUnsupportedSyntaxWithLocations(): void
	{
		$this->assertParseFails(
			'let { value } = row;' . "\n",
			'JSS destructuring declarations are not supported',
			'JSS should reject object destructuring declarations',
			'at 1:5.'
		);
		$this->assertParseFails(
			'print(...items);' . "\n",
			'JSS spread/rest syntax is not supported',
			'JSS should reject spread arguments',
			'at 1:7.'
		);
		$this->assertParseFails(
			'let fn = (value) => value;' . "\n",
			'Expected `;` after JSS declaration. Found `=>`',
			'JSS should currently reject grouped arrow syntax at the declaration boundary',
			'at 1:18.'
		);
		$this->assertParseFails(
			'value => value;' . "\n",
			'JSS arrow functions are not supported yet',
			'JSS should reject bare arrow syntax with the explicit unsupported-syntax diagnostic',
			'at 1:7.'
		);
		$this->assertParseFails(
			'print({"name"});' . "\n",
			'Expected `:` after object literal key.',
			'JSS should report object literal missing colon at the closing token',
			'at 1:14.'
		);
		$this->assertParseFails(
			'let row = { true: 1 };' . "\n",
			'Expected object literal key',
			'JSS should reject unsupported object literal keys',
			'at 1:13.'
		);
		$this->assertParseFails(
			'print(`Hello ${name + "!"}`);' . "\n",
			'JSS template literals currently support only `${identifier}` and `${a.b}`-style interpolation',
			'JSS should reject complex template literal interpolation',
			'at 1:7.'
		);
	}

	private function testJssTemplateLiteralSupportsMemberInterpolation(): void
	{
		$source = implode("\n", [
			'class User {',
			'    name: string = "Ada";',
			'}',
			'let user: User = new User();',
			'print(`Hello ${user.name}`, "\n");',
			'',
		]);
		$phs = (new JssTranspiler())->transpileToPhsWithStanClassifications($source, 'main.jss');
		$this->assertContains('echo "Hello " . $user->name, "\n";', $phs, 'JSS should lower dotted template interpolation to explicit concat');
	}

	private function testJssTemplateLiteralSupportsStaticMemberInterpolation(): void
	{
		$source = implode("\n", [
			'class BuildInfo {',
			'    static version: int = 3;',
			'    const LABEL = "JSS";',
			'}',
			'print(`v${BuildInfo.version}:${BuildInfo.LABEL}`, "\n");',
			'',
		]);
		$phs = (new JssTranspiler())->transpileToPhsWithStanClassifications($source, 'main.jss');
		$this->assertContains('echo "v" . BuildInfo::$version . ":" . BuildInfo::LABEL, "\n";', $phs, 'JSS should lower classified static/class-constant template interpolation to explicit concat');
	}

	private function testJssSemanticValidatorAcceptsBoolConditions(): void
	{
		$source = implode("\n", [
			'let flag: bool = true;',
			'if (flag) { print("yes", "\\n"); }',
			'while (flag !== false) { flag = false; }',
			'do { flag = true; } while (flag === false);',
			'',
		]);
		$phs = (new JssTranspiler())->transpileToPhs($source);
		$this->assertContains('if ($flag) {', $phs, 'JSS should allow typed bool conditions');
		$this->assertContains('while ($flag !== false) {', $phs, 'JSS should allow explicit comparison conditions');
		$this->assertContains('} while ($flag === false);', $phs, 'JSS should allow do while explicit comparison conditions');
	}

	private function testJssSemanticValidatorRejectsTruthyConditions(): void
	{
		$this->assertTranspileFails(
			'let name: string = "Ada";' . "\n" . 'if (name) { print(name); }' . "\n",
			'JSS conditions require `bool`',
			'JSS should reject string truthiness in if conditions',
			'at 2:5.'
		);
		$this->assertTranspileFails(
			'let count: int = 1;' . "\n" . 'while (count) { count = count - 1; }' . "\n",
			'JSS conditions require `bool`',
			'JSS should reject int truthiness in while conditions',
			'at 2:8.'
		);
		$this->assertTranspileFails(
			'let maybe: ?string = null;' . "\n" . 'do { print("x"); } while (maybe);' . "\n",
			'JSS conditions require `bool`',
			'JSS should reject nullable truthiness in do while conditions',
			'at 2:27.'
		);
	}

	private function testJssSemanticValidatorRejectsTruthyLogicalOperands(): void
	{
		$this->assertTranspileFails(
			'let name: string = "Ada";' . "\n" . 'let flag: bool = true;' . "\n" . 'if (name && flag) { print(name); }' . "\n",
			'JSS logical operators `&&` and `||` require `bool` operands',
			'JSS should reject string truthiness in logical and',
			'at 3:5.'
		);
		$this->assertTranspileFails(
			'let count: int = 1;' . "\n" . 'let flag: bool = true;' . "\n" . 'if (count || flag) { print("x"); }' . "\n",
			'JSS logical operators `&&` and `||` require `bool` operands',
			'JSS should reject int truthiness in logical or',
			'at 3:5.'
		);
		$this->assertTranspileFails(
			'let name: string = "Ada";' . "\n" . 'if (!name) { print("empty"); }' . "\n",
			'JSS logical operator `!` requires a `bool` operand',
			'JSS should reject string truthiness in logical not',
			'at 2:6.'
		);
	}

	private function testJssSemanticValidatorSupportsUnaryMinus(): void
	{
		$source = implode("\n", [
			'let offset: int = -1;',
			'let scale: float = -1.5;',
			'print(offset, scale, "\n");',
			'',
		]);
		$phs = (new JssTranspiler())->transpileToPhs($source);
		$this->assertContains('$offset int = -1;', $phs, 'JSS should allow unary minus on int literals');
		$this->assertContains('$scale float = -1.5;', $phs, 'JSS should allow unary minus on float literals');

		$this->assertTranspileFails(
			'let flag: bool = true;' . "\n" . 'print(-flag);' . "\n",
			'JSS unary `-` requires an `int` or `float` operand',
			'JSS should reject unary minus on bool operands',
			'at 2:8.'
		);
	}

	private function testJssSemanticValidatorValidatesForeachSources(): void
	{
		$source = implode("\n", [
			'let flags: vector<bool> = [true];',
			'for (let flag of flags) {',
			'    if (flag) { print("yes"); }',
			'}',
			'',
		]);
		$phs = (new JssTranspiler())->transpileToPhs($source);
		$this->assertContains('foreach ($flags as $flag) {', $phs, 'JSS should allow foreach over typed vectors');

		$this->assertParseFails(
			'let items: vector<int> = [1];' . "\n" . 'for (item of items) { print(item); }' . "\n",
			'JSS `for...of` requires `let` or `const` loop locals',
			'JSS should reject bare for-of loop locals',
			'at 2:6.'
		);
		$this->assertTranspileFails(
			'let name: string = "Ada";' . "\n" . 'for (let ch of name) { print(ch); }' . "\n",
			'JSS `for...of` requires a `vector<T>` or `hash<T>` source',
			'JSS should reject foreach over known non-container values',
			'at 2:1.'
		);
		$this->assertTranspileFails(
			'let items: vector<int> = [1];' . "\n" . 'for (let k, value of items) { print(value); }' . "\n",
			'JSS key/value `for...of` requires a `hash<T>` source',
			'JSS should reject key/value foreach over vector sources',
			'at 2:1.'
		);
	}

	private function testJssSemanticValidatorRejectsUnsafeTypeDefaults(): void
	{
		$this->assertTranspileFails(
			'let count: int = 1.5;' . "\n",
			'JSS cannot assign `float` to `int` without an explicit conversion',
			'JSS should reject float-to-int initialization',
			'at 1:18.'
		);
		$this->assertTranspileFails(
			'let value = null;' . "\n",
			'JSS `null` requires an explicit nullable target type',
			'JSS should reject null without nullable target type',
			'at 1:13.'
		);
		$this->assertTranspileFails(
			'let items = [1, 2];' . "\n",
			'JSS array literals require an explicit `vector<T>` target type',
			'JSS should reject untyped array literals',
			'at 1:13.'
		);
		$this->assertTranspileFails(
			'let row = {"name": "Ada"};' . "\n",
			'JSS object/hash literals require an explicit `hash<T>` target type',
			'JSS should reject untyped object/hash literals',
			'at 1:11.'
		);
		$this->assertTranspileFails(
			'let text: string = "Ada";' . "\n" . 'print(text + null);' . "\n",
			'JSS `+` requires numeric operands, a `mixed`/`dynamic` boundary, or one static string operand plus a known printable type',
			'JSS should reject string plus null without an explicit conversion',
			'at 2:7.'
		);
	}

	private function assertSame(mixed $expected, mixed $actual, string $message): void
	{
		if ($expected === $actual) {
			return;
		}
		throw new RuntimeException($message . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true));
	}

	private function assertContains(string $needle, string $haystack, string $message): void
	{
		if (str_contains($haystack, $needle)) {
			return;
		}
		throw new RuntimeException($message . ' missing `' . $needle . '` in `' . $haystack . '`');
	}

	private function assertParseFails(string $source, string $expectedMessage, string $message, ?string $expectedLocation = null): void
	{
		try {
			(new JssParser())->parse((new JssTokenizer())->tokenize($source));
		} catch (\Throwable $exception) {
			$this->assertContains($expectedMessage, $exception->getMessage(), $message);
			if ($expectedLocation !== null) {
				$this->assertContains($expectedLocation, $exception->getMessage(), $message . ' should include source location');
			}
			return;
		}
		throw new RuntimeException($message . ' did not fail.');
	}

	private function assertTranspileFails(string $source, string $expectedMessage, string $message, ?string $expectedLocation = null): void
	{
		try {
			(new JssTranspiler())->transpileToPhs($source);
		} catch (\Throwable $exception) {
			$this->assertContains($expectedMessage, $exception->getMessage(), $message);
			if ($expectedLocation !== null) {
				$this->assertContains($expectedLocation, $exception->getMessage(), $message . ' should include source location');
			}
			return;
		}
		throw new RuntimeException($message . ' did not fail.');
	}

	private function assertClassifiedTranspileFails(string $source, string $expectedMessage, string $message, ?string $expectedLocation = null): void
	{
		try {
			(new JssTranspiler())->transpileToPhsWithStanClassifications($source, 'main.jss');
		} catch (\Throwable $exception) {
			$this->assertContains($expectedMessage, $exception->getMessage(), $message);
			if ($expectedLocation !== null) {
				$this->assertContains($expectedLocation, $exception->getMessage(), $message . ' should include source location');
			}
			return;
		}
		throw new RuntimeException($message . ' did not fail.');
	}

	/** @param array<string,array<string,mixed>> $classifications @return array<string,mixed> */
	private function findClassificationByName(array $classifications, string $name): array
	{
		foreach ($classifications as $classification) {
			if (($classification['name'] ?? null) === $name) {
				return $classification;
			}
		}
		throw new RuntimeException('Missing classification for `' . $name . '`.');
	}

	/** @param array<string,array<string,mixed>> $classifications @return array<string,mixed> */
	private function findClassificationByKind(array $classifications, string $kind): array
	{
		foreach ($classifications as $classification) {
			if (($classification['kind'] ?? null) === $kind) {
				return $classification;
			}
		}
		throw new RuntimeException('Missing classification kind `' . $kind . '`.');
	}

	/** @param array<string,array<string,mixed>> $classifications @return array<string,mixed> */
	private function findClassificationByTarget(array $classifications, string $target): array
	{
		foreach ($classifications as $classification) {
			if (($classification['target'] ?? null) === $target) {
				return $classification;
			}
		}
		throw new RuntimeException('Missing classification target `' . $target . '`.');
	}

	private function mkdir(string $path): void
	{
		if (!is_dir($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
			throw new RuntimeException('Failed to create directory: ' . $path);
		}
	}

	private function write(string $path, string $contents): void
	{
		$this->mkdir(dirname($path));
		if (file_put_contents($path, $contents) === false) {
			throw new RuntimeException('Failed to write file: ' . $path);
		}
	}

	private function removeTree(string $path): void
	{
		if (!is_dir($path)) {
			return;
		}
		$items = scandir($path);
		if ($items === false) {
			return;
		}
		foreach ($items as $item) {
			if ($item === '.' || $item === '..') {
				continue;
			}
			$child = $path . '/' . $item;
			if (is_dir($child) && !is_link($child)) {
				$this->removeTree($child);
				continue;
			}
			@unlink($child);
		}
		@rmdir($path);
	}
}

(new ScppJssFrontendFirstSliceTest())->run();
