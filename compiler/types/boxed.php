<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Canonical tagged scalar family. Runtime checks consume the actual payload, e.g. (int)$boxed. */
abstract class boxed_type_definition extends type_definition implements indexed_mutation_type, membership_type
{
	use collection_keyed_store;

	public function prepare_membership(ast_binary_expression $site): void
	{
		$needle = $site->left->type_def();
		if ($needle !== $this && !$this->accepts_scalar($needle)) {
			throw new diagnostic($site->source(), $site->left->byte_start(), 'Unsupported boxed membership operand');
		}
		$site->settle_binary(type_bool::instance(), $this);
	}

	/** @param array<string,bool> $headers */
	public function lower_cpp_membership(ast_expression $needle, string $key, string $receiver, array &$headers): string
	{
		if ($needle->type_def() !== $this) $key = $this->box_cpp($needle, $key);
		return '(' . $receiver . ').python_contains(' . $key . ')';
	}

	public function prepare_key(ast_expression $key): void
	{
		$type = $key->type_def();
		if ($type !== type_int64::instance() && $type !== type_string::instance()) {
			throw new diagnostic($key->source(), $key->byte_start(), 'Boxed container key requires int or string');
		}
	}

	/** Admit nested boxed subscripts only for the prepared local mutation target outside Python. */
	public function prepare_index(ast_index_expression $value): void
	{
		if ($value->receiver instanceof ast_index_expression && $value->source()->flavor !== flavor::python) {
			$parent = $value->parent;
			if (!(($parent instanceof ast_index_compound || $parent instanceof ast_index_update) && $parent->target === $value
				&& ast_index_expression::boxed_path_root($value->receiver) !== null)) {
				throw new diagnostic($value->source(), $value->byte_start(), 'Nested boxed indexing is deferred');
			}
		}
		$this->prepare_key($value->index);
		$value->settle_element($this);
	}

	public function write_type(): type_definition
	{
		return $this;
	}

	public function lower_cpp_empty(): string
	{
		return $this->cpp_type() . '::container()';
	}

	/** Keep the selected Python receiver alive across an effectful index expression. */
	public function lower_cpp_index(ast_expression $key, string $receiver, string $index): string
	{
		if ($key->source()->flavor === flavor::python) {
			$index = $key->type_def()->lower_cpp_exact_argument($key, value_conversion::identity, $index);
			if (!($key instanceof ast_integer_expression || $key instanceof ast_string_expression || $key instanceof ast_name_expression)) {
				$receiver = $this->cpp_type() . '(' . $receiver . ')';
			}
			return '(' . $receiver . ').python_read(' . $index . ')';
		}
		return $receiver . '.source_read(scpp::boxed_key(' . $index . '))';
	}

	/** Generated source_has applies the same source-key normalization as indexed reads. */
	public function lower_cpp_key_presence(string $receiver, string $key): string
	{
		return '(' . $receiver . ').source_has(' . $key . ')';
	}

	public function lower_cpp_mutation_read(ast_expression $key, string $receiver, string $index): string
	{
		return $receiver . '.source_require(scpp::boxed_key(' . $index . '))';
	}

	public function lower_cpp_key(ast_expression $syntax, string $key): string
	{
		return 'scpp::boxed_key(' . $key . ')';
	}

	public function lower_cpp_write(string $receiver, string $key, string $value): string
	{
		return $receiver . '.source_set(' . $key . ', ' . $value . ')';
	}

	private function lower_cpp_child_lookup(string $root, string $parent_key): string
	{
		return "$root.source_check_container(); auto boxed_child = $root.source_require($parent_key); boxed_child.source_check_container(); ";
	}

	public function lower_cpp_child_read(string $root, string $parent_key, string $key): string
	{
		return '([&]() { ' . $this->lower_cpp_child_lookup($root, $parent_key) . "return boxed_child.source_require($key); }())";
	}

