<?php
declare(strict_types=1);
namespace load_runtime;

/** Validated physical metadata, before resource/lifetime/language-owner acceptance. */
final class Package_Type_Measurement {
    public function __construct(public readonly string $id, public readonly Runtime_Storage $storage,
        public readonly ?int $integer_bits, public readonly ?bool $signed) {}
}

/** Package type ingestion owns measurement validation; layout grants no semantic permissions. */
final class Package_Type_Import {
    public static function measure(\scpp\Json_View $row): Package_Type_Measurement {
        if ($row->kind() !== 'object') { throw new \RuntimeException('Expected runtime type record'); }
        $id = Package_Syntax::identifier($row->member('id'));
        $spelling = $row->member('kind')->text();
        $kind = -1;
        if ($spelling === 'integer') { $kind = \load_runtime\RUNTIME_STORAGE_INTEGER; }
        elseif ($spelling === 'address') { $kind = \load_runtime\RUNTIME_STORAGE_ADDRESS; }
        elseif ($spelling === 'runtime_value') { $kind = \load_runtime\RUNTIME_STORAGE_OPAQUE; }
        elseif ($spelling === 'value_record') { $kind = \load_runtime\RUNTIME_STORAGE_RECORD; }
        elseif ($spelling === 'byte_span') { $kind = \load_runtime\RUNTIME_STORAGE_BYTE_SPAN; }
        elseif ($spelling === 'void') { $kind = \load_runtime\RUNTIME_STORAGE_VOID; }
        else { throw new \RuntimeException('Unsupported runtime type kind'); }
        $size = 0;
        if ($kind !== \load_runtime\RUNTIME_STORAGE_VOID) { $size = Package_Syntax::positive($row->member('size_bytes'), 'size'); }
        $alignment = Package_Syntax::positive($row->member('alignment_bytes'), 'alignment');
        // Bounded doubling checks power-of-two alignment without overflow or bitwise syntax.
        $power = 1;
        while ($power < $alignment) {
            if ($power > $alignment - $power) { throw new \RuntimeException('Invalid runtime storage contract'); }
            $power = $power * 2;
        }
        if (($size % $alignment) !== 0) { throw new \RuntimeException('Invalid runtime storage contract'); }
        if ($kind === \load_runtime\RUNTIME_STORAGE_OPAQUE) {
            if ($row->member('storage')->text() !== 'inline') { throw new \RuntimeException('Invalid runtime storage contract'); }
        }
        $storage = new Runtime_Storage($kind, $size, $alignment);
        if ($kind === \load_runtime\RUNTIME_STORAGE_INTEGER) {
            $bits = Package_Syntax::positive($row->member('value_bits'), 'integer width');
            $signed_value = $row->member('signed')->boolean();
            // floor(INT64_MAX / 8): larger byte counts accommodate every supported int width.
            // Avoid the prototype's size*8 promotion to float and native signed overflow.
            if ($size < 1152921504606846976) {
                if ($bits > $size * 8) { throw new \RuntimeException('Invalid runtime integer contract'); }
            }
            return new Package_Type_Measurement($id, $storage, $bits, $signed_value);
        }
        return new Package_Type_Measurement($id, $storage, null, null);
    }
}
