# A shared inspection directory contains the most recently built profile.
execute_process(
  COMMAND "${GENERATOR}" "${METADATA}" "${PUBLICATION}" "${OUTPUT}" --check
  RESULT_VARIABLE freshness OUTPUT_QUIET ERROR_QUIET)
if(NOT freshness STREQUAL "0")
  execute_process(
    COMMAND "${GENERATOR}" "${METADATA}" "${PUBLICATION}" "${OUTPUT}"
    COMMAND_ERROR_IS_FATAL ANY)
endif()
