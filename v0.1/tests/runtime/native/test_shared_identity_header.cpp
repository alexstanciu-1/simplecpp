#include "operators/identity/shared_identity.hpp"
#include <cassert>
#include <memory>

namespace {
struct prefix { int padding=3; };
struct base { int value=7; };
struct derived final : prefix,base {};
struct virtual_parent : virtual base {};
struct virtual_child final : virtual_parent {};
}

int main() {
    scpp::shared_p<derived> child(std::make_shared<derived>());
    scpp::shared_p<base> parent=child;
    scpp::shared_p<derived> other(std::make_shared<derived>());
    assert(static_cast<const void *>(child.get())!=static_cast<const void *>(parent.get()));
    assert(scpp::identical(child,parent).native_value());
    assert(scpp::identical(parent,child).native_value());
    assert(!scpp::identical(other,parent).native_value());
    scpp::shared_p<virtual_child> virtual_owner(std::make_shared<virtual_child>());
    scpp::shared_p<base> virtual_view=virtual_owner;
    assert(scpp::identical(virtual_owner,virtual_view).native_value());
    scpp::shared_p<derived> empty_child;
    scpp::shared_p<base> empty_parent;
    assert(scpp::identical(empty_child,empty_parent).native_value());
    assert(!scpp::identical(child,empty_parent).native_value());
}
