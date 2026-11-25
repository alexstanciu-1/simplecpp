#include "modules/compiler/bindings.hpp"
#include "modules/compiler/storage_cursor.hpp"
#include "scpp/weak_p.hpp"
#include "scpp/hash_t.hpp"
#include <type_traits>

struct Node;
template<class T> constexpr unsigned traits() {
    return unsigned(std::is_default_constructible_v<T>)
        | (unsigned(std::is_copy_constructible_v<T>) << 1)
        | (unsigned(std::is_move_constructible_v<T>) << 2)
        | (unsigned(std::is_copy_assignable_v<T>) << 3)
        | (unsigned(std::is_move_assignable_v<T>) << 4)
        | (unsigned(std::is_nothrow_destructible_v<T>) << 5)
        | (unsigned(std::is_nothrow_default_constructible_v<T>) << 6)
        | (unsigned(std::is_nothrow_copy_constructible_v<T>) << 7)
        | (unsigned(std::is_nothrow_move_constructible_v<T>) << 8)
        | (unsigned(std::is_nothrow_copy_assignable_v<T>) << 9)
        | (unsigned(std::is_nothrow_move_assignable_v<T>) << 10);
}
using List = scpp::compiler::Storage<Node>;
using Keyed = scpp::compiler::Keyed_Storage<Node>;
using Multi = scpp::compiler::Key_Storage_List<Node>;
using Cursor = scpp::compiler::Storage_Cursor<Node>;
using Weak = scpp::weak_p<Node>;
using Vector = scpp::vector_t<scpp::shared_p<Node>>;
using Hash = scpp::hash_t<scpp::bool_t, scpp::shared_p<Node>>;
static_assert(sizeof(List) && alignof(List) && traits<List>());
static_assert(sizeof(Keyed) && alignof(Keyed) && traits<Keyed>());
static_assert(sizeof(Multi) && alignof(Multi) && traits<Multi>());
static_assert(sizeof(Cursor) && alignof(Cursor) && traits<Cursor>());
static_assert(sizeof(Weak) && alignof(Weak) && traits<Weak>());
static_assert(sizeof(Vector) && alignof(Vector) && traits<Vector>());
static_assert(sizeof(Hash) && alignof(Hash) && traits<Hash>());
struct Node { List children; Weak parent; };
int main() {
    auto parent = scpp::shared_p<Node>(std::make_shared<Node>());
    auto child = scpp::shared_p<Node>(std::make_shared<Node>());
    child->parent = parent;
    parent->children.append(child);
    auto retained = parent->children.read(0);
    return retained.native_value() == child.native_value()
        && retained->parent.lock().native_value() == parent.native_value() ? 0 : 1;
}
