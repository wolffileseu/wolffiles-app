// Stub of the build-time-generated ETLegacy version header.
// The real one (produced by the cmake build) defines a few macros that the
// menu sources reference for the in-game About / debug overlays. We don't
// care about specific values here — only that the file exists, so #include
// resolves and the macro names get an empty-string expansion that survives
// substitution into property values.

#define GIT_DESCRIBE      ""
#define GIT_COMMIT_SHORT  ""
#define GIT_BRANCH        ""
