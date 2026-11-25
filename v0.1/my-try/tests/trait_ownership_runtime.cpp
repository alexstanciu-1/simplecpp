#include "scpp/memory.hpp"
#include <cassert>
struct Base { static inline int destroyed=0; virtual ~Base(){++destroyed;} };
struct Child:Base {};
int main(){
 auto a=scpp::unique<Child>(); auto b=scpp::unique<Child>(); auto *identity=a.get();
 auto choose=[&]() -> scpp::unique_p<Base> {if(true)return std::move(a);return std::move(b);};
 auto result=choose();assert(!a && b && result.get()==identity);result.reset();assert(Base::destroyed==1);
 auto owner=scpp::shared<Child>();auto weak=scpp::weak(owner);scpp::weak_p<Base> view=weak;
 assert(view.use_count()==1);owner.reset();assert(static_cast<bool>(view.expired()));
 auto empty=scpp::unique_p<Base>();auto fallback=[&]() -> scpp::unique_p<Base> {if(empty)return std::move(empty);return std::move(b);};
 auto picked=fallback();assert(!b && picked);picked.reset();assert(Base::destroyed==3);
}
