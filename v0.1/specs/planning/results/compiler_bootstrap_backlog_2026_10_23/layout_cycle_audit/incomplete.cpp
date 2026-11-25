#include "modules/compiler/bindings.hpp"
#include <type_traits>
struct Node;
using Children = scpp::compiler::Storage<Node>;
static_assert(sizeof(Children) > 0);
static_assert(std::is_default_constructible_v<Children>);
static_assert(std::is_copy_constructible_v<Children>);
static_assert(std::is_move_constructible_v<Children>);
static_assert(std::is_copy_assignable_v<Children>);
static_assert(std::is_move_assignable_v<Children>);
struct Node { Children children; };
int main() { Node parent; auto child = scpp::shared_p<Node>(std::make_shared<Node>()); parent.children.append(child); return parent.children.count() == 1 ? 0 : 1; }
