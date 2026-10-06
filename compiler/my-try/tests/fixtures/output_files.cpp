#include "modules/filesystem/output_files.hpp"
#include <cassert>
#include <iostream>
#include <sys/stat.h>
using scpp::string_t;
int main(int argc,char **argv) {
	assert(argc==2);
	const auto root=scpp::fs::output_root(string_t(argv[1])).require_value("root");
	auto missing=scpp::fs::output_read(root,string_t("absent/file"));
	assert(!missing.require_value("missing").has_value().native_value());
	const string_t empty_path("empty.cpp"), empty_bytes("");
	assert(scpp::fs::output_write(root,empty_path,empty_bytes).require_value("empty write").native_value());
	assert(scpp::fs::output_read(root,empty_path).require_value("empty read").value().native_value().empty());
	assert(!scpp::fs::output_write(root,empty_path,empty_bytes).require_value("empty no-op").native_value());
	assert(scpp::fs::output_remove(root,empty_path,empty_bytes).require_value("empty remove").native_value());
	const string_t path("units/data.cpp"), bytes(std::string("abc\0def",7));
	assert(scpp::fs::output_write(root,path,bytes).require_value("write").native_value());
	struct stat before{},after{};
	const auto full=root.native_value()+"/units/data.cpp";
	assert(::stat(full.c_str(),&before)==0);
	assert(!scpp::fs::output_write(root,path,bytes).require_value("no-op").native_value());
	assert(::stat(full.c_str(),&after)==0);
	assert(before.st_ino==after.st_ino && before.st_mtim.tv_sec==after.st_mtim.tv_sec && before.st_mtim.tv_nsec==after.st_mtim.tv_nsec);
	assert(scpp::fs::output_read(root,path).require_value("read").value().native_value()==bytes.native_value());
	assert(!scpp::fs::output_remove(root,path,string_t("wrong")).has_value().native_value());
	for (const auto *bad:{"../escape","/absolute","a//b","a/./b","a/../b","link","pipe","sub/link/file"}) {
		assert(!scpp::fs::output_write(root,string_t(bad),bytes).has_value().native_value());
		assert(!scpp::fs::output_read(root,string_t(bad)).has_value().native_value());
		assert(!scpp::fs::output_remove(root,string_t(bad),bytes).has_value().native_value());
	}
	assert(scpp::fs::output_remove(root,path,bytes).require_value("remove").native_value());
	assert(!scpp::fs::output_remove(root,path,bytes).require_value("missing remove").native_value());
	for (std::size_t length:{0,1,3,55,56,63,64,65,127,128,10000}) {
		std::string input;
		for (std::size_t i=0;i<length;++i) input.push_back(static_cast<char>(i%256));
		std::cout << scpp::fs::output_sha256(string_t(input)).native_value() << '\n';
	}
}
