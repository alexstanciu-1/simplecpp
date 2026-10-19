<?php
namespace scpp\compiler;

/** Family recipes emit native code, never perform source semantic analysis. */
final class CPP_Concrete_Recipes
{
	/** Compatibility with the measured first slice; new runtime bindings use the opaque worker. */
	public static function request(canonical_type_use $type, CPP_Specializations $engine): cpp_specialization_entry
	{
		$canonical = Type_Queries::canonical($type);
		$definition = $canonical->definition();
		if ($definition instanceof integer_type_definition) {
			$integer = object_cast($definition, integer_type_definition::class);
			$name = ($integer->signed() ? 'int' : 'uint') . $integer->bit_width();
			$existing = $engine->find($name);
			if ($existing !== null) { return $existing; }
			return self::integer($name, $integer->signed(), $integer->bit_width());
		}
		if ($canonical instanceof applied_template_type) {
			$application = object_cast($canonical, applied_template_type::class);
			if ($definition !== Model::$type_catalog->definition('vector')) {
				throw new \RuntimeException('prelower_templates: unsupported template family ' . $definition->name());
			}
			$element = $application->arguments()[0];
			if (!(new Capability_Facts())->has_capability($element, generic_contract::copyable_value)) {
				throw new \RuntimeException('prelower_templates: vector recipe currently requires copyable elements');
			}
			$context = new cpp_generation_context($engine);
			$mapping = CPP_Types::representation($element, $context);
			$key = $engine->entry($engine->request(Model::$type_catalog->registry()->use(Model::$type_catalog->canonical('int')->type_id())));
			$suffix = '';
			if ($mapping->specialization !== null) { $suffix = $engine->entry($engine->request($element))->name; }
			else { $suffix = CPP_Artifacts::component($mapping->spelling); }
			$vector_name = 'vector_' . $suffix;
			$existing_vector = $engine->find($vector_name);
			if ($existing_vector !== null) { return $existing_vector; }
			return self::vector($vector_name, $mapping, $key);
		}
		throw new \RuntimeException('prelower_templates: no recipe for ' . $definition->name());
	}

	/** Ordinary nontemplate representations retain the existing binding, without another semantic pass. */
	public static function representation(canonical_type_use $type, cpp_generation_context $context, CPP_Specializations $engine): cpp_type
	{
		$canonical = Type_Queries::canonical($type);
		if (($canonical->family() !== type_family::integer) && !($canonical instanceof applied_template_type)) {
			return CPP_Types::representation($type);
		}
		$context->specializations[$type->type_id()] = $type;
		$entry = $engine->entry($engine->request($type));
		$result = new cpp_type();
		$result->spelling = 'scpp::internal::' . $entry->name;
		$result->headers[$entry->header] = true;
		$result->literal = $canonical->family() === type_family::integer ? cpp_literal_kind::signed_integer : cpp_literal_kind::none;
		$result->specialization = $type;
		return $result;
	}

	/** Substitute recipe slots; argument spelling comes only from backend bindings. */
	private static function expand(array $slots /** vector<string> */, array $values /** vector<string> */, string $recipe): string
	{
		foreach ($slots as $index => $slot)
		{
			$start = 0;
			$output = '';
			$length = string_byte_len($recipe);
			while ($start < $length)
			{
				$at = q_strpos($recipe, $slot, $start);
				if ($at === false) {
					$output .= string_byte_slice($recipe, $start, $length - $start); break;
				}
				$output .= string_byte_slice($recipe, $start, $at - $start) . $values[$index];
				$start = $at + string_byte_len($slot);
			}
			$recipe = $output;
		}
		return $recipe;
	}

