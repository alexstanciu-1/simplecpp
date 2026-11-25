#include "public_api_guard.hpp"
#include "scpp/operators/bit_and_int64.hpp"
#include "scpp/operators/bit_and_mixed.hpp"
#include "scpp/operators/bit_and_dynamic.hpp"
#include "scpp/operators/bit_or_int64.hpp"
#include "scpp/operators/bit_or_mixed.hpp"
#include "scpp/operators/bit_or_dynamic.hpp"
#include "scpp/operators/bit_xor_int64.hpp"
#include "scpp/operators/bit_xor_mixed.hpp"
#include "scpp/operators/bit_xor_dynamic.hpp"
#include "scpp/operators/shift_left_int64.hpp"
#include "scpp/operators/shift_left_mixed.hpp"
#include "scpp/operators/shift_left_dynamic.hpp"
#include "scpp/operators/shift_right_int64.hpp"
#include "scpp/operators/shift_right_mixed.hpp"
#include "scpp/operators/shift_right_dynamic.hpp"
#include "scpp/operators/power_int64.hpp"
#include "scpp/exception_info.hpp"
#include <cstdio>
#include <cstring>
#include <exception>
#include <initializer_list>
#include <cstdlib>
#include <limits>
#include <new>
using namespace scpp;
static unsigned allocations=0;
static bool counting=false;
void *operator new(std::size_t n) { if(counting) ++allocations; if(auto p=std::malloc(n?n:1)) return p; throw std::bad_alloc(); }
void operator delete(void *p) noexcept { std::free(p); }
void operator delete(void *p,std::size_t) noexcept { std::free(p); }
#define CHECK(x) do { if(!(x)) { std::fprintf(stderr,"special integer failure %d\n",__LINE__); std::abort(); } } while(false)
int main() {
 const auto minimum=std::numeric_limits<long long>::min(), maximum=std::numeric_limits<long long>::max();
 counting=true;
 CHECK(integer_bit_and(int64(12),int64(10)).native_value()==8);
 CHECK(integer_bit_or(int64(12),int64(10)).native_value()==14);
 CHECK(integer_bit_xor(int64(12),int64(10)).native_value()==6);
 CHECK(integer_bit_and(int64(-1),int64(maximum)).native_value()==maximum);
 CHECK(integer_shift_left(int64(1),int64(62)).native_value()==4611686018427387904LL);
 CHECK(integer_shift_right(int64(minimum),int64(63)).native_value()==-1);
 CHECK(integer_shift_left(int64(0),int64(63)).native_value()==0);
 CHECK(integer_shift_right(int64(maximum),int64(0)).native_value()==maximum);
 CHECK(integer_bit_and(mixed(int64(12)),mixed(int64(10))).get_int().native_value()==8);
 CHECK(integer_bit_or(mixed(int64(12)),mixed(int64(10))).get_int().native_value()==14);
 CHECK(integer_bit_xor(mixed(int64(12)),mixed(int64(10))).get_int().native_value()==6);
 CHECK(integer_bit_and(mixed(int64(-1)),mixed(int64(maximum))).get_int().native_value()==maximum);
 CHECK(integer_shift_left(mixed(int64(1)),mixed(int64(62))).get_int().native_value()==4611686018427387904LL);
 CHECK(integer_shift_right(mixed(int64(minimum)),mixed(int64(63))).get_int().native_value()==-1);
 CHECK(integer_shift_left(mixed(int64(0)),mixed(int64(63))).get_int().native_value()==0);
 CHECK(integer_shift_right(mixed(int64(maximum)),mixed(int64(0))).get_int().native_value()==maximum);
 CHECK(integer_bit_and(dynamic(int64(12)),dynamic(int64(10))).get_int().native_value()==8);
 CHECK(integer_bit_or(dynamic(int64(12)),dynamic(int64(10))).get_int().native_value()==14);
 CHECK(integer_bit_xor(dynamic(int64(12)),dynamic(int64(10))).get_int().native_value()==6);
 CHECK(integer_bit_and(dynamic(int64(-1)),dynamic(int64(maximum))).get_int().native_value()==maximum);
 CHECK(integer_shift_left(dynamic(int64(1)),dynamic(int64(62))).get_int().native_value()==4611686018427387904LL);
 CHECK(integer_shift_right(dynamic(int64(minimum)),dynamic(int64(63))).get_int().native_value()==-1);
 CHECK(integer_shift_left(dynamic(int64(0)),dynamic(int64(63))).get_int().native_value()==0);
 CHECK(integer_shift_right(dynamic(int64(maximum)),dynamic(int64(0))).get_int().native_value()==maximum);
 CHECK(integer_power(int64(0),int64(0)).native_value()==1);
 CHECK(integer_power(int64(-2),int64(63)).native_value()==minimum);
 CHECK(integer_power(int64(maximum),int64(1)).native_value()==maximum);
 CHECK(integer_power(int64(minimum),int64(1)).native_value()==minimum);
 CHECK(integer_power(int64(-1),int64(maximum)).native_value()==-1);
 for(long long base=-10;base<=10;++base) {
    long long expected=1;
    for(long long exponent=0;exponent<=10;++exponent) {
        CHECK(integer_power(int64(base),int64(exponent)).native_value()==expected);
        expected*=base;
    }
 }
 counting=false; CHECK(allocations==0);
 for(long long count : {-1LL,64LL,maximum}) { bool failed=false; try { (void)integer_shift_left(int64(1),int64(count)); } catch (const std::exception &error) { failed=true; CHECK(std::strcmp(error.what(),"Shift count must be nonnegative and less than the left operand bit width")==0); } CHECK(failed); }
 for(long long count : {-1LL,64LL,maximum}) { bool failed=false; try { (void)integer_shift_right(int64(1),int64(count)); } catch (const std::exception &error) { failed=true; CHECK(std::strcmp(error.what(),"Shift count must be nonnegative and less than the left operand bit width")==0); } CHECK(failed); }
 for(long long count : {-1LL,64LL,maximum}) { bool failed=false; try { (void)integer_shift_left(mixed(int64(1)),mixed(int64(count))); } catch (const std::exception &error) { failed=true; CHECK(std::strcmp(error.what(),"Shift count must be nonnegative and less than the left operand bit width")==0); } CHECK(failed); }
 for(long long count : {-1LL,64LL,maximum}) { bool failed=false; try { (void)integer_shift_right(mixed(int64(1)),mixed(int64(count))); } catch (const std::exception &error) { failed=true; CHECK(std::strcmp(error.what(),"Shift count must be nonnegative and less than the left operand bit width")==0); } CHECK(failed); }
 for(long long count : {-1LL,64LL,maximum}) { bool failed=false; try { (void)integer_shift_left(dynamic(int64(1)),dynamic(int64(count))); } catch (const std::exception &error) { failed=true; CHECK(std::strcmp(error.what(),"Shift count must be nonnegative and less than the left operand bit width")==0); } CHECK(failed); }
 for(long long count : {-1LL,64LL,maximum}) { bool failed=false; try { (void)integer_shift_right(dynamic(int64(1)),dynamic(int64(count))); } catch (const std::exception &error) { failed=true; CHECK(std::strcmp(error.what(),"Shift count must be nonnegative and less than the left operand bit width")==0); } CHECK(failed); }
 const mixed invalid_mixed[]={mixed(null_t{}),mixed(boolean(false)),mixed(float64(1.0)),mixed(string("1")),make_mixed_table()};
 for(const auto &bad:invalid_mixed) { bool failed=false; try { (void)integer_bit_and(bad,mixed(int64(1))); } catch (...) { failed=true; } CHECK(failed); failed=false; try { (void)integer_bit_and(mixed(int64(1)),bad); } catch (...) { failed=true; } CHECK(failed); }
 for(const auto &bad:invalid_mixed) { bool failed=false; try { (void)integer_bit_or(bad,mixed(int64(1))); } catch (...) { failed=true; } CHECK(failed); failed=false; try { (void)integer_bit_or(mixed(int64(1)),bad); } catch (...) { failed=true; } CHECK(failed); }
 for(const auto &bad:invalid_mixed) { bool failed=false; try { (void)integer_bit_xor(bad,mixed(int64(1))); } catch (...) { failed=true; } CHECK(failed); failed=false; try { (void)integer_bit_xor(mixed(int64(1)),bad); } catch (...) { failed=true; } CHECK(failed); }
 for(const auto &bad:invalid_mixed) { bool failed=false; try { (void)integer_shift_left(bad,mixed(int64(1))); } catch (...) { failed=true; } CHECK(failed); failed=false; try { (void)integer_shift_left(mixed(int64(1)),bad); } catch (...) { failed=true; } CHECK(failed); }
 for(const auto &bad:invalid_mixed) { bool failed=false; try { (void)integer_shift_right(bad,mixed(int64(1))); } catch (...) { failed=true; } CHECK(failed); failed=false; try { (void)integer_shift_right(mixed(int64(1)),bad); } catch (...) { failed=true; } CHECK(failed); }
 const dynamic invalid_dynamic[]={dynamic(null_t{}),dynamic(boolean(false)),dynamic(float64(1.0)),dynamic(string("1")),make_dynamic_container()};
 for(const auto &bad:invalid_dynamic) { bool failed=false; try { (void)integer_bit_and(bad,dynamic(int64(1))); } catch (...) { failed=true; } CHECK(failed); failed=false; try { (void)integer_bit_and(dynamic(int64(1)),bad); } catch (...) { failed=true; } CHECK(failed); }
 for(const auto &bad:invalid_dynamic) { bool failed=false; try { (void)integer_bit_or(bad,dynamic(int64(1))); } catch (...) { failed=true; } CHECK(failed); failed=false; try { (void)integer_bit_or(dynamic(int64(1)),bad); } catch (...) { failed=true; } CHECK(failed); }
 for(const auto &bad:invalid_dynamic) { bool failed=false; try { (void)integer_bit_xor(bad,dynamic(int64(1))); } catch (...) { failed=true; } CHECK(failed); failed=false; try { (void)integer_bit_xor(dynamic(int64(1)),bad); } catch (...) { failed=true; } CHECK(failed); }
 for(const auto &bad:invalid_dynamic) { bool failed=false; try { (void)integer_shift_left(bad,dynamic(int64(1))); } catch (...) { failed=true; } CHECK(failed); failed=false; try { (void)integer_shift_left(dynamic(int64(1)),bad); } catch (...) { failed=true; } CHECK(failed); }
 for(const auto &bad:invalid_dynamic) { bool failed=false; try { (void)integer_shift_right(bad,dynamic(int64(1))); } catch (...) { failed=true; } CHECK(failed); failed=false; try { (void)integer_shift_right(dynamic(int64(1)),bad); } catch (...) { failed=true; } CHECK(failed); }
 { bool failed=false; try { (void)integer_power(int64(2),int64(-1)); } catch (...) { failed=true; auto error=capture_runtime_exception(); CHECK(error.code().equals("power_negative_exponent")); CHECK(error.component().equals("scpp::pow")); CHECK(error.operation().equals("**")); } CHECK(failed); }
 { bool failed=false; try { (void)integer_power(int64(0),int64(-1)); } catch (...) { failed=true; auto error=capture_runtime_exception(); CHECK(error.code().equals("power_negative_exponent")); CHECK(error.component().equals("scpp::pow")); CHECK(error.operation().equals("**")); } CHECK(failed); }
 { bool failed=false; try { (void)integer_power(int64(2),int64(63)); } catch (...) { failed=true; auto error=capture_runtime_exception(); CHECK(error.code().equals("power_overflow")); CHECK(error.component().equals("scpp::pow")); CHECK(error.operation().equals("**")); } CHECK(failed); }
 { bool failed=false; try { (void)integer_power(int64(-2),int64(64)); } catch (...) { failed=true; auto error=capture_runtime_exception(); CHECK(error.code().equals("power_overflow")); CHECK(error.component().equals("scpp::pow")); CHECK(error.operation().equals("**")); } CHECK(failed); }
 { bool failed=false; try { (void)integer_power(int64(maximum),int64(2)); } catch (...) { failed=true; auto error=capture_runtime_exception(); CHECK(error.code().equals("power_overflow")); CHECK(error.component().equals("scpp::pow")); CHECK(error.operation().equals("**")); } CHECK(failed); }
 std::puts("PASS: integer/boxed bitwise and checked shifts, power boundaries/errors; successful allocations=0");
}
