<?php
declare(strict_types=1);
// <scpp-imports>
use function scpp\string_byte_len as string_byte_len;
use function scpp\string_byte_starts_with as string_byte_starts_with;
use function scpp\string_byte_ends_with as string_byte_ends_with;
use function scpp\string_utf8_is_valid as string_utf8_is_valid;
use function scpp\string_codepoint_at as string_codepoint_at;
use function scpp\compat\substr as substr;
use function scpp\compat\strpos as strpos;
use function scpp\compat\strrpos as strrpos;
use function scpp\same_exception as same_exception;
use function scpp\string_byte_at as string_byte_at;
use function scpp\take_nullable as take_nullable;
use function scpp\take_false as take_false;
use function scpp\take_bool as take_bool;
use function scpp\compat\str_starts_with as str_starts_with;
use function scpp\compat\str_ends_with as str_ends_with;
use function scpp\compat\strlen as strlen;
use function scpp\string_byte_slice as string_byte_slice;
// </scpp-imports>
$position /** int */ = -9;
echo strlen(""), "\n";
echo string_byte_len(""), "\n";
echo string_utf8_is_valid("") ? 1 : 0, "\n";
echo string_codepoint_at("", -1), "\n";
echo string_codepoint_at("", 0), "\n";
echo string_codepoint_at("", 1), "\n";
echo string_codepoint_at("", 0), "\n";
echo string_codepoint_at("", 1), "\n";
echo substr("", -20), "\n";
echo substr("", -20, -2), "\n";
echo substr("", -20, 0), "\n";
echo substr("", -20, 2), "\n";
echo substr("", -20, 20), "\n";
echo substr("", -1), "\n";
echo substr("", -1, -2), "\n";
echo substr("", -1, 0), "\n";
echo substr("", -1, 2), "\n";
echo substr("", -1, 20), "\n";
echo substr("", 0), "\n";
echo substr("", 0, -2), "\n";
echo substr("", 0, 0), "\n";
echo substr("", 0, 2), "\n";
echo substr("", 0, 20), "\n";
echo substr("", 1), "\n";
echo substr("", 1, -2), "\n";
echo substr("", 1, 0), "\n";
echo substr("", 1, 2), "\n";
echo substr("", 1, 20), "\n";
echo substr("", 20), "\n";
echo substr("", 20, -2), "\n";
echo substr("", 20, 0), "\n";
echo substr("", 20, 2), "\n";
echo substr("", 20, 20), "\n";
echo strlen("ascii"), "\n";
echo string_byte_len("ascii"), "\n";
echo string_utf8_is_valid("ascii") ? 1 : 0, "\n";
echo string_codepoint_at("ascii", -1), "\n";
echo string_codepoint_at("ascii", 0), "\n";
echo string_codepoint_at("ascii", 1), "\n";
echo string_codepoint_at("ascii", 5), "\n";
echo string_codepoint_at("ascii", 6), "\n";
echo substr("ascii", -20), "\n";
echo substr("ascii", -20, -2), "\n";
echo substr("ascii", -20, 0), "\n";
echo substr("ascii", -20, 2), "\n";
echo substr("ascii", -20, 20), "\n";
echo substr("ascii", -1), "\n";
echo substr("ascii", -1, -2), "\n";
echo substr("ascii", -1, 0), "\n";
echo substr("ascii", -1, 2), "\n";
echo substr("ascii", -1, 20), "\n";
echo substr("ascii", 0), "\n";
echo substr("ascii", 0, -2), "\n";
echo substr("ascii", 0, 0), "\n";
echo substr("ascii", 0, 2), "\n";
echo substr("ascii", 0, 20), "\n";
echo substr("ascii", 1), "\n";
echo substr("ascii", 1, -2), "\n";
echo substr("ascii", 1, 0), "\n";
echo substr("ascii", 1, 2), "\n";
echo substr("ascii", 1, 20), "\n";
echo substr("ascii", 20), "\n";
echo substr("ascii", 20, -2), "\n";
echo substr("ascii", 20, 0), "\n";
echo substr("ascii", 20, 2), "\n";
echo substr("ascii", 20, 20), "\n";
echo strlen("é中😀"), "\n";
echo string_byte_len("é中😀"), "\n";
echo string_utf8_is_valid("é中😀") ? 1 : 0, "\n";
echo string_codepoint_at("é中😀", -1), "\n";
echo string_codepoint_at("é中😀", 0), "\n";
echo string_codepoint_at("é中😀", 1), "\n";
echo string_codepoint_at("é中😀", 3), "\n";
echo string_codepoint_at("é中😀", 4), "\n";
echo substr("é中😀", -20), "\n";
echo substr("é中😀", -20, -2), "\n";
echo substr("é中😀", -20, 0), "\n";
echo substr("é中😀", -20, 2), "\n";
echo substr("é中😀", -20, 20), "\n";
echo substr("é中😀", -1), "\n";
echo substr("é中😀", -1, -2), "\n";
echo substr("é中😀", -1, 0), "\n";
echo substr("é中😀", -1, 2), "\n";
echo substr("é中😀", -1, 20), "\n";
echo substr("é中😀", 0), "\n";
echo substr("é中😀", 0, -2), "\n";
echo substr("é中😀", 0, 0), "\n";
echo substr("é中😀", 0, 2), "\n";
echo substr("é中😀", 0, 20), "\n";
echo substr("é中😀", 1), "\n";
echo substr("é中😀", 1, -2), "\n";
echo substr("é中😀", 1, 0), "\n";
echo substr("é中😀", 1, 2), "\n";
echo substr("é中😀", 1, 20), "\n";
echo substr("é中😀", 20), "\n";
echo substr("é中😀", 20, -2), "\n";
echo substr("é中😀", 20, 0), "\n";
echo substr("é中😀", 20, 2), "\n";
echo substr("é中😀", 20, 20), "\n";
echo strlen("é"), "\n";
echo string_byte_len("é"), "\n";
echo string_utf8_is_valid("é") ? 1 : 0, "\n";
echo string_codepoint_at("é", -1), "\n";
echo string_codepoint_at("é", 0), "\n";
echo string_codepoint_at("é", 1), "\n";
echo string_codepoint_at("é", 2), "\n";
echo string_codepoint_at("é", 3), "\n";
echo substr("é", -20), "\n";
echo substr("é", -20, -2), "\n";
echo substr("é", -20, 0), "\n";
echo substr("é", -20, 2), "\n";
echo substr("é", -20, 20), "\n";
echo substr("é", -1), "\n";
echo substr("é", -1, -2), "\n";
echo substr("é", -1, 0), "\n";
echo substr("é", -1, 2), "\n";
echo substr("é", -1, 20), "\n";
echo substr("é", 0), "\n";
echo substr("é", 0, -2), "\n";
echo substr("é", 0, 0), "\n";
echo substr("é", 0, 2), "\n";
echo substr("é", 0, 20), "\n";
echo substr("é", 1), "\n";
echo substr("é", 1, -2), "\n";
echo substr("é", 1, 0), "\n";
echo substr("é", 1, 2), "\n";
echo substr("é", 1, 20), "\n";
echo substr("é", 20), "\n";
echo substr("é", 20, -2), "\n";
echo substr("é", 20, 0), "\n";
echo substr("é", 20, 2), "\n";
echo substr("é", 20, 20), "\n";
echo strlen("👩‍💻"), "\n";
echo string_byte_len("👩‍💻"), "\n";
echo string_utf8_is_valid("👩‍💻") ? 1 : 0, "\n";
echo string_codepoint_at("👩‍💻", -1), "\n";
echo string_codepoint_at("👩‍💻", 0), "\n";
echo string_codepoint_at("👩‍💻", 1), "\n";
echo string_codepoint_at("👩‍💻", 3), "\n";
echo string_codepoint_at("👩‍💻", 4), "\n";
echo substr("👩‍💻", -20), "\n";
echo substr("👩‍💻", -20, -2), "\n";
echo substr("👩‍💻", -20, 0), "\n";
echo substr("👩‍💻", -20, 2), "\n";
echo substr("👩‍💻", -20, 20), "\n";
echo substr("👩‍💻", -1), "\n";
echo substr("👩‍💻", -1, -2), "\n";
echo substr("👩‍💻", -1, 0), "\n";
echo substr("👩‍💻", -1, 2), "\n";
echo substr("👩‍💻", -1, 20), "\n";
echo substr("👩‍💻", 0), "\n";
echo substr("👩‍💻", 0, -2), "\n";
echo substr("👩‍💻", 0, 0), "\n";
echo substr("👩‍💻", 0, 2), "\n";
echo substr("👩‍💻", 0, 20), "\n";
echo substr("👩‍💻", 1), "\n";
echo substr("👩‍💻", 1, -2), "\n";
echo substr("👩‍💻", 1, 0), "\n";
echo substr("👩‍💻", 1, 2), "\n";
echo substr("👩‍💻", 1, 20), "\n";
echo substr("👩‍💻", 20), "\n";
echo substr("👩‍💻", 20, -2), "\n";
echo substr("👩‍💻", 20, 0), "\n";
echo substr("👩‍💻", 20, 2), "\n";
echo substr("👩‍💻", 20, 20), "\n";
echo strlen("􏿿"), "\n";
echo string_byte_len("􏿿"), "\n";
echo string_utf8_is_valid("􏿿") ? 1 : 0, "\n";
echo string_codepoint_at("􏿿", -1), "\n";
echo string_codepoint_at("􏿿", 0), "\n";
echo string_codepoint_at("􏿿", 1), "\n";
echo string_codepoint_at("􏿿", 1), "\n";
echo string_codepoint_at("􏿿", 2), "\n";
echo substr("􏿿", -20), "\n";
echo substr("􏿿", -20, -2), "\n";
echo substr("􏿿", -20, 0), "\n";
echo substr("􏿿", -20, 2), "\n";
echo substr("􏿿", -20, 20), "\n";
echo substr("􏿿", -1), "\n";
echo substr("􏿿", -1, -2), "\n";
echo substr("􏿿", -1, 0), "\n";
echo substr("􏿿", -1, 2), "\n";
echo substr("􏿿", -1, 20), "\n";
echo substr("􏿿", 0), "\n";
echo substr("􏿿", 0, -2), "\n";
echo substr("􏿿", 0, 0), "\n";
echo substr("􏿿", 0, 2), "\n";
echo substr("􏿿", 0, 20), "\n";
echo substr("􏿿", 1), "\n";
echo substr("􏿿", 1, -2), "\n";
echo substr("􏿿", 1, 0), "\n";
echo substr("􏿿", 1, 2), "\n";
echo substr("􏿿", 1, 20), "\n";
echo substr("􏿿", 20), "\n";
echo substr("􏿿", 20, -2), "\n";
echo substr("􏿿", 20, 0), "\n";
echo substr("􏿿", 20, 2), "\n";
echo substr("􏿿", 20, 20), "\n";
$position = -9;
if (take_false($position, strpos("é中😀é", "é", -4))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strrpos("é中😀é", "é", -4))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strpos("é中😀é", "é", -1))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strrpos("é中😀é", "é", -1))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strpos("é中😀é", "é", 0))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strrpos("é中😀é", "é", 0))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strpos("é中😀é", "é", 1))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strrpos("é中😀é", "é", 1))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strpos("é中😀é", "é", 4))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strrpos("é中😀é", "é", 4))) { echo $position, "\n"; } else { echo "F\n"; }
if (take_false($position, strpos("é中😀é", "é"))) { echo $position, "\n"; } else { echo "F\n"; }
if (take_false($position, strrpos("é中😀é", "é"))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strpos("é中😀é", "😀", -4))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strrpos("é中😀é", "😀", -4))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strpos("é中😀é", "😀", -1))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strrpos("é中😀é", "😀", -1))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strpos("é中😀é", "😀", 0))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strrpos("é中😀é", "😀", 0))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strpos("é中😀é", "😀", 1))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strrpos("é中😀é", "😀", 1))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strpos("é中😀é", "😀", 4))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strrpos("é中😀é", "😀", 4))) { echo $position, "\n"; } else { echo "F\n"; }
if (take_false($position, strpos("é中😀é", "😀"))) { echo $position, "\n"; } else { echo "F\n"; }
if (take_false($position, strrpos("é中😀é", "😀"))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strpos("é中😀é", "😀é", -4))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strrpos("é中😀é", "😀é", -4))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strpos("é中😀é", "😀é", -1))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strrpos("é中😀é", "😀é", -1))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strpos("é中😀é", "😀é", 0))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strrpos("é中😀é", "😀é", 0))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strpos("é中😀é", "😀é", 1))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strrpos("é中😀é", "😀é", 1))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strpos("é中😀é", "😀é", 4))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strrpos("é中😀é", "😀é", 4))) { echo $position, "\n"; } else { echo "F\n"; }
if (take_false($position, strpos("é中😀é", "😀é"))) { echo $position, "\n"; } else { echo "F\n"; }
if (take_false($position, strrpos("é中😀é", "😀é"))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strpos("é中😀é", "x", -4))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strrpos("é中😀é", "x", -4))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strpos("é中😀é", "x", -1))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strrpos("é中😀é", "x", -1))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strpos("é中😀é", "x", 0))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strrpos("é中😀é", "x", 0))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strpos("é中😀é", "x", 1))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strrpos("é中😀é", "x", 1))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strpos("é中😀é", "x", 4))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strrpos("é中😀é", "x", 4))) { echo $position, "\n"; } else { echo "F\n"; }
if (take_false($position, strpos("é中😀é", "x"))) { echo $position, "\n"; } else { echo "F\n"; }
if (take_false($position, strrpos("é中😀é", "x"))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strpos("é中😀é", "", -4))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strrpos("é中😀é", "", -4))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strpos("é中😀é", "", -1))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strrpos("é中😀é", "", -1))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strpos("é中😀é", "", 0))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strrpos("é中😀é", "", 0))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strpos("é中😀é", "", 1))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strrpos("é中😀é", "", 1))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strpos("é中😀é", "", 4))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strrpos("é中😀é", "", 4))) { echo $position, "\n"; } else { echo "F\n"; }
if (take_false($position, strpos("é中😀é", ""))) { echo $position, "\n"; } else { echo "F\n"; }
if (take_false($position, strrpos("é中😀é", ""))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strpos("", "", 0))) { echo $position, "\n"; } else { echo "F\n"; }
$position = -9;
if (take_false($position, strrpos("", "", 0))) { echo $position, "\n"; } else { echo "F\n"; }
if (take_false($position, strpos("", ""))) { echo $position, "\n"; } else { echo "F\n"; }
if (take_false($position, strrpos("", ""))) { echo $position, "\n"; } else { echo "F\n"; }
echo str_starts_with("é中", "é") ? 1 : 0, "\n";
echo str_ends_with("é中", "中") ? 1 : 0, "\n";
$bad = string_byte_slice("é", 1, 1);
echo string_utf8_is_valid($bad) ? 1 : 0, "\n";
try { $ignored = strlen($bad); echo "BAD\n"; } catch (\InvalidArgumentException $error) { echo $error->getMessage(), "\n"; }
try { $ignored = substr($bad, 0); echo "BAD\n"; } catch (\InvalidArgumentException $error) { echo $error->getMessage(), "\n"; }
try { $ignored = strpos("ok", $bad); echo "BAD\n"; } catch (\InvalidArgumentException $error) { echo $error->getMessage(), "\n"; }
try { $ignored = strrpos($bad, ""); echo "BAD\n"; } catch (\InvalidArgumentException $error) { echo $error->getMessage(), "\n"; }
try { $ignored = str_starts_with($bad, ""); echo "BAD\n"; } catch (\InvalidArgumentException $error) { echo $error->getMessage(), "\n"; }
try { $ignored = str_ends_with("ok", $bad); echo "BAD\n"; } catch (\InvalidArgumentException $error) { echo $error->getMessage(), "\n"; }
try { $ignored = string_codepoint_at($bad, -1); echo "BAD\n"; } catch (\InvalidArgumentException $error) { echo $error->getMessage(), "\n"; }
$bad = string_byte_slice("😀", 0, 3);
echo string_utf8_is_valid($bad) ? 1 : 0, "\n";
try { $ignored = strlen($bad); echo "BAD\n"; } catch (\InvalidArgumentException $error) { echo $error->getMessage(), "\n"; }
try { $ignored = substr($bad, 0); echo "BAD\n"; } catch (\InvalidArgumentException $error) { echo $error->getMessage(), "\n"; }
try { $ignored = strpos("ok", $bad); echo "BAD\n"; } catch (\InvalidArgumentException $error) { echo $error->getMessage(), "\n"; }
try { $ignored = strrpos($bad, ""); echo "BAD\n"; } catch (\InvalidArgumentException $error) { echo $error->getMessage(), "\n"; }
try { $ignored = str_starts_with($bad, ""); echo "BAD\n"; } catch (\InvalidArgumentException $error) { echo $error->getMessage(), "\n"; }
try { $ignored = str_ends_with("ok", $bad); echo "BAD\n"; } catch (\InvalidArgumentException $error) { echo $error->getMessage(), "\n"; }
try { $ignored = string_codepoint_at($bad, -1); echo "BAD\n"; } catch (\InvalidArgumentException $error) { echo $error->getMessage(), "\n"; }
$bad = string_byte_slice("ࠀ", 0, 1) . string_byte_slice("ࠀ", 2, 1) . string_byte_slice("ࠀ", 2, 1);
echo string_utf8_is_valid($bad) ? 1 : 0, "\n";
try { $ignored = strlen($bad); echo "BAD\n"; } catch (\InvalidArgumentException $error) { echo $error->getMessage(), "\n"; }
try { $ignored = substr($bad, 0); echo "BAD\n"; } catch (\InvalidArgumentException $error) { echo $error->getMessage(), "\n"; }
try { $ignored = strpos("ok", $bad); echo "BAD\n"; } catch (\InvalidArgumentException $error) { echo $error->getMessage(), "\n"; }
try { $ignored = strrpos($bad, ""); echo "BAD\n"; } catch (\InvalidArgumentException $error) { echo $error->getMessage(), "\n"; }
try { $ignored = str_starts_with($bad, ""); echo "BAD\n"; } catch (\InvalidArgumentException $error) { echo $error->getMessage(), "\n"; }
try { $ignored = str_ends_with("ok", $bad); echo "BAD\n"; } catch (\InvalidArgumentException $error) { echo $error->getMessage(), "\n"; }
try { $ignored = string_codepoint_at($bad, -1); echo "BAD\n"; } catch (\InvalidArgumentException $error) { echo $error->getMessage(), "\n"; }
$bad = string_byte_slice("퀀", 0, 1) . string_byte_slice("ࠀ", 1, 1) . string_byte_slice("ࠀ", 2, 1);
echo string_utf8_is_valid($bad) ? 1 : 0, "\n";
try { $ignored = strlen($bad); echo "BAD\n"; } catch (\InvalidArgumentException $error) { echo $error->getMessage(), "\n"; }
try { $ignored = substr($bad, 0); echo "BAD\n"; } catch (\InvalidArgumentException $error) { echo $error->getMessage(), "\n"; }
try { $ignored = strpos("ok", $bad); echo "BAD\n"; } catch (\InvalidArgumentException $error) { echo $error->getMessage(), "\n"; }
try { $ignored = strrpos($bad, ""); echo "BAD\n"; } catch (\InvalidArgumentException $error) { echo $error->getMessage(), "\n"; }
try { $ignored = str_starts_with($bad, ""); echo "BAD\n"; } catch (\InvalidArgumentException $error) { echo $error->getMessage(), "\n"; }
try { $ignored = str_ends_with("ok", $bad); echo "BAD\n"; } catch (\InvalidArgumentException $error) { echo $error->getMessage(), "\n"; }
try { $ignored = string_codepoint_at($bad, -1); echo "BAD\n"; } catch (\InvalidArgumentException $error) { echo $error->getMessage(), "\n"; }
$bad = string_byte_slice("􀀀", 0, 1) . string_byte_slice("𐀀", 1, 3);
echo string_utf8_is_valid($bad) ? 1 : 0, "\n";
try { $ignored = strlen($bad); echo "BAD\n"; } catch (\InvalidArgumentException $error) { echo $error->getMessage(), "\n"; }
try { $ignored = substr($bad, 0); echo "BAD\n"; } catch (\InvalidArgumentException $error) { echo $error->getMessage(), "\n"; }
try { $ignored = strpos("ok", $bad); echo "BAD\n"; } catch (\InvalidArgumentException $error) { echo $error->getMessage(), "\n"; }
try { $ignored = strrpos($bad, ""); echo "BAD\n"; } catch (\InvalidArgumentException $error) { echo $error->getMessage(), "\n"; }
try { $ignored = str_starts_with($bad, ""); echo "BAD\n"; } catch (\InvalidArgumentException $error) { echo $error->getMessage(), "\n"; }
try { $ignored = str_ends_with("ok", $bad); echo "BAD\n"; } catch (\InvalidArgumentException $error) { echo $error->getMessage(), "\n"; }
try { $ignored = string_codepoint_at($bad, -1); echo "BAD\n"; } catch (\InvalidArgumentException $error) { echo $error->getMessage(), "\n"; }
echo string_byte_len(string_byte_slice("é", 1, 1)), "\n";
echo string_byte_starts_with(string_byte_slice("é", 1, 1), string_byte_slice("é", 1, 1)) ? 1 : 0, "\n";
try { $ignored = strpos("é", "", 2); echo "BAD\n"; } catch (\OutOfBoundsException $error) { echo $error->getMessage(), "\n"; }
try { $ignored = strrpos("é", "", -2); echo "BAD\n"; } catch (\OutOfBoundsException $error) { echo $error->getMessage(), "\n"; }
