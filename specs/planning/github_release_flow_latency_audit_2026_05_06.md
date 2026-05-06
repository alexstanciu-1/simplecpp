Doc Status: planning

# GitHub Release Flow Latency Audit (2026-05-06)

Purpose: capture a focused audit of the reported "GitHub deploy/release/git flow actions can sometimes feel very slow (5-10 min class delays)" observation after the `0.1.13` hotfix release cycle.

This is a planning/audit note only. It does not change the authoritative git workflow in `specs/git_workflow_release_procedure.md`.

## Scope

The goal of this audit was to answer:

- whether repository-local tooling is inherently slow
- whether the GitHub remote path is inherently slow for this repo
- whether recent release cycles actually showed 5-10 minute blocking delays
- what the most likely remaining sources of perceived slowness are

## Repository State Used

- repo: `alexstanciu-1/simplecpp`
- local worktree: `/home/alexv/__AI/simple_cpp/simple_cpp_01`
- current date: `2026-05-06`
- local branch during audit: `develop`
- local HEAD during audit: `3f73545`
- `main` release head reported by user: `bc95443`
- latest released version reported by user: `v0.1.13`

## Measured Command Timings

Measured against the current repository and authenticated GitHub session:

- `git ls-remote origin refs/heads/main`
  - `0.539s`
- `git fetch --dry-run origin main --tags`
  - `0.497s`
- `git describe --tags --match 'v[0-9]*' --abbrev=0`
  - `0.008s`
- `gh repo view alexstanciu-1/simplecpp --json name,defaultBranchRef`
  - `0.605s`
- `gh release view v0.1.13 --repo alexstanciu-1/simplecpp`
  - `0.435s`
- `gh pr list --repo alexstanciu-1/simplecpp --state open --limit 5`
  - `0.442s`

Result:

- basic `git` remote calls are fast
- basic `gh` API calls are also fast
- the repo is small enough that local repo size is not a plausible root cause

## Recent Release Timeline

Recent release timestamps from GitHub:

| Version | Release created | Release published |
| --- | --- | --- |
| `v0.1.13` | `2026-05-06T04:25:47Z` | `2026-05-06T04:26:37Z` |
| `v0.1.12` | `2026-05-05T17:01:19Z` | `2026-05-05T17:03:51Z` |
| `v0.1.11` | `2026-05-05T15:25:49Z` | `2026-05-05T15:31:07Z` |
| `v0.1.10` | `2026-05-05T14:34:29Z` | `2026-05-05T14:37:10Z` |
| `v0.1.9` | `2026-05-05T11:11:47Z` | `2026-05-05T11:15:12Z` |
| `v0.1.8` | `2026-05-04T10:39:06Z` | `2026-05-04T10:41:39Z` |

Observed publish gaps:

- `v0.1.13`: about `50s`
- `v0.1.12`: about `2m 32s`
- `v0.1.11`: about `5m 18s`
- `v0.1.10`: about `2m 41s`
- `v0.1.9`: about `3m 25s`
- `v0.1.8`: about `2m 33s`

Conclusion from the recent data:

- there is visible variability
- the slowest observed recent publish gap in sampled releases was a little over `5m`
- the sampled releases do not show a consistent `10m` repository-local bottleneck

## PR / Merge Timing Around 0.1.13

Recent PR merge timestamps:

- PR `#38` `hotfix/0.1.13 -> main`
  - merged: `2026-05-06T04:24:06Z`
- PR `#39` `hotfix/0.1.13 -> develop`
  - merged: `2026-05-06T04:25:21Z`

Release timing relative to the main merge:

- merge to release create for `v0.1.13`: about `1m 41s`
- merge to release publish for `v0.1.13`: about `2m 31s`

This specific hotfix flow was not unusually slow.

## What Was Ruled Out

The audit did not find evidence that the following are the main cause of the reported delay:

- local repository size
- local `git describe` / tag lookup cost
- basic `git fetch` / `ls-remote` latency to GitHub
- basic `gh` API latency
- a repo-local scripted release pipeline, because there is no substantial in-repo GitHub release automation beyond normal `git` diagnostics and `scpp update`

## Likely Remaining Causes

The remaining likely causes are higher-level workflow effects rather than a single slow command:

1. GitHub-side propagation and UI/API eventual consistency

- release publication timestamps already show multi-minute variability
- merge, tag visibility, release visibility, and branch sync visibility do not always become observable at the same moment

2. Multi-step manual release flow overhead

- the required procedure is intentionally sequential:
  - merge hotfix/release PR to `main`
  - fast-forward local `main`
  - create and push tag
  - publish GitHub Release
  - merge/sync back into `develop`
  - delete temporary branch
- each step is small, but the whole chain can feel slow when performed interactively

3. Human verification pauses between state transitions

- checking that `main` moved
- confirming release notes
- verifying tag placement
- checking release visibility
- syncing `develop`

4. Agent/tool orchestration overhead outside the repository itself

- if an AI agent performs these tasks sequentially with explicit checks after each step, the total wall-clock time can drift upward even when the underlying `git`/`gh` commands are fast

## Incidental Finding

While auditing, the local `develop` branch reported:

- `git describe --tags --match 'v[0-9]*' --abbrev=0` -> `v0.1.11`

At the same time:

- `main` points to `bc95443`
- `bc95443` is tagged `v0.1.13`

Interpretation:

- `git describe` returns the nearest reachable release tag from the current branch history, not the highest tag in the repository
- on `develop`, that can legitimately lag the latest `main` release tag until release history is merged back in a shape that makes the newest tag reachable

This is not a latency cause by itself, but it can make release state feel confusing during a live workflow.

## Recommended Follow-Ups

1. If the goal is better diagnosis, add lightweight timestamped command logging around any future manual release session.

Capture at least:

- start/end of `gh pr merge`
- start/end of tag creation and tag push
- start/end of `gh release create` or release publish action
- start/end of `develop` sync merge or PR merge

2. If the goal is better operator experience, write a small release helper checklist or wrapper that prints elapsed time per step.

That would help distinguish:

- actual slow network/API calls
- waiting for GitHub state propagation
- human confirmation time

3. If the goal is fewer perceived pauses, batch verification where safe.

For example:

- merge main PR
- fetch once
- verify commit/tag/release state together

instead of re-checking GitHub state after every single action.

4. Re-audit after 3-5 more releases if another 5m+ delay is observed.

The current sample suggests variability, but not a strong persistent repo-local bottleneck.

## Current Best Answer

Based on the measured commands and recent GitHub timestamps, the repository does not currently show a slow local `git` or `gh` path. The more plausible explanation is a combination of GitHub-side propagation variability plus the inherently sequential hotfix/release workflow and any manual or agent-side verification pauses layered on top.
