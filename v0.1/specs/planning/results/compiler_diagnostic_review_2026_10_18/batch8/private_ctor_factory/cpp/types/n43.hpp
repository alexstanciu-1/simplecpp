#pragma once
#include "scpp/shared_p.hpp"
struct record_C
{
public:
public:
	class scpp_construction_key { friend class record_C; scpp_construction_key() {} };
private:
	static scpp_construction_key scpp_constructor_key() { return scpp_construction_key(); }
public:
	record_C(record_C::scpp_construction_key);
public:
	static ::scpp::shared_p<record_C> implementation_make_for_type_C();
};

