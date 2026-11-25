<?php
declare(strict_types=1);

if ($argc !== 2) { echo "usage: settings-patch JSON\n"; exit(2); }
try {
    $patch = json_decode($argv[1], flags: JSON_THROW_ON_ERROR);
} catch (JsonException $e) {
    echo "invalid JSON\n";
    exit(1);
}
$settings = ['enabled' => true, 'limit' => 10];
try {
    if (!is_object($patch)) { throw new DomainException('expected object'); }
    foreach ($patch as $key => $value) {
        if ($key === 'enabled') {
            if (!is_bool($value)) { throw new DomainException('invalid enabled'); }
        } elseif ($key === 'limit') {
            if ($value !== null && (!is_int($value) || $value < 0 || $value > 100)) {
                throw new DomainException('invalid limit');
            }
        } else { throw new DomainException('unknown field'); }
        $settings[$key] = $value;
    }
} catch (DomainException $e) {
    echo $e->getMessage(), "\n";
    exit(1);
}
echo json_encode($settings, JSON_THROW_ON_ERROR), "\n";
