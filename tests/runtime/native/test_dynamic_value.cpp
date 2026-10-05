#include "scpp/support/dynamic_value.hpp"
#include "scpp/hash_t.hpp"
#include "scpp/data_initializer.hpp"
#include "scpp/index_write_path.hpp"
#include "scpp/value_iteration.hpp"
#include "scpp/keyed_path.hpp"
#include "scpp/cast.hpp"
#include "operators/conditional/condition_truthiness.hpp"
#include "operators/count/count.hpp"
#include "operators/arithmetic/boxed_numeric.hpp"
#include "operators/identity/boxed_ordering.hpp"
#include "operators/identity/strict_identity.hpp"
#include <limits>
#include <stdexcept>
#include <type_traits>
#include <utility>

namespace {
using value = scpp::detail::dynamic_value;
using scpp::int_t;
using scpp::string_t;

void require(bool condition, const char *message) {
	if (!condition) throw std::runtime_error(message);
}

static_assert(!std::is_constructible_v<value, scpp::mixed_t>);
static_assert(!std::is_assignable_v<value &, scpp::mixed_t>);
static_assert(!std::is_constructible_v<scpp::mixed_t, value>);
static_assert(!std::is_constructible_v<value, scpp::dynamic_t<>>);
static_assert(std::is_nothrow_move_constructible_v<value>);
static_assert(std::is_nothrow_move_assignable_v<value>);

template <typename Entry>
concept can_store = requires(value &receiver, const scpp::mixed_t &key, const Entry &entry) {
	scpp::index_store(receiver, key, entry);
};
static_assert(can_store<value>);
static_assert(!can_store<scpp::mixed_t>);
static_assert(!can_store<scpp::dynamic_t<>>);

template <typename Work>
void require_failure(Work work, const char *message) {
	bool rejected = false;
	try { work(); } catch (const std::runtime_error &) { rejected = true; }
	require(rejected, message);
}

void scalar_lifetimes() {
	// This checkpoint measures the agreed target ABI, not a portable language promise.
	require(sizeof(value) == 24 && alignof(value) == 8, "dynamic target layout");
	value null;
	require(null.is_null().native_value(), "default is null");
	value flag{scpp::bool_t{false}};
	require(flag.kind() == value::kind_t::bool_v && !flag.get_bool().native_value(), "false distinct from null");
	value number{int_t<>{42LL}};
	value copied{number};
	number = value{scpp::float_t{2.5}};
	require(copied.get_int().native_value() == 42 && number.get_float().native_value() == 2.5, "scalar value copy");
	value text{string_t{"hello"}};
	value text_copy{text};
	require(&text.get_string() != &text_copy.get_string(), "owned string wrapper copy");
	text = value{};
	require(text_copy.get_string().native_value() == "hello", "copied string lifetime");
	value moved{std::move(text_copy)};
	require(text_copy.is_null().native_value(), "move constructor leaves one null state");
	const auto &self = moved;
	moved = self;
	require(moved.get_string().native_value() == "hello", "self copy");
	auto &move_self = moved;
	moved = std::move(move_self);
	require(moved.get_string().native_value() == "hello", "self move");
	number = std::move(moved);
	require(moved.is_null().native_value() && number.get_string().native_value() == "hello", "move assignment");
	bool rejected = false;
	try { (void)number.get_int(); } catch (const std::runtime_error &) { rejected = true; }
	require(rejected && number.get_string().native_value() == "hello", "exact access failure preserves value");
	rejected = false;
	try { (void)null.get_table(); } catch (const std::runtime_error &) { rejected = true; }
	require(rejected && null.is_null().native_value(), "null does not create storage");
}

void recursive_containers() {
	value root = value::make_container();
	require(!root.is_null().native_value() && root.get_table().empty().native_value(), "empty container differs from null");
	require(root.get_table().is_packed().native_value(), "shape uses table query");
	value child = value::make_container();
	(void)child.get_table().append(value{int_t<>{7LL}});
	root.get_table().set(string_t{"child"}, child);
	value alias = root;
	require(alias.table_if() == root.table_if(), "container copy preserves identity");
	value selected = alias.get_table().read_or_default(string_t{"child"});
	selected.get_table().set(int_t<>{0LL}, value{int_t<>{9LL}});
	require(child.get_table().at(int_t<>{0LL}).get_int().native_value() == 9, "nested child aliases");
	static_assert(std::is_same_v<decltype(selected), value>);
	require(root.get_table().read_or_default(string_t{"missing"}).is_null().native_value(), "same-family null miss");
	require(root.get_table().size() == 1, "read miss does not insert");
	root = value{int_t<>{100LL}};
	require(alias.get_table().size() == 1 && root.get_int().native_value() == 100, "replacement only rebinds variable");
	require(alias.get_table().remove(string_t{"child"}), "remove existing child");
	require(selected.get_table().size() == 1, "child survives parent removal");
	value moved = std::move(selected);
	require(selected.is_null().native_value() && moved.table_if() == child.table_if(), "container move");
	for (int i = 0; i < 300; ++i) {
		alias.get_table().set(string_t{std::to_string(i)}, value{int_t<>{static_cast<std::int64_t>(i)}});
	}
	int expected = 0;
	for (auto it = alias.get_table().begin_entries(); it != alias.get_table().end_entries(); ++it) {
		const value element = (*it).value_copy();
		require(element.get_int().native_value() == expected++, "iteration gives same-family copied values");
	}
	require(expected == 300, "growth and tombstone traversal");
}

void container_operations() {
	using entry = scpp::initializer_entry<value, scpp::mixed_t>;
	using factory = scpp::data_initializer<value>;
	scpp::initializer_buffer<value> input{2};
	input.append(value{int_t<>{10LL}});
	input.append(value{int_t<>{20LL}});
	auto list = factory::from_list(input.view());
	scpp::initializer_buffer<entry> keyed{2};
	keyed.append(entry{scpp::mixed_t{string_t{"child"}}, list});
	keyed.append(entry{scpp::mixed_t{string_t{"none"}}, value{}});
	auto object = factory::from_key_value(keyed.view());
	const scpp::mixed_t child_key{string_t{"child"}};
	auto child = scpp::null_index_read(object, child_key);
	static_assert(std::is_same_v<decltype(child), value>);
	scpp::index_store(child, scpp::mixed_t{int_t<>{0LL}}, value{int_t<>{30LL}});
	scpp::index_append(child, value{int_t<>{40LL}});
	require(list.get_table().at(int_t<>{0LL}).get_int().native_value() == 30, "factory retains nested identity");
	require(list.get_table().size() == 3, "append shares child storage");
	int sum = 0;
	for (auto item : scpp::value_iteration(child)) {
		auto copied = item.value_copy();
		static_assert(std::is_same_v<decltype(copied), value>);
		sum += static_cast<int>(copied.get_int().native_value());
		copied = value{};
	}
	require(sum == 90, "shared iteration adapter copies same-family values");
	require(list.get_table().at(int_t<>{0LL}).get_int().native_value() == 30, "iteration scalar copies do not replace slots");
	const value replacement{int_t<>{50LL}};
	const scpp::mixed_t index{int_t<>{0LL}};
	const auto &probed_child = scpp::index_path_probe(object, child_key);
	scpp::index_path_check_store(probed_child, index, replacement);
	scpp::index_store(scpp::index_path_descend(object, child_key), index, replacement);
	require(list.get_table().at(int_t<>{0LL}).get_int().native_value() == 50, "nested commit retains shared identity");
	require_failure([&] { (void)scpp::index_path_probe(object, scpp::mixed_t{string_t{"none"}}); }, "null child does not autovivify");
	bool missing_path = false;
	try { (void)scpp::index_path_probe(object, scpp::mixed_t{string_t{"absent"}}); }
	catch (const std::out_of_range &) { missing_path = true; }
	require(missing_path && object.get_table().size() == 2, "missing child path does not create");
	value missing = scpp::null_index_read(object, scpp::mixed_t{string_t{"missing"}});
	require(missing.is_null().native_value() && object.get_table().size() == 2, "read miss does not create");
	require(scpp::null_index_read(missing, child_key).is_null().native_value(), "null intermediate read");
	require_failure([&] { (void)scpp::null_index_read(missing, scpp::mixed_t{false}); }, "null receiver still validates key");
	require_failure([&] { scpp::index_store(missing, child_key, child); }, "null write rejects bootstrap");
	require_failure([&] { scpp::index_append(missing, child); }, "null append rejects bootstrap");
	require(missing.is_null().native_value(), "rejected null mutation preserves null");
	value scalar{int_t<>{3LL}};
	require_failure([&] { (void)scpp::null_index_read(scalar, child_key); }, "scalar read rejects");
	require_failure([&] { scpp::index_store(scalar, child_key, child); }, "scalar write rejects");
	require_failure([&] { (void)scpp::value_iteration(scalar); }, "scalar foreach rejects");
	require_failure([&] { (void)scpp::value_iteration(missing); }, "null foreach rejects");
	require(scalar.get_int().native_value() == 3, "failed scalar operations preserve payload");
	scpp::initializer_buffer<value> empty_input{0};
	auto empty = factory::from_list(empty_input.view());
	int visits = 0;
	for ([[maybe_unused]] auto item : scpp::value_iteration(empty)) ++visits;
	require(visits == 0 && !empty.is_null().native_value(), "empty construction and iteration");
	scpp::initializer_buffer<entry> duplicates{2};
	duplicates.append(entry{scpp::mixed_t{int_t<>{10LL}}, value{}});
	duplicates.append(entry{scpp::mixed_t{int_t<>{10LL}}, value{int_t<>{5LL}}});
	require_failure([&] { object = factory::from_key_value(duplicates.view()); }, "duplicate null entry rejects");
	require(object.get_table().size() == 2, "failed factory leaves destination unchanged");
#if defined(SCPP_LANGUAGE_TARGET_PHP) && SCPP_LANGUAGE_TARGET_PHP
	scpp::initializer_buffer<entry> normalized_duplicates{2};
	normalized_duplicates.append(entry{scpp::mixed_t{string_t{"10"}}, value{}});
	normalized_duplicates.append(entry{scpp::mixed_t{int_t<>{10LL}}, value{}});
	require_failure([&] { (void)factory::from_key_value(normalized_duplicates.view()); }, "normalized duplicate rejects");
#endif
}
void conversions_and_probes() {
	value zero{string_t{"0"}};
	require(!scpp::condition_truthy(zero).native_value(), "string zero condition");
	require(!scpp::empty(zero).native_value(), "string zero is not empty");
	value none;
	require(!scpp::isset(none).native_value() && scpp::empty(none).native_value(), "null probes");
	require(!scpp::condition_truthy(none).native_value(), "null condition");
	value false_value{scpp::bool_t{false}};
	require(scpp::isset(false_value).native_value() && scpp::empty(false_value).native_value(), "present false probes");
	require(!scpp::condition_truthy(false_value).native_value(), "false condition");
	value container = value::make_container();
	require(scpp::condition_truthy(container).native_value() && scpp::empty(container).native_value(), "empty object presence vs emptiness");
	require(scpp::count(container).native_value() == 0, "container count");
	require_failure([&] { (void)scpp::count(zero); }, "scalar count rejects");
	const scpp::mixed_t key{string_t{"key"}};
	scpp::index_store(container, key, none);
	require(!scpp::isset(container, key).native_value(), "present null entry");
	scpp::index_store(container, key, zero);
	require(scpp::isset(container, key).native_value() && !scpp::empty(container, key).native_value(), "keyed string zero");
	require(!scpp::isset(container, scpp::mixed_t{false}).native_value(), "invalid probe key is absent");
	require(scpp::empty(container, scpp::mixed_t{false}).native_value(), "invalid empty key is empty");
	require(!scpp::keyed_ready(&none, true), "null unset parent is no-op");
	require(!scpp::keyed_ready(&zero, false), "scalar probe parent is absent");
	require_failure([&] { (void)scpp::keyed_ready(&zero, true); }, "scalar unset parent rejects");
	const auto *child = scpp::keyed_child(&container, key, false);
	require(child && !scpp::keyed_ready(child, false), "scalar intermediate stops probe");
	require(scpp::keyed_probe(&container, key, false).native_value(), "same-family keyed probe");
	require(!scpp::keyed_probe(&container, key, true).native_value(), "same-family keyed empty");
	scpp::keyed_erase(container, key);
	require(!scpp::keyed_child(&container, key, true) && scpp::count(container).native_value() == 0, "non-creating removal");
	scpp::vector_t<value> values;
	values.append(none);
	require(!scpp::isset(values, int_t<>{0LL}).native_value(), "typed dynamic element remains null-sensitive");
	const scpp::mixed_t samples[] = {scpp::mixed_t{}, scpp::mixed_t{false}, scpp::mixed_t{int_t<>{42LL}}, scpp::mixed_t{scpp::float_t{2.5}}, scpp::mixed_t{string_t{"text"}}};
	for (const auto &sample : samples) {
		auto converted = scpp::cast<value>(sample);
		auto returned = scpp::cast<scpp::mixed_t>(converted);
		require(returned.kind() == sample.kind(), "family conversion preserves scalar kind");
	}
	require(scpp::cast<value>(scpp::mixed_t{int_t<>{42LL}}).get_int().native_value() == 42, "integer conversion value");
	require(scpp::cast<scpp::mixed_t>(zero).get_string().native_value() == "0", "string conversion value");
	value destination{int_t<>{9LL}};
	scpp::mixed_t mixed_table{std::make_shared<scpp::hash_t<scpp::mixed_t>>()};
	require_failure([&] { destination = scpp::cast<value>(mixed_table); }, "mixed container cannot cross");
	require(destination.get_int().native_value() == 9, "failed conversion preserves dynamic destination");
	scpp::mixed_t mixed_destination{int_t<>{8LL}};
	require_failure([&] { mixed_destination = scpp::cast<scpp::mixed_t>(container); }, "dynamic container cannot cross");
	require(mixed_destination.get_int().native_value() == 8, "failed conversion preserves mixed destination");
}
void scalar_boundaries_and_arithmetic() {
	value integer{int_t<>{6LL}};
	value floating{scpp::float_t{2.5}};
	require((integer + value{int_t<>{2LL}}).get_int().native_value() == 8, "int arithmetic retains int");
	require((integer + floating).get_float().native_value() == 8.5, "numeric widening");
	require((floating - integer).get_float().native_value() == -3.5, "float-int subtraction");
	require((integer * floating).get_float().native_value() == 15.0, "mixed-kind multiplication");
	require((floating / value{scpp::float_t{0.5}}).get_float().native_value() == 5.0, "division");
	require((integer % value{int_t<>{4LL}}).get_int().native_value() == 2, "integer remainder");
	require((-integer).get_int().native_value() == -6 && (+floating).get_float().native_value() == 2.5, "unary numeric rules");
	static_assert(std::is_same_v<decltype(integer + floating), value>);
	require_failure([&] { (void)(integer / value{int_t<>{0LL}}); }, "integer zero divisor rejects");
	require_failure([&] { (void)(integer / value{scpp::float_t{-0.0}}); }, "floating zero divisor rejects");
	require_failure([&] { (void)(integer % value{int_t<>{0LL}}); }, "zero modulo rejects");
	require_failure([&] { (void)(integer % floating); }, "floating modulo rejects");
	require_failure([&] { (void)(integer + value{string_t{"2"}}); }, "numeric strings not coerced for arithmetic");
	require_failure([&] { (void)(integer + value{scpp::bool_t{true}}); }, "bool arithmetic rejects");
	require_failure([&] { (void)(integer + value::make_container()); }, "container arithmetic rejects");
	require_failure([&] { (void)(-value{}); }, "null arithmetic rejects");
	require(scpp::cast<int_t<>>(floating).native_value() == 2, "explicit float-int cast");
	require_failure([&] { (void)scpp::required_cast<int_t<>>(floating); }, "required int rejects floating payload");
	require(scpp::required_cast<int_t<>>(integer).native_value() == 6, "required int extraction");
	require(scpp::required_cast<scpp::float_t>(integer).native_value() == 6.0, "required float delegates widening");
	require(scpp::cast<string_t>(value{}).native_value().empty(), "explicit null-string cast");
	require_failure([&] { (void)scpp::required_cast<string_t>(value{}); }, "required string rejects null");
	require_failure([&] { (void)scpp::cast<scpp::bool_t>(value{}); }, "explicit null-bool cast is not condition truthiness");
	require(!scpp::cast<scpp::nullable<int_t<>>>(value{}).has_value().native_value(), "nullable absent extraction");
	require(scpp::required_cast<scpp::nullable<int_t<>>>(integer).require_value("test").native_value() == 6, "nullable present extraction");
	require_failure([&] { (void)scpp::cast<string_t>(value::make_container()); }, "scalar extraction cannot stringify a container");
	require(integer.get_int().native_value() == 6, "failed operators leave operands unchanged");
}
void comparisons_and_updates() {
	value integer{int_t<>{3LL}};
	value floating{scpp::float_t{3.0}};
	require((integer <= floating).native_value() && (integer >= floating).native_value(), "numeric ordering delegates widening");
	require(!(integer < floating).native_value() && !(integer > floating).native_value(), "equal numeric ordering");
	require(!scpp::identical(integer, floating).native_value(), "strict identity preserves kinds");
	require(scpp::identical(integer, value{int_t<>{3LL}}).native_value(), "strict int identity");
	require(scpp::identical(value{string_t{"hello"}}, value{string_t{"hello"}}).native_value(), "strict string value identity");
	value null;
	require(scpp::identical(null, scpp::null_t{}).native_value() && scpp::identical(scpp::null_t{}, null).native_value(), "null identity in both orders");
	require(!scpp::identical(integer, scpp::null_t{}).native_value(), "nonnull identity");
	value first = value::make_container();
	value alias = first;
	value second = value::make_container();
	require(scpp::identical(first, alias).native_value(), "container alias identity");
	require(!scpp::identical(first, second).native_value(), "equal contents do not confer container identity");
	require(!scpp::identical(first, null).native_value(), "empty container is not null");
	scpp::nullable<value> present{null};
	require(scpp::identical(present, scpp::null_t{}).native_value(), "nullable delegates payload identity");
	scpp::nullable<value> absent{scpp::null_t{}};
	require(scpp::identical(absent, scpp::null_t{}).native_value(), "absent nullable identity");
	require(scpp::identical(scpp::null_t{}, scpp::null_t{}).native_value(), "null sentinel identity excludes generic equality");
	require(scpp::identical(scpp::nullopt_t{}, scpp::nullopt_t{}).native_value(), "nullopt sentinel identity");
	require(scpp::identical(scpp::nullptr_t{}, scpp::nullptr_t{}).native_value(), "nullptr sentinel identity");
	value nan{scpp::float_t{std::numeric_limits<double>::quiet_NaN()}};
	require(!scpp::identical(nan, nan).native_value(), "NaN retains primitive identity rule");
	require(!(nan <= floating).native_value(), "NaN ordering not synthesized from opposite comparison");
	require_failure([&] { (void)(first < second); }, "container ordering rejects");
	require_failure([&] { (void)(value{string_t{"3"}} < integer); }, "string ordering rejects");
	integer += value{int_t<>{2LL}};
	require(integer.get_int().native_value() == 5, "same-kind compound update");
	require_failure([&] { integer += value{scpp::float_t{1.0}}; }, "compound int rejects float even integral");
	require(integer.get_int().native_value() == 5, "failed compound retains value");
	floating += integer;
	require(floating.get_float().native_value() == 8.0, "float compound accepts integer computation");
	floating *= value{int_t<>{2LL}};
	floating /= value{int_t<>{4LL}};
	floating -= value{scpp::float_t{1.5}};
	require(floating.get_float().native_value() == 2.5, "multiply divide subtract write-back");
	require_failure([&] { floating /= value{int_t<>{0LL}}; }, "failed division update");
	require(floating.get_float().native_value() == 2.5, "failed division preserves lhs");
	integer %= value{int_t<>{3LL}};
	require(integer.get_int().native_value() == 2, "remainder update");
	value snapshot = integer++;
	require(snapshot.get_int().native_value() == 2 && integer.get_int().native_value() == 3, "postincrement snapshot");
	value &same = ++integer;
	require(&same == &integer && integer.get_int().native_value() == 4, "preincrement identity");
	snapshot = floating--;
	require(snapshot.get_float().native_value() == 2.5 && floating.get_float().native_value() == 1.5, "float postdecrement");
	--floating;
	require(floating.get_float().native_value() == 0.5, "float predecrement");
	require_failure([&] { ++first; }, "container update rejects");
	require(scpp::identical(first, alias).native_value() && first.get_table().empty().native_value(), "failed container update preserves identity");
	integer = integer + value{scpp::float_t{0.5}};
	require(integer.get_float().native_value() == 4.5, "ordinary assignment may change numeric kind");
}
} // namespace

int main() {
	scalar_lifetimes();
	recursive_containers();
	container_operations();
	conversions_and_probes();
	scalar_boundaries_and_arithmetic();
	comparisons_and_updates();
}
