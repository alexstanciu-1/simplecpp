const byte UNKNOWN = 0;
const byte KNOWN_NULL = 1;
const byte BOOLEAN = 2;
const byte INTEGER = 3;
const byte FLOATING = 4;

struct constant_fact {
    public byte $kind;
    public bool $booleanValue;
    public int $integerValue;
    public float $floatValue;
}

function unavailableFact():constant_fact {
    $fact constant_fact;
    return $fact;
}
function nullFact():constant_fact {
    $fact constant_fact;
    $fact->kind = KNOWN_NULL;
    return $fact;
}
function boolFact(bool $value):constant_fact {
    $fact constant_fact;
    $fact->kind = BOOLEAN;
    $fact->booleanValue = $value;
    return $fact;
}
function intFact(int $value):constant_fact {
    $fact constant_fact;
    $fact->kind = INTEGER;
    $fact->integerValue = $value;
    return $fact;
}
function floatFact(float $value):constant_fact {
    $fact constant_fact;
    $fact->kind = FLOATING;
    $fact->floatValue = $value;
    return $fact;
}

function evaluateFact(constant_fact $source, bool $accept):constant_fact {
    if (!$accept || $source->kind === UNKNOWN) { return unavailableFact(); }
    return $source;
}

// Model the known bit and scalar value already retained on an integer binding.
struct integer_slot {
    public bool $known;
    public int $value;
    public function settle(constant_fact $fact):void {
        $this->known = $fact->kind === INTEGER;
        if ($this->known) { $this->value = $fact->integerValue; }
        else { $this->value = 0; }
    }
    public function read():constant_fact {
        if ($this->known) { return intFact($this->value); }
        return unavailableFact();
    }
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