	/** A null key appends using the current child's next integer key. */
	public function lower_cpp_child_store(string $root, string $parent_key, ?string $key, string $value, bool $require_leaf = false): string
	{
		$store = $this->lower_cpp_child_lookup($root, $parent_key);
		if ($require_leaf) $store .= "(void)boxed_child.source_require($key); ";
		$store .= ($key === null ? $this->lower_cpp_append_value('boxed_child', $value) : $this->lower_cpp_write('boxed_child', $key, $value)) . ';';
		if (!$this->mutates_referenced_value()) $store .= ' ' . $this->lower_cpp_write($root, $parent_key, 'boxed_child') . ';';
		return $store;
	}

	public function supports_inferred_local(): bool
	{
		return true;
	}

	public function cpp_type(): string
	{
		return 'scpp::' . $this->name;
	}

	abstract public function accepts_scalar(type_definition $source): bool;
	abstract public function admits_extraction(type_definition $target, conversion_context $context): bool;
	abstract public function box_cpp(ast_expression $syntax, string $value): string;
	abstract public function admits_pair(binary_operator $operator, type_definition $peer, bool $box_left): bool;

	/** Selected from the intersection of structural writes and this family's scalar factories. */
	protected function export_factory(type_definition $element): ?string
	{
		return null;
	}

	public function supports_record_export(): bool
	{
		return false;
	}

	public function exports_scalar(type_definition $element): bool
	{
		return $this->export_factory($element) !== null;
	}

	/** Borrow the evaluated vector once and publish a fresh list after every element has been boxed. */
	public function cpp_conversion_adapter(int $conversion /** uint16 */, ?type_definition $source): ?cpp_inline_adapter
	{
		if ($conversion === value_conversion::export_record && $source instanceof record_type_definition) {
			$body = "\treturn " . $this->cpp_type() . '::from_record(&source, &' . $source->cpp_type() . "::scpp_escape_encode_fields);\n";
			return new cpp_inline_adapter('export_record', [new cpp_adapter_parameter($source, 'source', cpp_adapter_mode::const_reference)],
				$this, $body, false);
		}
		if ($conversion === value_conversion::export_record && $source instanceof native_sequence_type) {
			$body = "\treturn " . $this->cpp_type() . "::from_record(&source, [](const void* raw, ::scpp::Encoder& out) {\n"
				. "\t    const auto& items = *static_cast<const " . $source->cpp_type() . "*>(raw);\n"
				. "\t    " . Cpp_Emitter::record_vector_walk($source, 'items') . "\n\t});\n";
			return new cpp_inline_adapter('export_record_vector', [new cpp_adapter_parameter($source, 'source', cpp_adapter_mode::const_reference)],
				$this, $body, false, headers: ['scpp/encoder.hpp']);
		}
		if ($conversion !== value_conversion::export_list) return null;
		if (!($source instanceof native_sequence_type)) throw new \LogicException('Unprepared list export');
		$factory = $this->export_factory($source->write_type()) ?? throw new \LogicException('Missing list element factory');
		$count = $source->lower_cpp_cardinality(collection_cardinality::count, 'source');
		$element = $source->lower_cpp_element_read('source', 'i');
		$body = "\tauto list = " . $this->lower_cpp_empty() . ";\n"
			. "\tfor (::scpp::int64 i = 0, count = $count; i < count; ++i) list.append($factory($element));\n"
			. "\treturn list;\n";
		return new cpp_inline_adapter('export_list', [new cpp_adapter_parameter($source, 'source', cpp_adapter_mode::const_reference)],
			$this, $body, false);
	}

	/** Admit copies within this family and box scalar values through the generated bindings. */
	public function prepare_scalar_conversion(ast_expression $value, conversion_context $context): int /** uint16 */
	{
		$source = $value->type_def();
		if ($context === conversion_context::explicit_cast && $source instanceof native_sequence_type) {
			if ($this->export_factory($source->write_type()) === null) {
				throw new diagnostic($value->source(), $value->byte_start(), 'Cannot export element type ' . $source->write_type()->name . ' to ' . $this->name);
			}
			return value_conversion::export_list;
		}
		if ($source === $this) {
			return value_conversion::identity;
		}
		if ($this->accepts_scalar($value->type_def())) {
			return value_conversion::box_scalar;
		}
		throw new diagnostic($value->source(), $value->byte_start(), 'Unsupported boxing into ' . $this->name);
	}

