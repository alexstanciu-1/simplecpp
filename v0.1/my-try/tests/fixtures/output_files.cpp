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
	const auto staged = scpp::fs::output_stage(root).require_value("stage");
	const auto staged_path = root.native_value() + "/" + staged.native_value();
	{ std::ofstream stream(staged_path); stream << "#!/bin/sh\nexit 7\n"; }
	struct stat staged_stat{}, promoted_stat{};
	assert(::stat(staged_path.c_str(), &staged_stat) == 0);
	assert(scpp::fs::output_promote(root,staged,string_t("program"),string_t(""),scpp::bool_t(true)).require_value("promote").native_value());
	assert(::stat((root.native_value()+"/program").c_str(), &promoted_stat) == 0);
	assert(staged_stat.st_ino == promoted_stat.st_ino && (promoted_stat.st_mode & 07777) == 0700);
	const auto digest = scpp::fs::output_digest(root,string_t("program")).require_value("digest");
	assert(digest.native_value() == scpp::fs::output_sha256(string_t("#!/bin/sh\nexit 7\n")).native_value());
	const auto duplicate = scpp::fs::output_stage(root).require_value("duplicate");
	{ std::ofstream stream(root.native_value()+"/"+duplicate.native_value()); stream << "#!/bin/sh\nexit 7\n"; }
	assert(!scpp::fs::output_promote(root,duplicate,string_t("program"),digest,scpp::bool_t(true)).require_value("unchanged").native_value());
	assert(::stat((root.native_value()+"/program").c_str(), &promoted_stat) == 0 && staged_stat.st_ino == promoted_stat.st_ino);
	const auto aborted = scpp::fs::output_stage(root).require_value("aborted");
	assert(!scpp::fs::output_promote(root,aborted,string_t("program"),string_t(""),scpp::bool_t(true)).has_value().native_value());
	assert(scpp::fs::output_discard(root,aborted).require_value("discard").native_value());
	assert(!scpp::fs::output_discard(root,aborted).require_value("discard again").native_value());
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
	for (std::size_t length:{0,1,3,55,56,63,64,65,127,128,10000,131071,131072,131073}) {
		std::string input;
		for (std::size_t i=0;i<length;++i) input.push_back(static_cast<char>(i%256));
		scpp::fs::output_write(root,string_t("chunks.cpp"),string_t(input)).require_value("chunks");
		assert(scpp::fs::output_digest(root,string_t("chunks.cpp")).require_value("chunk digest").native_value() == scpp::fs::output_detail::sha256(input));
		assert(scpp::fs::input_digest(string_t(root.native_value()+"/chunks.cpp")).require_value("input digest").native_value() == scpp::fs::output_detail::sha256(input));
		std::cout << scpp::fs::output_sha256(string_t(input)).native_value() << '\n';
		scpp::fs::output_detail::sha256_state streaming;
		for (std::size_t offset=0; offset<input.size(); offset+=7) streaming.update(std::string_view(input).substr(offset,7));
		assert(streaming.finish() == scpp::fs::output_detail::sha256(input));
	}
}
