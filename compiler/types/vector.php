<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Resolved vector<T> identity supplied by runtime publication, with checked element access and mutation.
 * Copies and by-value calls use the common conversion path; instance fields default to empty.
 */
abstract class vector_type_definition extends type_definition implements readonly_index_lookup_type, writable_iteration_type, appendable_collection_type, indexed_mutation_type, native_sequence_type, iterable_type, membership_type
{
	use checked_indexed_mutation;
	use collection_specializations;
	use concrete_value_storage;

	/** PROPERTIES */
	/** Native non-owning canonical element definition, shared by all occurrences. */
	public readonly type_definition $element_type;
	private ?runtime_function $filled_binding = null;

	/** CONSTRUCTOR */
	protected function __construct(string $name, type_definition $element)
	{
		parent::__construct($name);
		$this->element_type = $element;
	}

	/** METHODS */
	public function private_copy_allocation_only(): bool
	{
		return $this->element_type->copy_has_no_observable_effects();
	}

	public function lower_cpp_iteration_reference(string $receiver, int $loop_start): string
	{
		return $this->lower_cpp_slot($receiver, "iteration_index_{$loop_start}");
	}

	/** Checked storage access used at the point of mutation. */
	public function lower_cpp_slot(string $receiver, string $index): string
	{
		return "$receiver.iteration_reference($index)";
	}

	/** Bind the factory on the canonical vector, including application-owned element types. */
	public function filled_function(): runtime_function
	{
		if ($this->filled_binding !== null) return $this->filled_binding;
		$resize = $this->runtime_method('resize');
		return $this->filled_binding = new runtime_function('vector_filled', [$resize->parameters[1], $this->element_type],
			$this, $this->cpp_type() . '::filled', $this->cpp_headers()[0],
			const_reference_mask: $resize->is_const_reference(2) ? 2 : 0, exact_arguments: true);
	}

	public function supports_inferred_local(): bool
	{
		return true;
	}

	public function supports_instance_field(): bool
	{
		return true;
	}

	public function write_type(): type_definition
	{
		return $this->element_type;
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

	public function prepare_index(ast_index_expression $value): void
	{
		$this->prepare_key($value->index);
		$value->settle_element($this->element_type);
	}
}