	/** Select the box execution type once and attach the scalar or boxed result to the operator. */
	public function prepare_binary(ast_binary_expression $value): void
	{
		$box_left = $value->left->type_def() === $this;
		$peer = $box_left ? $value->right->type_def() : $value->left->type_def();
		if ($this->python_equality($value) && ($peer === $this || $this->accepts_scalar($peer))) {
			$value->settle_binary(type_bool::instance(), $this,
				$this->prepare_scalar_conversion($value->left, conversion_context::typed_destination),
				$this->prepare_scalar_conversion($value->right, conversion_context::typed_destination));
			return;
		}
		if ($peer === type_null::instance() && ($value->operator === binary_operator::identical || $value->operator === binary_operator::not_identical)
			&& ($box_left ? $value->right : $value->left) instanceof ast_keyword_expression) {
			$value->settle_binary(type_bool::instance(), $this);
			return;
		}
		if (!$this->admits_pair($value->operator, $peer, $box_left)) {
			throw new diagnostic($value->source(), $value->byte_start(), 'Unsupported boxed binary operands or operator');
		}
		$value->settle_binary($this->boxed_result($value->operator) ?? throw new \LogicException('Missing boxed result'), $this);
	}

	private function python_equality(ast_binary_expression $value): bool
	{
		return ($value->operator === binary_operator::equal || $value->operator === binary_operator::not_equal)
			&& $value->source()->flavor === flavor::python;
	}

	/** Emit Python value equality and literal null identity directly, or call the published boxed operator. */
	public function lower_prepared_binary(ast_binary_expression $expr, string $left, string $right, bool $typed_initializer = false,
		bool $bare_comparison = false): string
	{
		if ($this->python_equality($expr)) {
			return ($expr->operator === binary_operator::not_equal ? '!' : '') . '(' . $left . ').python_equal(' . $right . ')';
		}
		if ($expr->operator === binary_operator::identical || $expr->operator === binary_operator::not_identical) {
			$other = $expr->left->type_def() === $this ? $expr->right : $expr->left;
			if ($other->type_def() === type_null::instance() && $other instanceof ast_keyword_expression) {
				$operand = $expr->left->type_def() === $this ? $left : $right;
				return '((' . $operand . ').kind() ' . ($expr->operator === binary_operator::identical ? '==' : '!=') . ' scpp::boxed_kind::null)';
			}
		}
		return 'scpp::boxed_' . $expr->operator->name . '_' . $expr->left->type_def()->name . '_'
			. $expr->right->type_def()->name . '(' . $left . ', ' . $right . ', ' . $this->flavor_mask($expr) . ')';
	}

	abstract protected function boxed_result(binary_operator $operator): ?type_definition;
	abstract protected function flavor_mask(ast_node $site): int /** uint8 */;

	public function prepare_compound(ast_assignment_statement $value, type_definition $right): void
	{
		if (!$this->supports_compound($value->operator) || $this->binary_result($value->operator, $right) !== $this) {
			throw new diagnostic($value->source(), $value->byte_start(), 'Unsupported compound operands or write-back type');
		}
		$conversion = $this->prepare_scalar_conversion($value->value, conversion_context::typed_destination);
		$value->settle_compound($this, value_conversion::identity, $conversion, value_conversion::identity);
	}

	public function lower_prepared_compound(ast_assignment_statement $site, string $left, string $right): string
	{
		return 'scpp::boxed_' . $site->operator->name . '_' . $this->name . '_' . $this->name
			. '(' . $left . ', ' . $right . ', ' . $this->flavor_mask($site) . ')';
	}

	public function lower_cpp_update(update_operator $operator, string $target, bool $postfix): string
	{
		return $target . ' = scpp::boxed_' . $operator->name . '_' . $this->name . '(' . $target . ')';
	}
}
