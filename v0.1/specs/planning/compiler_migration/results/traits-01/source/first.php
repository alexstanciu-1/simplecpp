<?php
declare(strict_types=1);
namespace demo;
// <scpp-imports>
use function scpp\same_exception as same_exception;
use function scpp\string_byte_at as string_byte_at;
use function scpp\take_nullable as take_nullable;
use function scpp\take_false as take_false;
use function scpp\take_bool as take_bool;
use function scpp\string_byte_slice as string_byte_slice;
// </scpp-imports>
class First { use Operations; public int $value = 1; }
