#pragma once
struct record_S
{
public:
public:
	class scpp_construction_key { friend class record_S; scpp_construction_key() {} };
private:
	static scpp_construction_key scpp_constructor_key() { return scpp_construction_key(); }
public:
	record_S(record_S::scpp_construction_key, record_S local_other);
};

