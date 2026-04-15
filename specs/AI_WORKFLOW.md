# AI_WORKFLOW.md

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
7. The user handles all Git operations, including commit, push, and pull request management.
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
