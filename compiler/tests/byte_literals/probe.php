<?php
declare(strict_types=1);
namespace byte_literals_test;
final class Probe {
    private static function from_hex(string $hex): string {
        $bytes = '';
        for ($i = 0; $i < string_byte_len($hex); $i = $i+2) {
            $high = string_byte_at($hex,$i); $low = string_byte_at($hex,$i+1);
            if ($high < 58) { $high = $high-48; } else { $high = $high-87; }
            if ($low < 58) { $low = $low-48; } else { $low = $low-87; }
            $bytes .= string_byte_from_int($high*16+$low);
        }
        return $bytes;
    }
    public static function run(string $text): void {
        $cases = json_read($text);
        for ($i = 0; $i < $cases->size(); $i++) {
            $row = $cases->at($i); $input = Probe::from_hex($row->member('hex')->text());
            $expected = $row->member('expected')->text(); $success = $row->member('valid')->boolean();
            $actual = ''; $failed = false;
            try { $actual = \check_bodies\Byte_Literals::decode($input); }
            catch (\InvalidArgumentException $error) { $actual = $error->getMessage(); $failed = true; }
            if ($success) { $expected = Probe::from_hex($expected); }
            $valid = ($failed !== $success) && ($actual === $expected);
            echo $valid ? "true\n" : "false\n";
        }
    }
}
