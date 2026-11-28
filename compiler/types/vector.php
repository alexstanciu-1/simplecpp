<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Resolved vector<T> identity supplied by runtime publication, with checked element access and mutation.
 * Copies and by-value calls use the common conversion path; instance fields default to empty.
 */
abstract class vector_type_definition extends type_definition implements readonly_index_lookup_type, appendable_collection_type, indexed_mutation_type, native_sequence_type, iterable_type, membership_type
{
	use indexed_entry_read;

	use checked_indexed_mutation;
	use collection_specializations;
	use concrete_value_storage;

	/** Native non-owning canonical element definition, shared by all occurrences. */
	public readonly type_definition $element_type;

	protected function __construct(string $name, type_definition $element)
	{
		parent::__construct($name);
		$this->element_type = $element;
	}

	/** Admit the scalar and shared-owner equality domains that have Python representations. */
	public function prepare_membership(ast_binary_expression $site): void
	{
		foreach ([$this->element_type, $site->left->type_def()] as $type) {
			if (!type_registry::is_numeric_scalar($type) && $type !== type_bool::instance()
				&& $type !== type_string::instance() && $type !== type_null::instance() && !($type instanceof shared_record_type_definition)) {
				throw new diagnostic($site->source(), $site->byte_start(), 'Python vector membership requires scalar or shared-handle values');
			}
		}
		$site->settle_binary(type_bool::instance(), $this);
	}

	/**
	 * Use the same cursor as foreach and return on the first match.
	 * @param array<string,bool> $headers
	 */
	public function lower_cpp_membership(ast_expression $needle, string $key, string $receiver, array &$headers): string
	{
		$slot = $this->iteration_output_type();
		$item = $slot === null ? 'item' : 'item.value';
		$type = $needle->type_def();
		$element = $this->element_type;
		if ($type instanceof shared_record_type_definition && $element instanceof shared_record_type_definition) {
			$match = "($key).ptr == $item.ptr";
		} elseif ($type === type_null::instance() && $element instanceof shared_record_type_definition) {
			$match = "((void)($key), $item.ptr == nullptr)";
		} elseif ($type instanceof shared_record_type_definition && $element === type_null::instance()) {
			$match = "($key).ptr == nullptr";
		} elseif ($type === $element) {
			$match = "$key == $item";
		} elseif ((type_registry::is_numeric_scalar($element) || $element === type_bool::instance())
			&& (type_registry::is_numeric_scalar($type) || $type === type_bool::instance())) {
			if ($type === type_float64::instance() || $element === type_float64::instance()) {
				// Compare without rounding the integer to float or converting an out-of-range float to int.
				[$floating, $integer] = $type === type_float64::instance() ? [$key, $item] : [$item, $key];
				$match = "$floating >= -0x1p63 && $floating < 0x1p63 && static_cast<scpp::int64>($floating) == $integer"
					. " && static_cast<double>(static_cast<scpp::int64>($floating)) == $floating";
			} else $match = "static_cast<scpp::int64>($key) == static_cast<scpp::int64>($item)";
		} else {
			return '((void)(' . $key . '), (void)(' . $receiver . '), false)';
		}
		$output = $slot ?? $element->cpp_type();
		$next = $this->lower_cpp_iteration_next('sequence', 'item', null);
		return '([&]() { const auto& sequence = ' . $receiver . '; ' . $output . ' item{}; '
			. $this->lower_cpp_iteration_storage(false) . $this->lower_cpp_iteration_initialize('sequence')
			. ' while (' . $next . ') { if (' . $match . ') return true; } return false; }())';
	}
}
