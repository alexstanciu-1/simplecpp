# AI_WORKFLOW.md
Doc Status: planning
Version: 1.0  
Purpose: Standardized workflow for interacting with the AI on the Simple C++ / Prism++ project.

---

## 1. Core Workflow (Mandatory)

1. The user provides a full project archive (`.zip` or `.7z`).
2. The assistant extracts the archive to a fixed path:
   `/mnt/data/simplecpp_work`
3. If a new archive is provided:
   - the previous folder is deleted entirely
   - the new archive replaces it
4. The assistant works **only inside this folder**.
5. The assistant returns a **full updated project archive**, not partial files.
6. The user reviews changes using Beyond Compare.
7. Git operations, pull requests, and releases must follow:
   - `specs/git_workflow_release_procedure.md`
8. If no new archive is provided:
   - the assistant continues working on the existing folder (same chat only)
9. In a new chat:
   - a fresh archive must be provided because sandbox state is not preserved across chats

---

## 2. Archive Rules

- Default format: `git archive` ZIP (no `.git`)
- Optional format: `.7z` including `.git` when Git-aware operations are needed
- Archives must contain a single project root
- Do not mix multiple project roots in one archive

---

## 3. File System Rules

- All work must happen under:
  `/mnt/data/simplecpp_work`
- If the user is on Windows, development work must be performed through WSL rather than native Windows shells/tools
- On Windows + WSL setups, the working copy should live in the WSL filesystem and commands should be run from WSL
- Any git-related command that needs to be run from the Windows side should be run through Git Bash rather than PowerShell
- Git branching, pull requests, release handling, and release notes are governed by:
  - `specs/git_workflow_release_procedure.md`
- Current Windows + WSL project mapping:
  - Windows mirror: `D:\Work_2026\__AI\simple_cpp`
  - Active WSL working copy: `/home/alexv/__AI/simple-cpp`
- The assistant works in the WSL working copy
- The user reviews changes and syncs code back to `D:\Work_2026\__AI\simple_cpp` when needed
- Do not read from or write to unrelated project folders
- Preserve:
  - file encoding
  - line endings
  - tab-based indentation (1 tab = 4 spaces)

---

## 4. Output Requirements

- Always return a **full project archive**
- Do not return partial edits unless explicitly requested
- Ensure consistency and completeness across all modified files

---

## 5. Prohibited Actions

- Returning only partial file edits without a full project archive when a full return is expected
- Working outside the fixed project folder
- Assuming file state persists across chats
- Introducing unrelated formatting changes

---

## 6. Goal

Ensure:
- deterministic changes
- reproducible builds
- clean diffs compatible with Beyond Compare
- full user control over Git operations

---

## 7. Communication Rules (Project Instructions Alignment)

- Use English only for this project
- Keep responses short and to the point, unless more detail is explicitly requested
- Ask clarifying questions when requirements are unclear or incomplete
