# Agent Setup — Uninterrupted AI Work, Safely

> You were torn between **allowlist** and **lightweight container**. Answer: **do both, layered** — they're not rivals. Allowlist is your baseline (zero setup, everyone uses it today); the container is an optional wrapper for the one person running heavy autonomous agents.
>
> The point of all this: stop the AI pausing to ask permission for safe commands (`pnpm`, `git`, `python`), **without** giving it free rein to do something destructive. Never run full "skip all permissions" on your real machine with no guardrail — a hallucinated `rm`, a bad `pip install`, or a leaked token is one auto-approve away.

---

## Layer 1 — Permission allowlist (everyone, do this first — 5 min)
Pre-approve the safe commands; keep the dangerous ones gated. ~90% fewer prompts, ~0 risk.

**Claude Code** — `.claude/settings.json` in the repo:
```json
{
  "permissions": {
    "allow": [
      "Bash(pnpm:*)", "Bash(npm:*)", "Bash(npx:*)", "Bash(uv:*)",
      "Bash(python:*)", "Bash(python3:*)", "Bash(git add:*)",
      "Bash(git commit:*)", "Bash(git status:*)", "Bash(git diff:*)",
      "Edit", "Write", "Read"
    ],
    "deny": [
      "Bash(rm:*)", "Bash(git push:*)", "Bash(curl:*)", "Bash(sudo:*)",
      "Bash(dd:*)", "Read(./.env)", "Read(**/secrets/**)"
    ]
  }
}
```
*(Commit this so all 3 share it. Keep `git push`, `rm`, `curl`, and secrets **manual** — those are the ones worth a 1-second glance.)*

**Cursor:** Settings → enable agent **auto-run** but set the **allowlist/denylist** (Cursor calls it "auto-run allowlist") — allow build/test commands, deny `rm`/`push`/network.
**Codex CLI:** run with its `--ask-for-approval` set to an *on-failure*/auto profile (auto-approve safe, prompt on risky), not full bypass.
**Copilot:** completions need no permission; agent mode — keep file-write confirmations on for shared files.

## Layer 2 — Git as the undo button (everyone)
- Commit every few minutes (`git commit -m "wip"`). An agent breaks something → `git reset --hard HEAD` or `git revert`. **This is your real safety net**, allowlist or not.
- Use **worktrees** if one person runs >1 agent, so they can't corrupt each other's files.

## Layer 3 — Lightweight container (optional — for the heavy-autonomous-runner only)
If one teammate wants to let an agent run *truly* unattended (full auto), wrap it in a container so even a bad command can't touch the host. Cheaper + faster than a full VM.

**`.devcontainer/devcontainer.json`:**
```json
{
  "name": "liberhack",
  "image": "mcr.microsoft.com/devcontainers/universal:2",
  "features": {
    "ghcr.io/devcontainers/features/node:1": {},
    "ghcr.io/devcontainers/features/python:1": {}
  },
  "forwardPorts": [5173, 8000],
  "postCreateCommand": "pnpm -C web install && uv sync --project api && uv sync --project data"
}
```
- Open in VS Code → "Reopen in Container." Ports 5173 (Vite) + 8000 (FastAPI) forward automatically.
- Inside the container you can run agents more aggressively — the blast radius is the container, not your laptop.
- ⚠️ Costs some RAM (you have mid laptops) — only the **one** person doing autonomous runs needs it; everyone else stays on Layer 1.

---

## Decision guide (since you couldn't pick)
| Your situation | Use |
|---|---|
| Default for all 3 of you, today | **Layer 1 allowlist + Layer 2 git** ✅ start here |
| One person wants hands-off autonomous agents | add **Layer 3 container** for them only |
| Someone has a beefy spare machine + wants max isolation | full VM (see `09_ai_usage_guide.md` note) — overkill otherwise |

**Don't** spend Friday's first hours building container infra for everyone. Allowlist + git gets you 90% of "uninterrupted" with zero RAM cost and zero novel-infra risk (your weak spot is time management — respect it).

---

## ⚠️ Never auto-approve
`rm -rf`, `git push --force`, `curl | sh`, reading `.env`/secrets, `sudo`, package installs of libs you didn't verify exist. These deserve the one-second look — every time.
