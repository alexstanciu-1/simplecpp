cmake_minimum_required(VERSION 3.20)

project(scpp_runtime LANGUAGES CXX)

option(SCPP_LANGUAGE_TARGET_PHP "Build runtime with PHP-target array semantics" ON)
option(SCPP_WITH_MYSQLI "Build runtime with the optional mysqli module enabled" ON)
option(SCPP_WITH_REGEX "Build runtime with the optional PCRE2 regex module enabled" OFF)

include(CheckIncludeFileCXX)
include(FindPkgConfig)

enable_testing()

add_library(scpp_json SHARED
	include/modules/json/json.cpp
)

target_include_directories(scpp_json
PUBLIC
	${CMAKE_CURRENT_SOURCE_DIR}/include
)

target_compile_features(scpp_json PUBLIC cxx_std_23)

add_library(scpp_filesystem SHARED
	include/modules/filesystem/filesystem.cpp
)

target_include_directories(scpp_filesystem
PUBLIC
	${CMAKE_CURRENT_SOURCE_DIR}/include
)

target_compile_features(scpp_filesystem PUBLIC cxx_std_23)

if(SCPP_WITH_REGEX)
	add_library(scpp_regex SHARED
		include/modules/regex/regex.cpp
	)

	target_include_directories(scpp_regex
	PUBLIC
		${CMAKE_CURRENT_SOURCE_DIR}/include
	)

	target_compile_features(scpp_regex PUBLIC cxx_std_23)
endif()

add_library(scpp_runtime
	include/core/runtime.cpp
)

target_include_directories(scpp_runtime
PUBLIC
	${CMAKE_CURRENT_SOURCE_DIR}/include
)

target_compile_features(scpp_runtime PUBLIC cxx_std_23)

target_link_libraries(scpp_runtime PUBLIC scpp_json scpp_filesystem)
if(SCPP_WITH_REGEX)
	target_link_libraries(scpp_runtime PUBLIC scpp_regex)
endif()

add_library(scpp_php_legacy
	include/lang/php/php_filesystem.cpp
	include/lang/php/php_json.cpp
	include/lang/php/php_mysqli.cpp
	include/lang/php/php_regex.cpp
)

target_include_directories(scpp_php_legacy
PUBLIC
	${CMAKE_CURRENT_SOURCE_DIR}/include
)

target_compile_features(scpp_php_legacy PUBLIC cxx_std_23)
target_link_libraries(scpp_php_legacy PUBLIC scpp_runtime scpp_json scpp_filesystem)
if(SCPP_WITH_REGEX)
	target_link_libraries(scpp_php_legacy PUBLIC scpp_regex)
endif()

add_library(scpp_lang_php INTERFACE)

target_include_directories(scpp_lang_php
INTERFACE
	${CMAKE_CURRENT_SOURCE_DIR}/include
)

target_link_libraries(scpp_lang_php INTERFACE scpp_runtime)

if(SCPP_LANGUAGE_TARGET_PHP)
	target_compile_definitions(scpp_runtime PUBLIC SCPP_LANGUAGE_TARGET_PHP=1)
	target_compile_definitions(scpp_lang_php INTERFACE SCPP_LANGUAGE_TARGET_PHP=1)
	target_compile_definitions(scpp_php_legacy PUBLIC SCPP_LANGUAGE_TARGET_PHP=1)
endif()

if(SCPP_WITH_REGEX)
	set(SCPP_REGEX_PKG_NAME "")
	if(PKG_CONFIG_FOUND)
		pkg_check_modules(PCRE2_8 QUIET IMPORTED_TARGET libpcre2-8)
		if(TARGET PkgConfig::PCRE2_8)
			set(SCPP_REGEX_PKG_NAME "libpcre2-8")
		endif()
	endif()

	if(SCPP_REGEX_PKG_NAME STREQUAL "libpcre2-8")
		target_link_libraries(scpp_regex PUBLIC PkgConfig::PCRE2_8)
		target_compile_definitions(scpp_runtime PUBLIC SCPP_HAS_REGEX=1)
		target_compile_definitions(scpp_lang_php INTERFACE SCPP_HAS_REGEX=1)
		target_compile_definitions(scpp_php_legacy PUBLIC SCPP_HAS_REGEX=1)
		message(STATUS "SCPP regex module: using pkg-config package libpcre2-8")
	else()
		message(FATAL_ERROR "SCPP_WITH_REGEX=ON but no supported PCRE2 pkg-config package was found (tried: libpcre2-8).")
	endif()
else()
	target_compile_definitions(scpp_runtime PUBLIC SCPP_HAS_REGEX=0)
	target_compile_definitions(scpp_lang_php INTERFACE SCPP_HAS_REGEX=0)
	target_compile_definitions(scpp_php_legacy PUBLIC SCPP_HAS_REGEX=0)
