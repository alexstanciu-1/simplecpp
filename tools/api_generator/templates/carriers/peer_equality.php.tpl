
/** Project a participating operand to its source payload type. */
function carrier_operand_payload(type_definition $type): ?type_definition
{
	if (!($type instanceof carrier_type_definition)) return $type;
	return match ($type->family) {
@families@
		default => null,
	};
}

/** Project a carrier storage operand for a checked update or compound write. */
function carrier_write_payload(carrier_type_definition $type, flavor $flavor): ?type_definition
{
@writes@
}

/** Resolve the positive comparator for the first consumer of operand participation. */
function peer_equality_binding(type_definition $left, type_definition $right, flavor $flavor, binary_operator $operator): ?prepared_peer_equality
{
@dispatch@
}

/** Alternate states shared by different participating families. */
function shared_peer_alternate(carrier_type_definition $left_type, carrier_type_definition $right_type, string $left, string $right): ?string
{
	$matches = [];
@shared_states@
	return $matches === [] ? null : implode(' || ', $matches);
}
