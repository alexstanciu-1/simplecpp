#pragma once
#include <cerrno>
#include <cstdio>
#include <cstdlib>
#include <filesystem>
#include <fstream>
#include <iterator>
#include <stdexcept>
#include <string>
#include <system_error>
#include "scpp/bool_t.hpp"
#include "scpp/string_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/result.hpp"
#include "detail/output_sha256.hpp"
#ifdef __linux__
#include <fcntl.h>
#include <sys/stat.h>
#include <unistd.h>
#endif

namespace scpp::fs::output_detail {
namespace files = std::filesystem;
inline void require_linux() {
#ifndef __linux__
	throw std::runtime_error("Output publication requires Linux");
#endif
}
inline files::file_status path_status(const files::path &path) {
	std::error_code error;
	const auto value=files::symlink_status(path,error);
	if (error && error!=std::errc::no_such_file_or_directory) throw std::system_error(error,"output stat");
	return value;
}
inline void directories(const files::path &path, bool create, bool missing_leaf=false) {
	files::path current="/";
	for (const auto &part:path.relative_path()) {
		if (part.empty() || part=="." || part=="..") throw std::runtime_error("Invalid output directory component");
		current/=part;
		const auto info=path_status(current);
		if (!files::exists(info)) {
			if (create) {
				if (!files::create_directory(current)) throw std::runtime_error("Cannot create output directory");
				files::permissions(current,files::perms::owner_all);
			} else if (missing_leaf && current==path) return;
			else throw std::runtime_error("Missing output directory");
		} else if (!files::is_directory(info)) throw std::runtime_error("Unsafe output directory");
	}
}
inline void root_name(const std::string &root) {
	require_linux();
	if (root.empty() || root.front()!='/' || root=="/" || root.find('\0')!=std::string::npos)
		throw std::runtime_error("Invalid output root");
	// Match the portable facade rather than accepting filesystem normalization.
	if (root.find("//")!=std::string::npos) throw std::runtime_error("Invalid output root component");
}
inline files::path target(const std::string &root,const std::string &relative,bool create) {
	root_name(root); directories(root,false);
	if (relative.empty() || relative.front()=='/' || relative.back()=='/') throw std::runtime_error("Invalid relative output path");
	for (unsigned char c:relative) {
		if (!((c>='a'&&c<='z')||(c>='A'&&c<='Z')||(c>='0'&&c<='9')||c=='_'||c=='-'||c=='.'||c=='/'))
			throw std::runtime_error("Invalid relative output character");
	}
	if (relative.find("//")!=std::string::npos) throw std::runtime_error("Invalid relative output component");
	files::path path=root;
	for (const auto &part:files::path(relative)) {
		if (part=="."||part=="..") throw std::runtime_error("Invalid relative output component");
		path/=part;
	}
	if (create) directories(path.parent_path(),true);
	else {
		files::path parent=root;
		for (const auto &part:files::path(relative).parent_path()) {
			parent/=part; const auto info=path_status(parent);
			if (!files::exists(info)) return path;
			if (!files::is_directory(info)) throw std::runtime_error("Unsafe output parent");
		}
	}
	const auto info=path_status(path);
	if (files::exists(info) && !files::is_regular_file(info)) throw std::runtime_error("Output target is not a regular file");
	return path;
}
inline nullable<string_t> read(const files::path &path) {
	const auto info=path_status(path);
	if (!files::exists(info)) return {};
	if (!files::is_regular_file(info)) throw std::runtime_error("Output target is not a regular file");
	std::ifstream stream(path,std::ios::binary);
	if (!stream) throw std::runtime_error("Cannot open output file");
	std::string bytes((std::istreambuf_iterator<char>(stream)),{});
	if (stream.bad()) throw std::runtime_error("Cannot read output file");
	return string_t(std::move(bytes));
}
inline error_t failure(const std::exception &error) { return error_t(string_t(error.what())); }
} // namespace scpp::fs::output_detail

namespace scpp::fs {
inline result<string_t> output_root(const string_t &name) {
	try {
		auto root=name.native_value();
		while (root.size()>1 && root.back()=='/') root.pop_back();
		output_detail::root_name(root);
		output_detail::directories(root,false,true);
		if (!std::filesystem::exists(root)) {
			std::filesystem::create_directory(root);
			std::filesystem::permissions(root,std::filesystem::perms::owner_all);
		}
		return string_t(std::filesystem::canonical(root).string());
	} catch (const std::exception &error) { return output_detail::failure(error); }
}
inline result<nullable<string_t>> output_read(const string_t &root,const string_t &relative) {
	try { return output_detail::read(output_detail::target(root.native_value(),relative.native_value(),false)); }
	catch (const std::exception &error) { return output_detail::failure(error); }
}
inline result<bool_t> output_write(const string_t &root,const string_t &relative,const string_t &content) {
	try {
		const auto path=output_detail::target(root.native_value(),relative.native_value(),true);
		const auto bytes=content.native_value();
		const auto previous=output_detail::read(path);
		if (previous.has_value().native_value() && previous.value().native_value()==bytes) return bool_t(false);
#ifdef __linux__
		std::string temporary=(path.parent_path()/".scpp-output-XXXXXX").string();
		int descriptor=::mkstemp(temporary.data());
		if (descriptor<0) throw std::runtime_error("Cannot create output temporary");
		struct cleanup {
			int descriptor; std::string path;
			~cleanup() { if (descriptor>=0) ::close(descriptor); ::unlink(path.c_str()); }
		} guard{descriptor,temporary};
		if (::fcntl(descriptor,F_SETFD,FD_CLOEXEC)<0) throw std::runtime_error("Cannot protect output temporary");
		std::size_t offset=0;
		while (offset<bytes.size()) {
			const auto written=::write(descriptor,bytes.data()+offset,bytes.size()-offset);
			if (written<0 && errno==EINTR) continue;
			if (written<=0) throw std::runtime_error("Cannot write output temporary");
			offset+=static_cast<std::size_t>(written);
		}
		guard.descriptor=-1;
		if (::close(descriptor)!=0) throw std::runtime_error("Cannot close output temporary");
		output_detail::target(root.native_value(),relative.native_value(),false);
		if (::rename(temporary.c_str(),path.c_str())!=0) throw std::runtime_error("Cannot replace output file");
#endif
		return bool_t(true);
	} catch (const std::exception &error) { return output_detail::failure(error); }
}
inline result<bool_t> output_remove(const string_t &root,const string_t &relative,const string_t &expected) {
	try {
		const auto path=output_detail::target(root.native_value(),relative.native_value(),false);
		const auto previous=output_detail::read(path);
		if (!previous.has_value().native_value()) return bool_t(false);
		if (previous.value().native_value()!=expected.native_value()) throw std::runtime_error("Refusing to remove edited output");
		if (!std::filesystem::remove(path)) throw std::runtime_error("Cannot remove output file");
		return bool_t(true);
	} catch (const std::exception &error) { return output_detail::failure(error); }
}
inline string_t output_sha256(const string_t &bytes) { return string_t(output_detail::sha256(bytes.native_value())); }
} // namespace scpp::fs