endif()

if(SCPP_WITH_MYSQLI)
	target_sources(scpp_runtime PRIVATE include/modules/mysql/mysql_module.cpp)
	set(SCPP_MYSQLI_PKG_NAME "")

	if(PKG_CONFIG_FOUND)
		# Prefer the pkg-config name used by Ubuntu 24.04 / Debian dev packages.
		pkg_check_modules(MARIADB_LIB QUIET IMPORTED_TARGET libmariadb)
		if(TARGET PkgConfig::MARIADB_LIB)
			set(SCPP_MYSQLI_PKG_NAME "libmariadb")
		endif()

		# Fall back to alternative package names used by some distributions.
		if(SCPP_MYSQLI_PKG_NAME STREQUAL "")
			pkg_check_modules(MARIADB QUIET IMPORTED_TARGET mariadb)
			if(TARGET PkgConfig::MARIADB)
				set(SCPP_MYSQLI_PKG_NAME "mariadb")
			endif()
		endif()

		if(SCPP_MYSQLI_PKG_NAME STREQUAL "")
			pkg_check_modules(MYSQLCLIENT QUIET IMPORTED_TARGET mysqlclient)
			if(TARGET PkgConfig::MYSQLCLIENT)
				set(SCPP_MYSQLI_PKG_NAME "mysqlclient")
			endif()
		endif()
	endif()

	if(SCPP_MYSQLI_PKG_NAME STREQUAL "libmariadb")
		target_link_libraries(scpp_runtime PUBLIC PkgConfig::MARIADB_LIB)
		target_link_libraries(scpp_php_legacy PUBLIC PkgConfig::MARIADB_LIB)
		target_compile_definitions(scpp_runtime PUBLIC SCPP_HAS_MYSQLI=1)
		target_compile_definitions(scpp_lang_php INTERFACE SCPP_HAS_MYSQLI=1)
		target_compile_definitions(scpp_php_legacy PUBLIC SCPP_HAS_MYSQLI=1)
		message(STATUS "SCPP mysqli module: using pkg-config package libmariadb")
	elseif(SCPP_MYSQLI_PKG_NAME STREQUAL "mariadb")
		target_link_libraries(scpp_runtime PUBLIC PkgConfig::MARIADB)
		target_link_libraries(scpp_php_legacy PUBLIC PkgConfig::MARIADB)
		target_compile_definitions(scpp_runtime PUBLIC SCPP_HAS_MYSQLI=1)
		target_compile_definitions(scpp_lang_php INTERFACE SCPP_HAS_MYSQLI=1)
		target_compile_definitions(scpp_php_legacy PUBLIC SCPP_HAS_MYSQLI=1)
		message(STATUS "SCPP mysqli module: using pkg-config package mariadb")
	elseif(SCPP_MYSQLI_PKG_NAME STREQUAL "mysqlclient")
		target_link_libraries(scpp_runtime PUBLIC PkgConfig::MYSQLCLIENT)
		target_link_libraries(scpp_php_legacy PUBLIC PkgConfig::MYSQLCLIENT)
		target_compile_definitions(scpp_runtime PUBLIC SCPP_HAS_MYSQLI=1)
		target_compile_definitions(scpp_lang_php INTERFACE SCPP_HAS_MYSQLI=1)
		target_compile_definitions(scpp_php_legacy PUBLIC SCPP_HAS_MYSQLI=1)
		message(STATUS "SCPP mysqli module: using pkg-config package mysqlclient")
	else()
		message(WARNING "SCPP_WITH_MYSQLI=ON but no supported MariaDB/MySQL Connector/C pkg-config package was found (tried: libmariadb, mariadb, mysqlclient). Building mysqli wrappers in disabled-stub mode.")
		target_compile_definitions(scpp_runtime PUBLIC SCPP_HAS_MYSQLI=0)
		target_compile_definitions(scpp_lang_php INTERFACE SCPP_HAS_MYSQLI=0)
		target_compile_definitions(scpp_php_legacy PUBLIC SCPP_HAS_MYSQLI=0)
	endif()
else()
	target_compile_definitions(scpp_runtime PUBLIC SCPP_HAS_MYSQLI=0)
	target_compile_definitions(scpp_lang_php INTERFACE SCPP_HAS_MYSQLI=0)
	target_compile_definitions(scpp_php_legacy PUBLIC SCPP_HAS_MYSQLI=0)
endif()

add_subdirectory(${CMAKE_CURRENT_SOURCE_DIR}/../tests/runtime/native ${CMAKE_CURRENT_BINARY_DIR}/tests_runtime_native)
