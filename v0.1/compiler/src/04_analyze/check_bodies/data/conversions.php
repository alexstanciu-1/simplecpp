<?php
declare(strict_types=1);
namespace check_bodies;
const CONVERSION_IDENTITY = 1;
const CONVERSION_PRIMITIVE = 2;
const CONVERSION_PROVIDER_CALL = 3;
/** Fixed canonical IDs and semantic purpose; never a search request for conversion chains. */
final class Conversion_Request {
    public function __construct(public readonly int $source_type, public readonly int $destination_type,
        public readonly int $purpose) { \type_model\Callable_Modes::conversion_name($purpose); }
}
/** Identity, one primitive, or one provider target; alternatives are mutually exclusive. */
final class Conversion_Selection {
    public function __construct(public readonly int $form, public readonly int $primitive = 0,
        public readonly int $callable_id = 0) {
        if (($form < \check_bodies\CONVERSION_IDENTITY) || ($form > \check_bodies\CONVERSION_PROVIDER_CALL)) { throw new \InvalidArgumentException('Invalid conversion form'); }
        if (($primitive !== 0) && ($primitive !== \check_bodies\CONVERSION_INTEGER_WIDEN)) { throw new \InvalidArgumentException('Invalid primitive conversion'); }
        if (($form === \check_bodies\CONVERSION_PRIMITIVE) !== ($primitive !== 0)) { throw new \InvalidArgumentException('Invalid conversion selection'); }
        if (($form === \check_bodies\CONVERSION_PROVIDER_CALL) !== ($callable_id > 0)) { throw new \InvalidArgumentException('Invalid conversion selection'); }
        if ($callable_id < 0) { throw new \InvalidArgumentException('Invalid conversion selection'); }
    }
}
