cmake_minimum_required(VERSION 3.20)

project(scpp_runtime LANGUAGES CXX)

option(BUILD_SHARED_LIBS "Build core, providers and language adapters as shared libraries" OFF)
option(SCPP_LANGUAGE_TARGET_PHP "Build runtime with PHP-target array semantics" ON)
option(SCPP_WITH_MYSQLI "Build runtime with the optional mysqli module enabled" ON)
option(SCPP_WITH_REGEX "Build runtime with the optional PCRE2 regex module enabled" OFF)
option(SCPP_WITH_PROCESS "Build managed child processes" OFF)
option(SCPP_WITH_CURL "Build runtime with the optional libcurl module enabled" OFF)
option(SCPP_WITH_UI "Build runtime with the optional native ui module enabled" OFF)

include(CheckIncludeFileCXX)
include(FindPkgConfig)

enable_testing()

# Configuration is shared without making core depend on an aggregate/module.
add_library(scpp_runtime_configuration INTERFACE)
target_include_directories(scpp_runtime_configuration INTERFACE ${CMAKE_CURRENT_SOURCE_DIR}/include)
target_compile_features(scpp_runtime_configuration INTERFACE cxx_std_23)
add_library(scpp_runtime_core include/core/runtime_core.cpp)
target_link_libraries(scpp_runtime_core PUBLIC scpp_runtime_configuration)
set_target_properties(scpp_runtime_core PROPERTIES POSITION_INDEPENDENT_CODE ON)
# Consumers retain the aggregate target name; implementations have truthful owners.
add_library(scpp_runtime INTERFACE)

# New public consumers see only this root. Existing targets remain compatibility APIs.
add_library(scpp_public_configuration INTERFACE)
target_include_directories(scpp_public_configuration INTERFACE ${CMAKE_CURRENT_SOURCE_DIR}/public)
target_compile_features(scpp_public_configuration INTERFACE cxx_std_23)
add_library(scpp_core_api INTERFACE)
target_link_libraries(scpp_core_api INTERFACE scpp_public_configuration "$<LINK_ONLY:scpp_runtime_core>")


# Optional consumers link the tasks owner explicitly.
add_library(scpp_tasks EXCLUDE_FROM_ALL include/modules/tasks/tasks.cpp)
target_link_libraries(scpp_tasks PUBLIC scpp_runtime_configuration scpp_runtime_core)
target_compile_definitions(scpp_tasks PUBLIC SCPP_HAS_TASKS=1)
find_package(Threads REQUIRED)
target_link_libraries(scpp_tasks PUBLIC Threads::Threads)
add_library(scpp_tasks_api INTERFACE)
target_link_libraries(scpp_tasks_api INTERFACE scpp_core_api "$<LINK_ONLY:scpp_tasks>")
target_compile_definitions(scpp_tasks_api INTERFACE SCPP_HAS_TASKS=1)


add_library(scpp_json
	include/modules/json/json.cpp
	include/modules/json/document.cpp
)

target_include_directories(scpp_json
PUBLIC
	${CMAKE_CURRENT_SOURCE_DIR}/include
)

target_compile_features(scpp_json PUBLIC cxx_std_23)

if(SCPP_WITH_PROCESS)
	add_library(scpp_process include/modules/process/process.cpp)
	target_include_directories(scpp_process PUBLIC ${CMAKE_CURRENT_SOURCE_DIR}/include)
	target_compile_features(scpp_process PUBLIC cxx_std_23)
	if(CMAKE_SYSTEM_NAME STREQUAL "Linux")
		find_package(Threads REQUIRED)
		target_link_libraries(scpp_process PRIVATE Threads::Threads)
	endif()
endif()

add_library(scpp_filesystem
	include/modules/filesystem/public_scan.cpp
	include/modules/filesystem/file_lock.cpp
	include/modules/filesystem/filesystem.cpp
	include/modules/filesystem/snapshot.cpp
)

target_include_directories(scpp_filesystem
PUBLIC
	${CMAKE_CURRENT_SOURCE_DIR}/include
)

target_compile_features(scpp_filesystem PUBLIC cxx_std_23)

add_library(scpp_datetime
	include/modules/datetime/datetime.cpp
)

target_include_directories(scpp_datetime
PUBLIC
	${CMAKE_CURRENT_SOURCE_DIR}/include
)

