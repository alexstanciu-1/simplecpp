#include "public_api_guard.hpp"
#include "scpp/operators/pre_increment_int64.hpp"
#include "scpp/operators/pre_increment_float64.hpp"
#include "scpp/operators/pre_increment_mixed.hpp"
#include "scpp/operators/pre_increment_dynamic.hpp"
#include "scpp/operators/post_increment_int64.hpp"
#include "scpp/operators/post_increment_float64.hpp"
#include "scpp/operators/post_increment_mixed.hpp"
#include "scpp/operators/post_increment_dynamic.hpp"
#include "scpp/operators/pre_decrement_int64.hpp"
#include "scpp/operators/pre_decrement_float64.hpp"
#include "scpp/operators/pre_decrement_mixed.hpp"
#include "scpp/operators/pre_decrement_dynamic.hpp"
#include "scpp/operators/post_decrement_int64.hpp"
#include "scpp/operators/post_decrement_float64.hpp"
#include "scpp/operators/post_decrement_mixed.hpp"
#include "scpp/operators/post_decrement_dynamic.hpp"
#include "scpp/identity/strict_identical_mixed.hpp"
#include "scpp/identity/strict_identical_dynamic.hpp"
#include <cstdio>
#include <cstdlib>
#include <cmath>
#include <limits>
#include <new>
#include <type_traits>
#include <utility>
using namespace scpp;
static unsigned allocations=0;
static bool counting=false;
void *operator new(std::size_t n) { if(counting) ++allocations; if(auto p=std::malloc(n?n:1)) return p; throw std::bad_alloc(); }
void operator delete(void *p) noexcept { std::free(p); }
void operator delete(void *p,std::size_t) noexcept { std::free(p); }
#define CHECK(x) do { if(!(x)) { std::fprintf(stderr,"update failure %d\n",__LINE__); std::abort(); } } while(false)
static_assert(std::is_same_v<decltype(update_pre_increment(std::declval<int64&>())), int64>);
static_assert(std::is_same_v<decltype(static_cast<int64(*)(int64&)>(&update_pre_increment)), int64(*)(int64&)>);
static_assert(std::is_same_v<decltype(update_pre_increment(std::declval<float64&>())), float64>);
static_assert(std::is_same_v<decltype(static_cast<float64(*)(float64&)>(&update_pre_increment)), float64(*)(float64&)>);
static_assert(std::is_same_v<decltype(update_pre_increment(std::declval<mixed&>())), mixed>);
static_assert(std::is_same_v<decltype(static_cast<mixed(*)(mixed&)>(&update_pre_increment)), mixed(*)(mixed&)>);
static_assert(std::is_same_v<decltype(update_pre_increment(std::declval<dynamic&>())), dynamic>);
static_assert(std::is_same_v<decltype(static_cast<dynamic(*)(dynamic&)>(&update_pre_increment)), dynamic(*)(dynamic&)>);
static_assert(std::is_same_v<decltype(update_post_increment(std::declval<int64&>())), int64>);
static_assert(std::is_same_v<decltype(static_cast<int64(*)(int64&)>(&update_post_increment)), int64(*)(int64&)>);
static_assert(std::is_same_v<decltype(update_post_increment(std::declval<float64&>())), float64>);
static_assert(std::is_same_v<decltype(static_cast<float64(*)(float64&)>(&update_post_increment)), float64(*)(float64&)>);
static_assert(std::is_same_v<decltype(update_post_increment(std::declval<mixed&>())), mixed>);
static_assert(std::is_same_v<decltype(static_cast<mixed(*)(mixed&)>(&update_post_increment)), mixed(*)(mixed&)>);
static_assert(std::is_same_v<decltype(update_post_increment(std::declval<dynamic&>())), dynamic>);
static_assert(std::is_same_v<decltype(static_cast<dynamic(*)(dynamic&)>(&update_post_increment)), dynamic(*)(dynamic&)>);
static_assert(std::is_same_v<decltype(update_pre_decrement(std::declval<int64&>())), int64>);
static_assert(std::is_same_v<decltype(static_cast<int64(*)(int64&)>(&update_pre_decrement)), int64(*)(int64&)>);
static_assert(std::is_same_v<decltype(update_pre_decrement(std::declval<float64&>())), float64>);
static_assert(std::is_same_v<decltype(static_cast<float64(*)(float64&)>(&update_pre_decrement)), float64(*)(float64&)>);
static_assert(std::is_same_v<decltype(update_pre_decrement(std::declval<mixed&>())), mixed>);
static_assert(std::is_same_v<decltype(static_cast<mixed(*)(mixed&)>(&update_pre_decrement)), mixed(*)(mixed&)>);
static_assert(std::is_same_v<decltype(update_pre_decrement(std::declval<dynamic&>())), dynamic>);
static_assert(std::is_same_v<decltype(static_cast<dynamic(*)(dynamic&)>(&update_pre_decrement)), dynamic(*)(dynamic&)>);
static_assert(std::is_same_v<decltype(update_post_decrement(std::declval<int64&>())), int64>);
static_assert(std::is_same_v<decltype(static_cast<int64(*)(int64&)>(&update_post_decrement)), int64(*)(int64&)>);
static_assert(std::is_same_v<decltype(update_post_decrement(std::declval<float64&>())), float64>);
static_assert(std::is_same_v<decltype(static_cast<float64(*)(float64&)>(&update_post_decrement)), float64(*)(float64&)>);
static_assert(std::is_same_v<decltype(update_post_decrement(std::declval<mixed&>())), mixed>);
static_assert(std::is_same_v<decltype(static_cast<mixed(*)(mixed&)>(&update_post_decrement)), mixed(*)(mixed&)>);
static_assert(std::is_same_v<decltype(update_post_decrement(std::declval<dynamic&>())), dynamic>);
static_assert(std::is_same_v<decltype(static_cast<dynamic(*)(dynamic&)>(&update_post_decrement)), dynamic(*)(dynamic&)>);
int main() { counting=true;
 { auto value=int64(10); auto &alias=value; int resolutions=0;
 const auto first=update_pre_increment(([&]() -> int64& { ++resolutions; return alias; })());
 CHECK(resolutions==1); CHECK(first.native_value()==11); CHECK(value.native_value()==11);
 const auto second=update_post_increment(value); CHECK(second.native_value()==11); CHECK(value.native_value()==12);
 const auto third=update_pre_decrement(value); CHECK(third.native_value()==11);
 const auto fourth=update_post_decrement(value); CHECK(fourth.native_value()==11); CHECK(value.native_value()==10);
 CHECK(first.native_value()==11); CHECK(second.native_value()==11); }
 { auto value=float64(1.5); auto &alias=value; int resolutions=0;
 const auto first=update_pre_increment(([&]() -> float64& { ++resolutions; return alias; })());
 CHECK(resolutions==1); CHECK(first.native_value()==2.5); CHECK(value.native_value()==2.5);
 const auto second=update_post_increment(value); CHECK(second.native_value()==2.5); CHECK(value.native_value()==3.5);
 const auto third=update_pre_decrement(value); CHECK(third.native_value()==2.5);
 const auto fourth=update_post_decrement(value); CHECK(fourth.native_value()==2.5); CHECK(value.native_value()==1.5);
 CHECK(first.native_value()==2.5); CHECK(second.native_value()==2.5); }
 { auto value=mixed(int64(10)); auto &alias=value; int resolutions=0;
 const auto first=update_pre_increment(([&]() -> mixed& { ++resolutions; return alias; })());
 CHECK(resolutions==1); CHECK(first.get_int().native_value()==11); CHECK(value.get_int().native_value()==11);
 const auto second=update_post_increment(value); CHECK(second.get_int().native_value()==11); CHECK(value.get_int().native_value()==12);
 const auto third=update_pre_decrement(value); CHECK(third.get_int().native_value()==11);
 const auto fourth=update_post_decrement(value); CHECK(fourth.get_int().native_value()==11); CHECK(value.get_int().native_value()==10);
 CHECK(first.get_int().native_value()==11); CHECK(second.get_int().native_value()==11); }
 { auto value=dynamic(int64(10)); auto &alias=value; int resolutions=0;
 const auto first=update_pre_increment(([&]() -> dynamic& { ++resolutions; return alias; })());
 CHECK(resolutions==1); CHECK(first.get_int().native_value()==11); CHECK(value.get_int().native_value()==11);
 const auto second=update_post_increment(value); CHECK(second.get_int().native_value()==11); CHECK(value.get_int().native_value()==12);
 const auto third=update_pre_decrement(value); CHECK(third.get_int().native_value()==11);
 const auto fourth=update_post_decrement(value); CHECK(fourth.get_int().native_value()==11); CHECK(value.get_int().native_value()==10);
 CHECK(first.get_int().native_value()==11); CHECK(second.get_int().native_value()==11); }
 { auto value=mixed(float64(1.5)); auto &alias=value; int resolutions=0;
 const auto first=update_pre_increment(([&]() -> mixed& { ++resolutions; return alias; })());
 CHECK(resolutions==1); CHECK(first.get_float().native_value()==2.5); CHECK(value.get_float().native_value()==2.5);
 const auto second=update_post_increment(value); CHECK(second.get_float().native_value()==2.5); CHECK(value.get_float().native_value()==3.5);
 const auto third=update_pre_decrement(value); CHECK(third.get_float().native_value()==2.5);
 const auto fourth=update_post_decrement(value); CHECK(fourth.get_float().native_value()==2.5); CHECK(value.get_float().native_value()==1.5);
 CHECK(first.get_float().native_value()==2.5); CHECK(second.get_float().native_value()==2.5); }
 { auto value=dynamic(float64(1.5)); auto &alias=value; int resolutions=0;
 const auto first=update_pre_increment(([&]() -> dynamic& { ++resolutions; return alias; })());
 CHECK(resolutions==1); CHECK(first.get_float().native_value()==2.5); CHECK(value.get_float().native_value()==2.5);
 const auto second=update_post_increment(value); CHECK(second.get_float().native_value()==2.5); CHECK(value.get_float().native_value()==3.5);
 const auto third=update_pre_decrement(value); CHECK(third.get_float().native_value()==2.5);
 const auto fourth=update_post_decrement(value); CHECK(fourth.get_float().native_value()==2.5); CHECK(value.get_float().native_value()==1.5);
 CHECK(first.get_float().native_value()==2.5); CHECK(second.get_float().native_value()==2.5); }
 { int64 low(std::numeric_limits<long long>::min()); CHECK(update_post_increment(low).native_value()==std::numeric_limits<long long>::min()); }
 { int64 high(std::numeric_limits<long long>::max()); CHECK(update_post_decrement(high).native_value()==std::numeric_limits<long long>::max()); }
 { float64 value(std::numeric_limits<double>::infinity()); CHECK(std::isinf(update_pre_increment(value).native_value())); }
 { float64 value(std::numeric_limits<double>::quiet_NaN()); CHECK(std::isnan(update_post_decrement(value).native_value())); }
 counting=false; CHECK(allocations==0);
 mixed invalid_mixed[]={mixed(null_t{}),mixed(boolean(false)),mixed(string("12")),make_mixed_table()};
 for(auto &bad:invalid_mixed) { const auto original=bad; bool failed=false; try { (void)update_pre_increment(bad); } catch (...) { failed=true; } CHECK(failed); CHECK(strict_identical(bad,original)); }
 for(auto &bad:invalid_mixed) { const auto original=bad; bool failed=false; try { (void)update_post_increment(bad); } catch (...) { failed=true; } CHECK(failed); CHECK(strict_identical(bad,original)); }
 for(auto &bad:invalid_mixed) { const auto original=bad; bool failed=false; try { (void)update_pre_decrement(bad); } catch (...) { failed=true; } CHECK(failed); CHECK(strict_identical(bad,original)); }
 for(auto &bad:invalid_mixed) { const auto original=bad; bool failed=false; try { (void)update_post_decrement(bad); } catch (...) { failed=true; } CHECK(failed); CHECK(strict_identical(bad,original)); }
 dynamic invalid_dynamic[]={dynamic(null_t{}),dynamic(boolean(false)),dynamic(string("12")),make_dynamic_container()};
 for(auto &bad:invalid_dynamic) { const auto original=bad; bool failed=false; try { (void)update_pre_increment(bad); } catch (...) { failed=true; } CHECK(failed); CHECK(strict_identical(bad,original)); }
 for(auto &bad:invalid_dynamic) { const auto original=bad; bool failed=false; try { (void)update_post_increment(bad); } catch (...) { failed=true; } CHECK(failed); CHECK(strict_identical(bad,original)); }
 for(auto &bad:invalid_dynamic) { const auto original=bad; bool failed=false; try { (void)update_pre_decrement(bad); } catch (...) { failed=true; } CHECK(failed); CHECK(strict_identical(bad,original)); }
 for(auto &bad:invalid_dynamic) { const auto original=bad; bool failed=false; try { (void)update_post_decrement(bad); } catch (...) { failed=true; } CHECK(failed); CHECK(strict_identical(bad,original)); }
 std::puts("PASS: prefix/postfix owned snapshots, alias updates, destination once, kind-preserving failure; successful allocations=0");
}
