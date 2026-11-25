<?php

/* Neutral lexical lifetime facts; no syntax, environment or worker back-links. */
namespace scpp\compiler;

final class storage_lifetime_region
{
	public int $next_order = 0;
	public function __construct(public ?storage_lifetime_region $parent, public int $entered_at) {}
}

/** Establishment within a region, independent of parser token generations. */
final class storage_lifetime
{
	public function __construct(public storage_lifetime_region $region, public int $order) {}
}

/** A borrow always has both an origin and its establishment lifetime. */
final class storage_borrow
{
	public function __construct(public prepared_place $place, public storage_lifetime $lifetime) {}
}