target_compile_features(scpp_datetime PUBLIC cxx_std_23)

if(SCPP_WITH_REGEX)
	add_library(scpp_regex
		include/modules/regex/regex.cpp
	)

	target_include_directories(scpp_regex
	PUBLIC
		${CMAKE_CURRENT_SOURCE_DIR}/include
	)

	target_compile_features(scpp_regex PUBLIC cxx_std_23)
endif()

if(SCPP_WITH_CURL)
	add_library(scpp_curl
		include/modules/curl/curl.cpp
	)

	target_include_directories(scpp_curl
	PUBLIC
		${CMAKE_CURRENT_SOURCE_DIR}/include
	)

	target_compile_features(scpp_curl PUBLIC cxx_std_23)
	target_compile_definitions(scpp_curl PUBLIC SCPP_HAS_CURL=1)
endif()

if(SCPP_WITH_UI)
	add_library(scpp_ui
		include/modules/ui/ui.cpp
	)
	if(APPLE)
		enable_language(OBJCXX)
		set_source_files_properties(include/modules/ui/ui.cpp PROPERTIES LANGUAGE OBJCXX)
	endif()

	target_include_directories(scpp_ui
	PUBLIC
		${CMAKE_CURRENT_SOURCE_DIR}/include
	)

	target_compile_features(scpp_ui PUBLIC cxx_std_23)
	target_compile_definitions(scpp_ui PUBLIC SCPP_HAS_UI=1)
endif()

target_link_libraries(scpp_runtime INTERFACE scpp_json scpp_filesystem scpp_datetime)
if(SCPP_WITH_PROCESS)
	target_link_libraries(scpp_runtime INTERFACE scpp_process)
endif()
if(SCPP_WITH_REGEX)
	target_link_libraries(scpp_runtime INTERFACE scpp_regex)
endif()
if(SCPP_WITH_CURL)
	target_link_libraries(scpp_runtime INTERFACE scpp_curl)
endif()
if(SCPP_WITH_UI)
	target_link_libraries(scpp_runtime INTERFACE scpp_ui)
endif()

# Every enabled provider uses the same core target. BUILD_SHARED_LIBS chooses
# one coherent built-in composition, avoiding core archive copies in module DSOs.
foreach(provider scpp_json scpp_filesystem scpp_datetime scpp_process scpp_regex scpp_curl scpp_ui)
	if(TARGET ${provider})
		target_link_libraries(${provider} PUBLIC scpp_runtime_core)
	endif()
endforeach()
target_link_libraries(scpp_runtime INTERFACE scpp_runtime_core)

add_library(scpp_php_legacy
	include/lang/php/php_abi_bridge.cpp
	include/lang/php/php_filesystem.cpp
	include/lang/php/php_json.cpp
	include/lang/php/php_datetime.cpp
	include/lang/php/php_mysqli.cpp
	include/lang/php/php_regex.cpp
)

target_include_directories(scpp_php_legacy
PUBLIC
	${CMAKE_CURRENT_SOURCE_DIR}/include
)

target_compile_features(scpp_php_legacy PUBLIC cxx_std_23)
target_link_libraries(scpp_php_legacy PUBLIC scpp_runtime scpp_json scpp_filesystem scpp_datetime)
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
	target_compile_definitions(scpp_runtime_configuration INTERFACE SCPP_LANGUAGE_TARGET_PHP=1)
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
		target_compile_definitions(scpp_runtime_configuration INTERFACE SCPP_HAS_REGEX=1)
		target_compile_definitions(scpp_lang_php INTERFACE SCPP_HAS_REGEX=1)
		target_compile_definitions(scpp_php_legacy PUBLIC SCPP_HAS_REGEX=1)
		message(STATUS "SCPP regex module: using pkg-config package libpcre2-8")
	else()
		message(FATAL_ERROR "SCPP_WITH_REGEX=ON but no supported PCRE2 pkg-config package was found (tried: libpcre2-8).")
	endif()
else()
	target_compile_definitions(scpp_runtime_configuration INTERFACE SCPP_HAS_REGEX=0)
	target_compile_definitions(scpp_lang_php INTERFACE SCPP_HAS_REGEX=0)
	target_compile_definitions(scpp_php_legacy PUBLIC SCPP_HAS_REGEX=0)