	/** Emit one concrete width/signedness representation without an int_t template. */
	public static function integer(string $name, bool $signed, int $bits): cpp_specialization_entry
	{
		$representation = ($signed ? 'int' : 'uint') . $bits . '_t';
		$header = self::expand(['@N@', '@R@'], [$name, $representation], '#pragma once
#include <stdint.h>
namespace scpp::internal {
class @N@ final {
    @R@ value_ = 0;
public:
    constexpr @N@() noexcept = default;
    explicit constexpr @N@(uint64_t value) noexcept : value_(static_cast<@R@>(value)) {}
    constexpr @R@ native_value() const noexcept { return value_; }
    @N@& operator++() noexcept { value_ = static_cast<@R@>(value_ + 1); return *this; }
    @N@& operator--() noexcept { value_ = static_cast<@R@>(value_ - 1); return *this; }
    @N@ operator++(int) noexcept { auto previous=*this; ++*this; return previous; }
    @N@ operator--(int) noexcept { auto previous=*this; --*this; return previous; }
};
}
');
		return new cpp_specialization_entry($name, 'specializations/' . $name . '.hpp', $header, '');
	}

	/** Emit the admitted vector protocol over its resolved element representation. */
	public static function vector(string $name, cpp_type $element, cpp_specialization_entry $key): cpp_specialization_entry
	{
		$headers = $element->headers;
		$headers[$key->header] = true;
		$find = ['@N@', '@E@', '@K@', '@INCLUDES@'];
		$replace = [$name, $element->spelling, 'scpp::internal::' . $key->name, CPP_Artifacts::includes($headers, '')];
		$header = self::expand($find, $replace, '#pragma once
@INCLUDES@
#include <stddef.h>
namespace scpp::internal {
class @N@ final {
    using element = @E@;
    element* values_ = nullptr;
    size_t size_ = 0;
    size_t capacity_ = 0;
    void swap(@N@&) noexcept;
    [[noreturn]] void bounds(int64_t) const;
public:
    @N@() noexcept = default;
    @N@(const @N@&);
    @N@(@N@&&) noexcept;
    @N@& operator=(const @N@&);
    @N@& operator=(@N@&&) noexcept;
    ~@N@();
    size_t size() const noexcept;
    @K@ count() const noexcept;
    void clear() noexcept;
    void reserve(size_t);
    void append(const element&);
    element& at(@K@);
    const element& at(@K@) const;
};
@K@ count_@N@(const @N@&);
}
');
		$body = self::expand($find, $replace, '#include <new>
#include <cstdint>
#include "scpp/runtime_error.hpp"
namespace scpp::internal {
@N@::@N@(const @N@& other) {
    try { reserve(other.size_); for(size_t i=0;i<other.size_;++i) { new(values_+size_) element(other.values_[i]); ++size_; } }
    catch (...) { clear(); ::operator delete(values_, std::align_val_t(alignof(element))); throw; }
}
@N@::@N@(@N@&& other) noexcept { swap(other); }
@N@& @N@::operator=(const @N@& other) { if(this != &other) { @N@ next(other); swap(next); } return *this; }
@N@& @N@::operator=(@N@&& other) noexcept { if(this != &other) { @N@ next(static_cast<@N@&&>(other)); swap(next); } return *this; }
@N@::~@N@() { clear(); ::operator delete(values_, std::align_val_t(alignof(element))); }
void @N@::swap(@N@& other) noexcept {
    auto p=values_; values_=other.values_; other.values_=p;
    auto n=size_; size_=other.size_; other.size_=n;
    n=capacity_; capacity_=other.capacity_; other.capacity_=n;
}
size_t @N@::size() const noexcept { return size_; }
@K@ @N@::count() const noexcept { return @K@(size_); }
@K@ count_@N@(const @N@& value) { return value.count(); }
void @N@::clear() noexcept { while(size_) { values_[--size_].~element(); } }
void @N@::reserve(size_t wanted) {
    if(wanted<=capacity_) return;
    if(wanted>SIZE_MAX/sizeof(element)) throw std::bad_array_new_length();
    auto next=static_cast<element*>(::operator new(wanted*sizeof(element),std::align_val_t(alignof(element))));
    size_t built=0;
    try { for(;built<size_;++built) new(next+built) element(values_[built]); }
    catch (...) { while(built) next[--built].~element(); ::operator delete(next,std::align_val_t(alignof(element))); throw; }
    auto n=size_; clear(); ::operator delete(values_,std::align_val_t(alignof(element)));
    values_=next; size_=n; capacity_=wanted;
}
void @N@::append(const element& value) {
    element copy(value);
    if(size_==capacity_) { if(capacity_>SIZE_MAX/2) throw std::bad_array_new_length(); reserve(capacity_?capacity_*2:4); }
    new(values_+size_) element(copy); ++size_;
}
[[noreturn]] void @N@::bounds(int64_t index) const {
    throw scpp::runtime_error("vector index " + std::to_string(index) + " is out of bounds for size " + std::to_string(size_) + ".",
        "bounds_error", "runtime", "operator[]", {{"container","vector"},{"index",std::to_string(index)},{"size",std::to_string(size_)},{"operation","operator[]"}});
}
@E@& @N@::at(@K@ index) {
    auto n=index.native_value(); if(n<0 || static_cast<uint64_t>(n)>=size_) bounds(n);
    return values_[static_cast<size_t>(n)];
}
const @E@& @N@::at(@K@ index) const {
    auto n=index.native_value(); if(n<0 || static_cast<uint64_t>(n)>=size_) bounds(n);
    return values_[static_cast<size_t>(n)];
}
}
');
		return new cpp_specialization_entry($name, 'specializations/' . $name . '.hpp', $header, $body);
	}

	/** Expose a small nontemplate bridge to existing CLI and error contracts. */
	public static function base_header(): string
	{
		return '#pragma once
#include <stdint.h>
namespace scpp::internal {
[[noreturn]] void integer_cast_range(int64_t value);
[[noreturn]] void integer_cast_range(uint64_t value);
[[noreturn]] void zero_divisor(bool remainder);
void initialize_cli(int argc, char** argv);
}
';
	}

	/** Keep production runtime diagnostics behind separately compiled support. */
	public static function base_body(): string
	{
		return '#include "specializations/base.hpp"
#include <cstdint>
#include "scpp/runtime_error.hpp"
#include "lang/php/cli_context.hpp"
namespace scpp::internal {
void initialize_cli(int argc, char** argv) { scpp::php::set_cli_args(argc, argv); }
[[noreturn]] static void cast_range_message(const std::string& value) {
    throw scpp::runtime_error("scpp::cast<int_t>: integer value is outside the target range: " + value,
        "integer_cast_out_of_range", "scpp::cast<int_t>", "", {{"target_type","int_t"},{"value",value}});
}
[[noreturn]] void integer_cast_range(int64_t value) { cast_range_message(std::to_string(value)); }
[[noreturn]] void integer_cast_range(uint64_t value) { cast_range_message(std::to_string(value)); }

[[noreturn]] void zero_divisor(bool remainder) {
    const char* operation = remainder ? "%" : "/";
    throw scpp::runtime_error(std::string("scpp runtime arithmetic error: operator \'") + operation + "\' rejected a zero divisor.",
        remainder ? "modulo_by_zero" : "division_by_zero", "generated_operator_detail", operation,
        {{"lhs_kind","int_t"},{"rhs_kind","int_t"}});
}
}
';
	}
}
