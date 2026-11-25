#include "operators/isset/isset.hpp"
#include "scpp/shared_p.hpp"
#include <cassert>
#include <type_traits>

struct base { virtual ~base() = default; };
struct derived : base {};
struct unrelated {};
using base_handle = scpp::shared_p<base>;
using derived_handle = scpp::shared_p<derived>;
using base_map = scpp::hash_t<scpp::bool_t, base_handle>;
using derived_map = scpp::hash_t<scpp::bool_t, derived_handle>;

template<class Map, class Key>
concept can_probe = requires(const Map& map, const Key& key) { scpp::isset(map, key); };
static_assert(can_probe<base_map, derived_handle>);
static_assert(can_probe<base_map, base_handle>);
static_assert(!can_probe<derived_map, base_handle>);
static_assert(!can_probe<base_map, scpp::shared_p<unrelated>>);

int main() {
	derived_handle key(std::make_shared<derived>());
	derived_handle missing(std::make_shared<derived>());
	base_map map;
	map.set(key, scpp::bool_t(false));
	const base_map& view = map;
	const auto owners = key.native_value().use_count();
	assert(scpp::isset(view, key).native_value()); // False payload is present.
	assert(!scpp::isset(view, missing).native_value());
	assert(key.native_value().use_count() == owners);
	assert(map.size() == 1); // Probe does not insert missing keys.
	base_handle base_key = key;
	assert(scpp::isset(view, base_key).native_value());
}
