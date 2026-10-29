<?php
 declare(strict_types=1);
namespace Scpp\Api;
require_once __DIR__.'/input.php';
require_once __DIR__.'/../php/scalars.php';
require_once __DIR__.'/../php/families.php';
require_once __DIR__.'/../php/render.php';
require_once __DIR__.'/../php/render_sequences.php';
require_once __DIR__.'/../php/render_modules.php';

$root = dirname(__DIR__, 3);
$metadata = readFile($root.'/runtime/metadata/runtime.json');
$hashes = readFile($root.'/tests/hash/publication.json');
$datetime = readFile($root.'/tests/datetime/publication.json');
$owners = readFile($root.'/tests/ownership/publication.json');
$prepare = fn(string $m, string $p): array => prepareRuntime(decode($m,'metadata'), decode($p,'publication'), $facts);
$hashPlan = $prepare($metadata, $hashes);
requireThat(count($hashPlan['hashes']['specializations']) === 4, 'Hash specializations lost');
foreach (['key'=>'int8','value'=>'hash_int64_int64'] as $key=>$value) {
    $p = decode($hashes,'publication'); $p->hashes->specializations[0]->{$key} = $value;
    rejects(fn() => $prepare($metadata,encode($p)));
}
foreach (['carriers','string'] as $key) {
    $p = decode($hashes,'publication'); unset($p->{$key});
    rejects(fn() => $prepare($metadata,encode($p)));
}
$p = decode($hashes,'publication');
$p->hashes->specializations[] = $p->hashes->specializations[0];
rejects(fn() => $prepare($metadata,encode($p)), 'Duplicate hash');
$m = decode($metadata,'metadata'); $m->hash->key_policy = 'coerce';
rejects(fn() => $prepare(encode($m),$hashes));
$p = decode($hashes,'publication'); unset($p->string);
$p->hashes->specializations = [(object)['key'=>'int64','value'=>'int64']];
$p->carriers->specializations = [(object)['family'=>'nullable','payload'=>'int64']];
requireThat(count($prepare($metadata,encode($p))['hashes']['specializations']) === 1, 'Integer-only hash publication failed');

$dt = $prepare($metadata,$datetime);
requireThat(count($dt['datetime']['operations']) === 11, 'Datetime operations lost');
requireThat($dt['datetime']['build']['requires'] === 'scpp_runtime', 'Datetime dependency lost');
foreach (['carriers','string'] as $key) {
    $p = decode($datetime,'publication'); unset($p->{$key});
    rejects(fn() => $prepare($metadata,encode($p)));
}
foreach ([[],['unknown'],['sleep_millis','sleep_millis']] as $operations) {
    $p = decode($datetime,'publication'); $p->datetime->operations = $operations;
    rejects(fn() => $prepare($metadata,encode($p)));
}
$m = decode($metadata,'metadata'); $m->datetime->local_dst = 'reject_gaps';
rejects(fn() => $prepare(encode($m),$datetime));
$p = decode($datetime,'publication');
$p->datetime->operations = ['monotonic_nanos']; $p->types = ['uint64']; unset($p->string,$p->carriers);
requireThat(count($prepare($metadata,encode($p))['datetime']['operations']) === 1, 'Clock-only publication failed');

// Preparation and emission must both use the supplied native layouts.
$ownerPlan = prepareRuntime(decode($metadata,'metadata'),decode($owners,'publication'),$prepared);
$artifacts = render($ownerPlan);
requireThat(str_contains($artifacts['src/ownership.cpp'],'== 32 && alignof(std::shared_ptr<'), 'Emitter ignored supplied ownership layout');
requireThat(str_contains($artifacts['public/'.$ownerPlan['ownership']['header']],'alignas(16) unsigned char storage_[32]'), 'Public ownership layout drifted');
echo "Hash, datetime and target-layout publication contracts passed\n";
