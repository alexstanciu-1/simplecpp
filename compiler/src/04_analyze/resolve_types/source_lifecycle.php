<?php
declare(strict_types=1);
namespace resolve_types;
/** Source lifecycle spelling. Body/signature validation remains concrete preparation work. */
final class Source_Lifecycle {
    public static function role(\collect_symbols\Symbol_Record $symbol): int {
        if ($symbol->owner_symbol_id === 0) { return \type_model\LIFECYCLE_NONE; }
        if (!$symbol->is_source()) { return \type_model\LIFECYCLE_NONE; }
        $name = $symbol->name;
        if ($name === '__construct') { return \type_model\LIFECYCLE_DEFAULT; }
        if ($name === '__destruct') { return \type_model\LIFECYCLE_DESTROY; }
        if ($name === '__copy_construct') { return \type_model\LIFECYCLE_COPY; }
        if ($name === '__copy_assign') { return \type_model\LIFECYCLE_ASSIGN; }
        return \type_model\LIFECYCLE_NONE;
    }
}
