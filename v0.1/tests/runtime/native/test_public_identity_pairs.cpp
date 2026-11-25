#include "public_api_guard.hpp"
#include "scpp/identity/strict_identical_boxed_mixed_boolean.hpp"
#include "scpp/identity/strict_identical_boxed_mixed_int64.hpp"
#include "scpp/identity/strict_identical_boxed_mixed_float64.hpp"
#include "scpp/identity/strict_identical_boxed_mixed_string.hpp"
#include "scpp/identity/strict_identical_boxed_mixed_null.hpp"
#include "scpp/identity/strict_identical_boxed_dynamic_boolean.hpp"
#include "scpp/identity/strict_identical_boxed_dynamic_int64.hpp"
#include "scpp/identity/strict_identical_boxed_dynamic_float64.hpp"
#include "scpp/identity/strict_identical_boxed_dynamic_string.hpp"
#include "scpp/identity/strict_identical_boxed_dynamic_null.hpp"
#include "scpp/identity/strict_not_identical_boxed_mixed_boolean.hpp"
#include "scpp/identity/strict_not_identical_boxed_mixed_int64.hpp"
#include "scpp/identity/strict_not_identical_boxed_mixed_float64.hpp"
#include "scpp/identity/strict_not_identical_boxed_mixed_string.hpp"
#include "scpp/identity/strict_not_identical_boxed_mixed_null.hpp"
#include "scpp/identity/strict_not_identical_boxed_dynamic_boolean.hpp"
#include "scpp/identity/strict_not_identical_boxed_dynamic_int64.hpp"
#include "scpp/identity/strict_not_identical_boxed_dynamic_float64.hpp"
#include "scpp/identity/strict_not_identical_boxed_dynamic_string.hpp"
#include "scpp/identity/strict_not_identical_boxed_dynamic_null.hpp"
#include <cstdio>
#include <cstdlib>
#include <limits>
#include <new>
using namespace scpp;
static unsigned allocations=0;
static bool counting=false;
void *operator new(std::size_t n) { if(counting) ++allocations; if(auto p=std::malloc(n?n:1)) return p; throw std::bad_alloc(); }
void operator delete(void *p) noexcept { std::free(p); }
void operator delete(void *p,std::size_t) noexcept { std::free(p); }
#define CHECK(x) do { if(!(x)) { std::fprintf(stderr,"pair failure %d\n",__LINE__); std::abort(); } } while(false)
int main() {
 const string text("long scalar identity compares borrowed bytes without allocating a boxed string wrapper");
 const string binary("x\0y",uint64(3));
 const float64 nan(std::numeric_limits<double>::quiet_NaN());
 const mixed mixed_values[]={mixed{},mixed(boolean(false)),mixed(boolean(true)),mixed(int64(7)),mixed(float64(7.0)),mixed(float64(-0.0)),mixed(text),mixed(binary),mixed(nan),make_mixed_table()};
 const dynamic dynamic_values[]={dynamic{},dynamic(boolean(false)),dynamic(boolean(true)),dynamic(int64(7)),dynamic(float64(7.0)),dynamic(float64(-0.0)),dynamic(text),dynamic(binary),dynamic(nan),make_dynamic_container()};
 counting=true;
 for(int i=0;i<10;++i) { const auto &value=mixed_values[i];
 CHECK(static_cast<bool>(strict_identical(value,null_t{}))==(i==0));
 CHECK(static_cast<bool>(strict_not_identical(value,null_t{}))!=(i==0));
 CHECK(static_cast<bool>(strict_identical(value,boolean(false)))==(i==1));
 CHECK(static_cast<bool>(strict_not_identical(value,boolean(false)))!=(i==1));
 CHECK(static_cast<bool>(strict_identical(value,boolean(true)))==(i==2));
 CHECK(static_cast<bool>(strict_not_identical(value,boolean(true)))!=(i==2));
 CHECK(static_cast<bool>(strict_identical(value,int64(7)))==(i==3));
 CHECK(static_cast<bool>(strict_not_identical(value,int64(7)))!=(i==3));
 CHECK(static_cast<bool>(strict_identical(value,float64(7.0)))==(i==4));
 CHECK(static_cast<bool>(strict_not_identical(value,float64(7.0)))!=(i==4));
 CHECK(static_cast<bool>(strict_identical(value,float64(0.0)))==(i==5));
 CHECK(static_cast<bool>(strict_not_identical(value,float64(0.0)))!=(i==5));
 CHECK(static_cast<bool>(strict_identical(value,text))==(i==6));
 CHECK(static_cast<bool>(strict_not_identical(value,text))!=(i==6));
 CHECK(static_cast<bool>(strict_identical(value,binary))==(i==7));
 CHECK(static_cast<bool>(strict_not_identical(value,binary))!=(i==7));
 CHECK(static_cast<bool>(strict_identical(value,nan))==(false));
 CHECK(static_cast<bool>(strict_not_identical(value,nan))!=(false));
 }
 for(int i=0;i<10;++i) { const auto &value=dynamic_values[i];
 CHECK(static_cast<bool>(strict_identical(value,null_t{}))==(i==0));
 CHECK(static_cast<bool>(strict_not_identical(value,null_t{}))!=(i==0));
 CHECK(static_cast<bool>(strict_identical(value,boolean(false)))==(i==1));
 CHECK(static_cast<bool>(strict_not_identical(value,boolean(false)))!=(i==1));
 CHECK(static_cast<bool>(strict_identical(value,boolean(true)))==(i==2));
 CHECK(static_cast<bool>(strict_not_identical(value,boolean(true)))!=(i==2));
 CHECK(static_cast<bool>(strict_identical(value,int64(7)))==(i==3));
 CHECK(static_cast<bool>(strict_not_identical(value,int64(7)))!=(i==3));
 CHECK(static_cast<bool>(strict_identical(value,float64(7.0)))==(i==4));
 CHECK(static_cast<bool>(strict_not_identical(value,float64(7.0)))!=(i==4));
 CHECK(static_cast<bool>(strict_identical(value,float64(0.0)))==(i==5));
 CHECK(static_cast<bool>(strict_not_identical(value,float64(0.0)))!=(i==5));
 CHECK(static_cast<bool>(strict_identical(value,text))==(i==6));
 CHECK(static_cast<bool>(strict_not_identical(value,text))!=(i==6));
 CHECK(static_cast<bool>(strict_identical(value,binary))==(i==7));
 CHECK(static_cast<bool>(strict_not_identical(value,binary))!=(i==7));
 CHECK(static_cast<bool>(strict_identical(value,nan))==(false));
 CHECK(static_cast<bool>(strict_not_identical(value,nan))!=(false));
 }
 counting=false; CHECK(allocations==0); std::puts("PASS: boxed/plain identity kinds, bytes, NaN, zero, containers; allocations=0");
}
