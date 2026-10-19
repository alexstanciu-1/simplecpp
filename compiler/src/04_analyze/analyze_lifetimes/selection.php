<?php
declare(strict_types=1);
namespace analyze_lifetimes;
final class Lifetime_Selection {
    public static function select(\check_bodies\Body_Set $bodies, Lifetime_Set $previous, Lifetime_Inputs $ownership, bool $full): array /** vector<\check_bodies\Checked_Body> */ {
        $tasks /** vector<\check_bodies\Checked_Body> */ = [];
        foreach ($bodies->bodies() as $body) {
            $old = $previous->for_callable($body->callable_id); $reuse = false;
            if (!$full) { if ($old !== null) { $reuse = ($old->body === $body) && ($old->ownership === $ownership->for_body($body->callable_id)); } }
            if (!$reuse) { $tasks[] = $body; }
        }
        return $tasks;
    }
}
