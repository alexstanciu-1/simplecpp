# Keep generated PHP stable on unchanged builds, and repair stale/obsolete output.
set(arguments "${PHP}" "${GENERATOR}" emit-compiler "${METADATA}" "${PUBLICATION}" "${FACTS}" "${CONTEXT}" "${OUTPUT}")
execute_process(COMMAND ${arguments} --check
  RESULT_VARIABLE freshness OUTPUT_QUIET ERROR_QUIET)
if(NOT freshness STREQUAL "0")
  execute_process(COMMAND ${arguments} COMMAND_ERROR_IS_FATAL ANY)
endif()
