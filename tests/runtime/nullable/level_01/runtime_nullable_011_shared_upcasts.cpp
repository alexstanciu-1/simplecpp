#include "scpp/shared_p.hpp"
#include "scpp/cast.hpp"
#include <cassert>

struct Base { virtual ~Base() = default; };
struct Derived : Base {};
struct Other {};
struct Private : private Base {};
struct Left : Base {};
struct Right : Base {};
struct Ambiguous : Left, Right {};
using B = scpp::shared_p<Base>;
using D = scpp::shared_p<Derived>;
using NB = scpp::nullable<B>;
using ND = scpp::nullable<D>;

static_assert(std::is_convertible_v<D, B>);
static_assert(std::is_convertible_v<D, NB>);
static_assert(std::is_convertible_v<ND, B>);
static_assert(std::is_convertible_v<ND, NB>);
static_assert(std::is_convertible_v<ND, D>);
static_assert(!std::is_convertible_v<NB, D>);
static_assert(!std::is_convertible_v<NB, ND>);
static_assert(!std::is_convertible_v<scpp::shared_p<Other>, NB>);
static_assert(!std::is_convertible_v<scpp::shared_p<Private>, NB>);
static_assert(!std::is_convertible_v<scpp::shared_p<Ambiguous>, NB>);
static_assert(!std::is_convertible_v<D &, B &>);
static_assert(!std::is_convertible_v<ND &, B &>);
static_assert(!scpp::detail::shared_boundary_convertible<scpp::nullable<int>, ND>);

void choose(B);
void choose(NB);
template<class T> concept unambiguous_choice = requires(T value) { choose(value); };
static_assert(!unambiguous_choice<ND>);

B required(B value) { return value; }
NB optional(NB value) { return value; }
B return_required(ND value) { return value; }
NB return_optional(ND value) { return value; }

int main() {
	D source(std::make_shared<Derived>());
	ND present(source), absent;
	B base = required(present);
	assert(base.get() == source.get());
	assert(!base.native_value().owner_before(source.native_value()));
	assert(!source.native_value().owner_before(base.native_value()));
	assert(dynamic_cast<Derived *>(base.get()) == source.get());
	assert(return_required(present).get() == source.get());
	assert(optional(source).value().get() == source.get());
	assert(return_optional(present).value().get() == source.get());
	assert(!return_optional(absent).has_value().native_value());
	NB destination = present;
	destination = source;
	destination = present;
	assert(destination.value().get() == source.get());
	destination = absent;
	assert(!destination.has_value().native_value());
	base = present;
	bool threw = false;
	try { base = absent; } catch (const scpp::runtime_error &) { threw = true; }
	assert(threw && base.get() == source.get());
	D exact = source;
	threw = false;
	try { exact = absent; } catch (const scpp::runtime_error &) { threw = true; }
	assert(threw && exact.get() == source.get());
	assert(scpp::cast<B>(source).get() == source.get());
	assert(scpp::cast<B>(present).get() == source.get());
	assert(scpp::required_cast<B>(present).get() == source.get());
	assert(scpp::cast<NB>(source).value().get() == source.get());
	assert(scpp::cast<NB>(present).value().get() == source.get());
	assert(!scpp::cast<NB>(absent).has_value().native_value());
	threw = false;
	try { (void)scpp::required_cast<B>(absent); }
	catch (const scpp::runtime_error &) { threw = true; }
	assert(threw);
	ND empty_handle{D{}};
	assert(scpp::cast<NB>(empty_handle).has_value().native_value());
	assert(!required(empty_handle).has_value().native_value());
}