endif()

if(SCPP_WITH_CURL)
	set(SCPP_CURL_LINKED FALSE)

	if(PKG_CONFIG_FOUND)
		pkg_check_modules(LIBCURL QUIET IMPORTED_TARGET libcurl)
		if(TARGET PkgConfig::LIBCURL)
			target_link_libraries(scpp_curl PUBLIC PkgConfig::LIBCURL)
			set(SCPP_CURL_LINKED TRUE)
			message(STATUS "SCPP curl module: using pkg-config package libcurl")
		endif()
	endif()

	if(NOT SCPP_CURL_LINKED)
		find_package(CURL QUIET)
		if(TARGET CURL::libcurl)
			target_link_libraries(scpp_curl PUBLIC CURL::libcurl)
			set(SCPP_CURL_LINKED TRUE)
			message(STATUS "SCPP curl module: using CMake CURL::libcurl target")
		elseif(CURL_FOUND)
			target_include_directories(scpp_curl PUBLIC ${CURL_INCLUDE_DIRS})
			target_link_libraries(scpp_curl PUBLIC ${CURL_LIBRARIES})
			set(SCPP_CURL_LINKED TRUE)
			message(STATUS "SCPP curl module: using CMake CURL include/library variables")
		endif()
	endif()

	if(SCPP_CURL_LINKED)
		target_compile_definitions(scpp_runtime_configuration INTERFACE SCPP_HAS_CURL=1)
		target_compile_definitions(scpp_lang_php INTERFACE SCPP_HAS_CURL=1)
		target_compile_definitions(scpp_php_legacy PUBLIC SCPP_HAS_CURL=1)
	else()
		message(FATAL_ERROR "SCPP_WITH_CURL=ON but no supported libcurl development package was found. Install libcurl dev files or disable the curl module.")
	endif()
else()
	target_compile_definitions(scpp_runtime_configuration INTERFACE SCPP_HAS_CURL=0)
	target_compile_definitions(scpp_lang_php INTERFACE SCPP_HAS_CURL=0)
	target_compile_definitions(scpp_php_legacy PUBLIC SCPP_HAS_CURL=0)
endif()

if(SCPP_WITH_UI)
	if(CMAKE_SYSTEM_NAME STREQUAL "Linux")
		if(PKG_CONFIG_FOUND)
			pkg_check_modules(GTK3 QUIET IMPORTED_TARGET gtk+-3.0)
		endif()
		if(TARGET PkgConfig::GTK3)
			target_link_libraries(scpp_ui PUBLIC PkgConfig::GTK3)
			target_compile_definitions(scpp_ui PUBLIC SCPP_UI_BACKEND_GTK=1)
			message(STATUS "SCPP ui module: using pkg-config package gtk+-3.0")
		else()
			message(FATAL_ERROR "SCPP_WITH_UI=ON but GTK 3 development files were not found. Install a package that provides pkg-config package gtk+-3.0 or disable the ui module.")
		endif()
	endif()
	if(APPLE)
		target_link_libraries(scpp_ui PUBLIC "-framework Cocoa")
		target_compile_definitions(scpp_ui PUBLIC SCPP_UI_BACKEND_APPKIT=1)
		message(STATUS "SCPP ui module: using AppKit/Cocoa")
	endif()
	target_compile_definitions(scpp_runtime_configuration INTERFACE SCPP_HAS_UI=1)
	target_compile_definitions(scpp_lang_php INTERFACE SCPP_HAS_UI=1)
	target_compile_definitions(scpp_php_legacy PUBLIC SCPP_HAS_UI=1)
else()
	target_compile_definitions(scpp_runtime_configuration INTERFACE SCPP_HAS_UI=0)
	target_compile_definitions(scpp_lang_php INTERFACE SCPP_HAS_UI=0)
	target_compile_definitions(scpp_php_legacy PUBLIC SCPP_HAS_UI=0)
endif()

