<?php
namespace scpp\compiler;

// Reuse each payload fixture's independent value oracle and state constructors.
// Cross mode replaces its binary signature set; its original controls still run.
$cross_mode = ($argv[2] ?? '') === '--cross';
$cross_signatures = [];
$cross_recoveries = [];

function guarded_cross_pairs(string $left): array
{
	$pairs = [];
	foreach (['n','r','f','b'] as $right) {
		if ($left !== $right) { $pairs[] = [$left,$right]; }
	}
	return $pairs;
}

// Ordinary equality retains its domain; strict identity also admits distinct carriers.
function guarded_fixture_equality_pair(string $left, string $right, string $symbol = '=='): bool
{
	$payload=guarded_fixture_payload($left);
	$right_payload=guarded_fixture_payload($right);
	if (in_array($symbol,['===','!=='],true) && $payload!==$right_payload && in_array($payload,['bool','int','float'],true) && in_array($right_payload,['bool','int','float'],true)) { return true; }
	if ($payload!==$right_payload || !in_array($payload,['int','float','bool','string'],true)) { return false; }
	$carriers=['?'.$payload,'result<'.$payload.'>'];
	$carriers[]='result_or_false<'.$payload.'>'; $carriers[]='result_or_bool<'.$payload.'>';
	return (in_array($symbol,['===','!=='],true) && in_array($left,$carriers,true) && in_array($right,$carriers,true))
		|| ($left===$right && in_array($left,$carriers,true))
		|| ($left===$payload && in_array($right,$carriers,true))
		|| ($right===$payload && in_array($left,$carriers,true));
}

function guarded_cross_probe(string $lt, string $rt, string $left_type, string $right_type,
	string $name, string $symbol, string $ret, string $expected, string $reach,
	string $equal, string $literal, bool $mixed, string &$program, string &$observers): void
{
	global $cross_signatures, $cross_recoveries;
	$id = $lt . $rt . $name;
	$cross_signatures[] = [$left_type, $symbol, $right_type, $ret];
	$le = in_array($left_type, ['int','float','bool','string'], true) ? 0 : ['n'=>1,'r'=>2,'f'=>3,'b'=>6][$lt[0]];
	$re = in_array($right_type, ['int','float','bool','string'], true) ? 0 : ['n'=>1,'r'=>2,'f'=>3,'b'=>6][$rt[0]];
	$operror = $name === 'div' ? '(b==0?4:0)' : ($name === 'mod' ? '(b==0?5:0)' : '0');
	$valid = $equal === 'integer' ? 'valid("'.$name.'",a,b)' : 'true';
	$compare = $equal === 'integer' ? '(out.native_value()=='.$expected.')' : $equal.'(out.native_value(),'.$expected.')';
	$program .= 'class CrossBox'.$id.' { public '.$left_type.' $value = '.$literal.'; } '
		. 'function crossreset'.$id.'('.$left_type.' &$a, '.$left_type.' $replacement, '.$right_type.' $b, int &$calls): '.$right_type.' { $calls++; $a=$replacement; return $b; } '
		. 'function crossboxreset'.$id.'(CrossBox'.$id.' $box, '.$left_type.' $replacement, '.$right_type.' $b, int &$calls): '.$right_type.' { $calls++; $box->value=$replacement; return $b; } '
		. 'function crosssnap'.$id.'('.$left_type.' &$a, '.$left_type.' $replacement, '.$right_type.' $b, int &$calls): '.$ret.' { return ($a '.$symbol.' crossreset'.$id.'($a,$replacement,$b,$calls)); } '
		. 'function crossprop'.$id.'('.$left_type.' $a, '.$left_type.' $replacement, '.$right_type.' $b, int &$calls, '.$left_type.' &$final): '.$ret.' { $box=new CrossBox'.$id.'(); $box->value=$a; $out=($box->value '.$symbol.' crossboxreset'.$id.'($box,$replacement,$b,$calls)); $final=$box->value; return $out; } ';
	// An unavailable left operand must prevent the replacement. A failing right
	// extraction occurs after the replacement and must leave that effect visible.
	$replacements = in_array($left_type, ['int','float','bool','string'], true) ? 'states'.$lt : (str_contains($left_type, 'string') ? 'replacements'.$lt : 'empty'.$lt);
	$state_prefix = $name === 'cmp' && guarded_fixture_payload($left_type) === 'int' ? 'boundary' : 'states';
	$observers .= 'for(auto sa:'.$state_prefix.$lt.') for(auto sb:'.$state_prefix.$rt.') for(auto replacement:'.$replacements.') { const auto& a=sa.value; const auto& b=sb.value; if(!('.$valid.')) continue; auto av=make'.$lt.'(sa); auto bv=make'.$rt.'(sb); auto rv=make'.$lt.'(replacement); I calls{0}; int expected_error=!sa.payload()?'.$le.':0; bool reached=expected_error==0 && ('.$reach.'); if(reached) expected_error=!sb.payload()?'.$re.':'.$operror.'; int failure=0; try { auto out=function_crosssnap'.$id.'(av,rv,bv,calls); if(expected_error==0 && !'.$compare.') return 60; } catch(const std::exception& e) { failure=classify(e); } if(failure!=expected_error || calls.native_value()!=(reached?1:0) || !same(av,reached?replacement:sa) || !same(bv,sb) || !same(rv,replacement)) return 61; ++cross_snapshot_cases; if(expected_error==0) { calls=I{0}; auto final=make'.$lt.'(sa); auto out=function_crossprop'.$id.'(make'.$lt.'(sa),rv,bv,calls,final); if(!'.$compare.' || calls.native_value()!=(reached?1:0) || !same(final,reached?replacement:sa)) return 62; ++cross_property_cases; } }' . "\n";
	$key = $left_type . ':' . $right_type;
	if (isset($cross_recoveries[$key])) { return; }
	$cross_recoveries[$key] = true;
	foreach (['==','!=','===','!==','<=>','xor'] as $excluded) {
		$lp = guarded_fixture_payload($left_type); $rp = guarded_fixture_payload($right_type);
		if (in_array($excluded, ['==','!=','===','!=='], true) && guarded_fixture_equality_pair($left_type, $right_type, $excluded)) { continue; }
		if ($excluded === 'xor' && (($lp === 'bool' && $rp === 'bool') || (in_array($lp, ['int','float'], true) && in_array($rp, ['int','float'], true)))) { continue; }
		if ($excluded === '<=>' && $lp === $rp && in_array($lp, ['int','string'], true)) { continue; }
		if ($left_type === $lp && $right_type === $rp) { continue; }
		guarded_cross_reject('function bad('.$left_type.' $a, '.$right_type.' $b): void { $out=($a '.$excluded.' $b); }');
	}
	if ($mixed) {
		foreach (['%','**','&','|','^','<<','>>'] as $excluded) {
			guarded_cross_reject('function bad('.$left_type.' $a, '.$right_type.' $b): void { $out=($a '.$excluded.' $b); }');
		}
	}
	guarded_cross_reject('function bad('.$left_type.' $a, '.$right_type.' $b): void { $out=($a '.$symbol.' $b); false && missing(); }');
	$directory = sys_get_temp_dir().'/scpp_guarded_cross_'.bin2hex(random_bytes(6));
	mkdir($directory); $path=$directory.'/main.phs';
	$valid_source = 'function probe('.$left_type.' $a, '.$right_type.' $b): void { $out=($a '.$symbol.' $b); }';
	try {
		file_put_contents($path,$valid_source); Compiler_Lifecycle::reset(); $compiler=new Compiler(); $compiler->init([$directory]); $compiler->exec_cpp();
		file_put_contents($path,str_replace('($a '.$symbol.' $b)', 'missing()', $valid_source));
		foreach ([[$path],[]] as $changes) {
			$failed=false; try { $compiler->update_cpp($changes); } catch (\RuntimeException $e) { $failed=true; }
			if (!$failed || !Model::$cpp_files->is_empty() || !Model::$prepared_files->is_empty()) { throw new \LogicException('Cross-family failure retained artifacts'); }
		}
		file_put_contents($path,str_replace('($a '.$symbol.' $b)','($b '.$symbol.' $a)',$valid_source)); $compiler->update_cpp([$path]);
		$output=CPP_Probe::text(Model::$cpp_files); $compiler->update_cpp([]);
		if ($output!==CPP_Probe::text(Model::$cpp_files)) { throw new \LogicException('Cross-family no-op changed output'); }
		Compiler_Lifecycle::reset(); $compiler->init([$directory]); $compiler->exec_cpp();
		if ($output!==CPP_Probe::text(Model::$cpp_files)) { throw new \LogicException('Cross-family repair differs from fresh'); }
	} finally { unlink($path); rmdir($directory); }
}

