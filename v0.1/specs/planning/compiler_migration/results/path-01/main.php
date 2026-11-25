<?php
// <scpp-imports>
use function scpp\take_nullable as take_nullable;
use function scpp\take_false as take_false;
use function scpp\take_bool as take_bool;
// </scpp-imports>

$coordinator = new \compile\Update_Context();
$worker = $coordinator;
echo $worker->full_rebuild ? "initial=1\n" : "initial=0\n";

// Both PHP class assignment and native shared handles retain the same decision.
$coordinator->full_rebuild = true;
echo $worker->full_rebuild ? "shared=1\n" : "shared=0\n";

// A new update owns a fresh decision, independent of the previous update.
$next = new \compile\Update_Context();
echo $next->full_rebuild ? "next=1\n" : "next=0\n";
echo $worker->full_rebuild ? "previous=1\n" : "previous=0\n";

// Rebinding one handle does not replace the object held by other consumers.
$coordinator = $next;
echo $coordinator->full_rebuild ? "rebound=1\n" : "rebound=0\n";
echo $worker->full_rebuild ? "retained=1\n" : "retained=0\n";

// Real tokenizer rows retain the adopted reference semantics in this slice.
$row = new \tokenize\token();
echo $row->kind === \tokenize\token_kind::invalid ? "default=1\n" : "default=0\n";
echo $row->start, ":", $row->length, "\n";
$alias = $row;
$row->kind = \tokenize\token_kind::identifier;
$row->start = 7;
$row->length = 3;
echo $alias->kind === \tokenize\token_kind::identifier ? "token-shared=1\n" : "token-shared=0\n";
echo $alias->start, ":", $alias->length, "\n";
$fresh = new \tokenize\token();
echo $fresh->kind !== $row->kind ? "independent=1\n" : "independent=0\n";

$state = \compile\step_status::created;
echo $state === \compile\step_status::created ? "created=1\n" : "created=0\n";
$state = \compile\step_status::finished;
echo $state !== \compile\step_status::failed ? "finished=1\n" : "finished=0\n";
echo \read_sources\Source_Path_Syntax::join("a", "b"), "\n";
echo \read_sources\Source_Path_Syntax::join("a/", "b"), "\n";
echo \read_sources\Source_Path_Syntax::join("", "x"), "\n";
echo \read_sources\Source_Path_Syntax::join("/", "x"), "\n";
echo \read_sources\Source_Path_Syntax::join("a", ""), "\n";
echo \read_sources\Source_Path_Syntax::is_absolute("", false) ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_absolute("", true) ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_absolute("/", false) ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_absolute("/", true) ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_absolute("x", false) ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_absolute("x", true) ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_absolute("C:", false) ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_absolute("C:", true) ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_absolute("C:/x", false) ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_absolute("C:/x", true) ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_absolute("C:\\x", false) ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_absolute("C:\\x", true) ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_absolute("\\server\\x", false) ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_absolute("\\server\\x", true) ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_absolute("/a", false) ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_absolute("/a", true) ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_absolute("a/b", false) ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_absolute("a/b", true) ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_absolute("C:x", false) ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_absolute("C:x", true) ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_source("a.phs") ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_source(".phs") ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_source("a.php") ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_source("a.PHS") ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_source("a.phs/x") ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_source("") ? "1\n" : "0\n";
