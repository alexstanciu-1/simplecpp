function evaluateFact(constant_fact $source, bool $accept):constant_fact {
    if (!$accept || $source->kind === UNKNOWN) { return unavailableFact(); }
    return $source;
}

function agrees(constant_fact $fact, bool $known, int $expected):bool {
    if (!$known) { return $fact->kind === UNKNOWN; }
    return $fact->kind === INTEGER && $fact->integerValue === $expected;
}

function checkPropagation():bool {
    $local integer_slot;
    $named integer_slot;
    $i = 0;
    while ($i < 4) {
        $accept = $i % 2 === 1;
        $cast = evaluateFact(intFact(8), $accept);
        if (!agrees($cast, $accept, 8)) { return false; }
        $local->settle($cast);
        if (!agrees($local->read(), $accept, 8)) { return false; }
        $local->settle(evaluateFact(intFact(9), $accept));
        if (!agrees($local->read(), $accept, 9)) { return false; }
        $named->settle(evaluateFact(intFact(8), $accept));
        if (!agrees($named->read(), $accept, 8)) { return false; }
        $local->settle(unavailableFact());
        $named->settle(unavailableFact());
        if ($local->known || $named->known) { return false; }
        $i++;
    }
    return true;
}

if (!checkPropagation()) { return 1; }
return 8;
