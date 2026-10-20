#pragma once
struct record_B
{
public:
public:
	class scpp_construction_key { friend class record_B; scpp_construction_key() {} };
protected:
	static scpp_construction_key scpp_constructor_key() { return scpp_construction_key(); }
public:
	record_B(record_B::scpp_construction_key);
};