if(SCPP_WITH_MYSQLI)
	add_library(scpp_mysql include/modules/mysql/mysql_module.cpp)
	target_link_libraries(scpp_mysql PUBLIC scpp_runtime_core)
	target_link_libraries(scpp_runtime INTERFACE scpp_mysql)
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
		target_link_libraries(scpp_mysql PUBLIC PkgConfig::MARIADB_LIB)
		target_link_libraries(scpp_php_legacy PUBLIC PkgConfig::MARIADB_LIB)
		target_compile_definitions(scpp_runtime_configuration INTERFACE SCPP_HAS_MYSQLI=1)
		target_compile_definitions(scpp_lang_php INTERFACE SCPP_HAS_MYSQLI=1)
		target_compile_definitions(scpp_php_legacy PUBLIC SCPP_HAS_MYSQLI=1)
		message(STATUS "SCPP mysqli module: using pkg-config package libmariadb")
	elseif(SCPP_MYSQLI_PKG_NAME STREQUAL "mariadb")
		target_link_libraries(scpp_mysql PUBLIC PkgConfig::MARIADB)
		target_link_libraries(scpp_php_legacy PUBLIC PkgConfig::MARIADB)
		target_compile_definitions(scpp_runtime_configuration INTERFACE SCPP_HAS_MYSQLI=1)
		target_compile_definitions(scpp_lang_php INTERFACE SCPP_HAS_MYSQLI=1)
		target_compile_definitions(scpp_php_legacy PUBLIC SCPP_HAS_MYSQLI=1)
		message(STATUS "SCPP mysqli module: using pkg-config package mariadb")
	elseif(SCPP_MYSQLI_PKG_NAME STREQUAL "mysqlclient")
		target_link_libraries(scpp_mysql PUBLIC PkgConfig::MYSQLCLIENT)
		target_link_libraries(scpp_php_legacy PUBLIC PkgConfig::MYSQLCLIENT)
		target_compile_definitions(scpp_runtime_configuration INTERFACE SCPP_HAS_MYSQLI=1)
		target_compile_definitions(scpp_lang_php INTERFACE SCPP_HAS_MYSQLI=1)
		target_compile_definitions(scpp_php_legacy PUBLIC SCPP_HAS_MYSQLI=1)
		message(STATUS "SCPP mysqli module: using pkg-config package mysqlclient")
	else()
		message(WARNING "SCPP_WITH_MYSQLI=ON but no supported MariaDB/MySQL Connector/C pkg-config package was found (tried: libmariadb, mariadb, mysqlclient). Building mysqli wrappers in disabled-stub mode.")
		target_compile_definitions(scpp_runtime_configuration INTERFACE SCPP_HAS_MYSQLI=0)
		target_compile_definitions(scpp_lang_php INTERFACE SCPP_HAS_MYSQLI=0)
		target_compile_definitions(scpp_php_legacy PUBLIC SCPP_HAS_MYSQLI=0)
	endif()
else()
	target_compile_definitions(scpp_runtime_configuration INTERFACE SCPP_HAS_MYSQLI=0)
	target_compile_definitions(scpp_lang_php INTERFACE SCPP_HAS_MYSQLI=0)
	target_compile_definitions(scpp_php_legacy PUBLIC SCPP_HAS_MYSQLI=0)
endif()

add_subdirectory(${CMAKE_CURRENT_SOURCE_DIR}/../tests/runtime/native ${CMAKE_CURRENT_BINARY_DIR}/tests_runtime_native)

# Focused filesystem consumers receive only public headers and the owning libraries.
add_library(scpp_filesystem_api INTERFACE)
target_link_libraries(scpp_filesystem_api INTERFACE scpp_core_api "$<LINK_ONLY:scpp_filesystem>")

# Reusable physical API generation library; no compiler-language semantic admission.
add_library(scpp_api_generation STATIC EXCLUDE_FROM_ALL
  tools/api_generator/value.cpp tools/api_generator/names.cpp tools/api_generator/conversions.cpp tools/api_generator/operators.cpp tools/api_generator/generator.cpp)
target_include_directories(scpp_api_generation PUBLIC ${CMAKE_CURRENT_SOURCE_DIR}/tools/api_generator/public)
target_link_libraries(scpp_api_generation PRIVATE scpp_runtime_core)
target_link_libraries(scpp_api_generation PUBLIC scpp_public_configuration)
add_executable(scpp_api_generator EXCLUDE_FROM_ALL tools/api_generator/main.cpp)
target_link_libraries(scpp_api_generator PRIVATE scpp_api_generation scpp_runtime_core)