function guarded_cross_reject(string $source): void
{
	$failed=false;
	try { operator_test_source($source); } catch (\RuntimeException $e) { $failed=true; }
	if (!$failed || !Model::$cpp_files->is_empty() || !Model::$prepared_files->is_empty()) {
		throw new \LogicException('Cross-family exclusion failed: '.$source);
	}
}

function guarded_cross_finish(string $directory): void
{
	global $cross_signatures, $cross_recoveries;
	file_put_contents($directory.'/cross-signatures.json',json_encode(['signatures'=>$cross_signatures,'recovery_sequences'=>count($cross_recoveries)],JSON_PRETTY_PRINT)."\n");
	echo 'Cross-family binary signatures: '.count($cross_signatures)."\n";
}

/** Exact test-domain payload identity, independent of family name substrings. */
function guarded_fixture_payload(string $type): string
{
	foreach (['int','string','bool','float'] as $payload) {
		if (in_array($type, [$payload,'?'.$payload,'result<'.$payload.'>','result_or_false<'.$payload.'>','result_or_bool<'.$payload.'>'], true)) { return $payload; }
	}
	return '';
}

function guarded_spaceship_exclusions(string $payload): void
{
	$formats = ['%s','?%s','result<%s>','result_or_false<%s>','result_or_bool<%s>'];
	foreach ($formats as $left_format) foreach ($formats as $right_format) {
		if ($left_format === '%s' && $right_format === '%s') { continue; }
		foreach (['int16','uint32','float','bool', $payload === 'int' ? 'string' : 'int'] as $other) {
			$left = sprintf($left_format, $payload); $right = sprintf($right_format, $other);
			guarded_cross_reject('function bad('.$left.' $a, '.$right.' $b): void { $out=($a <=> $b); }');
			guarded_cross_reject('function bad('.$right.' $a, '.$left.' $b): void { $out=($a <=> $b); }');
		}
		foreach (['int16','uint32','float','bool'] as $other) {
			guarded_cross_reject('function bad('.sprintf($left_format,$other).' $a, '.sprintf($right_format,$other).' $b): void { $out=($a <=> $b); }');
		}
	}
}
