<?php
namespace exception_identity;

final class Signal_Owner {
    private \RuntimeException $signal;
    public static \RuntimeException $published;
    public ?\RuntimeException $optional = null;

    public function __construct() {
        $this->signal = new \RuntimeException('yield');
        self::$published = $this->signal;
        $this->optional = $this->signal;
    }

    public function raise(): void { throw $this->signal; }
    public function matches(\Throwable $error): bool {
        return same_exception($error, $this->signal);
    }
}

$owner = new Signal_Owner();
try {
    $owner->raise();
} catch (\RuntimeException $error) {
    echo $owner->matches($error) ? 'same' : 'bad';
    echo $owner->matches(new \RuntimeException('yield')) ? ':bad' : ':distinct';
    echo same_exception($error, Signal_Owner::$published) ? ':static' : ':bad';
    echo same_exception($error, $owner->optional) ? ':optional' : ':bad';
    try { throw $error; }
    catch (\Exception $again) { echo $owner->matches($again) ? ':rethrow' : ':bad'; }
}
