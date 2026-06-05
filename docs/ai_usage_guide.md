# 48-Hour Hackathon AI-Coding Playbook (June 2026)

**Stack:** TypeScript + React (Vite) frontend · Python + FastAPI + DuckDB backend · mostly AI-written code · non-expert team
**Tools in play:** Claude Code (CLI) · Cursor · Gemini CLI / Code Assist · OpenAI Codex / GitHub Copilot — used simultaneously by different people.

> **Read this first (the 3 rules that matter most):**
> 1. **One shared context file** (`AGENTS.md`, symlinked to `CLAUDE.md` / `GEMINI.md`) that every tool and every person reads. Set it up in the first 30 minutes.
> 2. **Lock the API contract early** (an OpenAPI / Pydantic schema + TypeScript types). Frontend and backend agents both code against it, not against each other.
> 3. **Small, frequent commits + one writer per file.** Two agents must never edit the same file at the same time. This is how you avoid the AI-merge-hell that kills hackathons.

---

## 1. Token & Context Optimization (universal — applies to all four tools)

The constraint is the **context window**, not just cost. As the window fills, reasoning quality degrades — this is "context rot." Token management is a *quality* problem, not only a billing one. ([HumanLayer](https://www.humanlayer.dev/blog/writing-a-good-claude-md), [ClaudeLog](https://claudelog.com/faqs/how-to-optimize-claude-code-token-usage/))

### What WASTES tokens vs what SAVES them

| Wastes tokens / context | Saves tokens / context |
|---|---|
| Dumping the whole repo or pasting 500-line files for a 20-line change | Point the tool at **exact files/lines** it needs |
| A bloated 2,000-token context file loaded every turn | Context file **< 500 tokens / ~50 lines**, only universal rules |
| One marathon session doing 6 tasks | One **scoped session per task**; `/clear` between unrelated tasks |
| Letting the agent "explore" the codebase freely | Tell it which files to read and which to **ignore** (`.claudeignore`, `.gitignore` patterns) |
| 10+ MCP servers / web search / extended thinking always on | Disable tools/MCP you aren't using this task |
| Putting code-style rules in the context file | Let the **linter/formatter** enforce style (deterministic, free) |
| Re-pasting docs every message | Store stable docs in the context file once; rely on caching |

### Concrete practices (2025–2026 consensus)

- **Keep the context file tiny.** It loads into *every* message. A 2,000-token file × 30 messages = 60,000 wasted tokens before any work. Target < 500 tokens. Frontier models reliably follow only ~150–200 instructions and the harness already spends ~50 — don't waste the budget. ([HumanLayer](https://www.humanlayer.dev/blog/writing-a-good-claude-md))
- **Keep source files small and single-purpose.** Big files force the agent to read (and pay for) code it doesn't need. ([MindStudio](https://www.mindstudio.ai/blog/how-to-manage-claude-code-token-usage))
- **Scope the prompt.** Short, direct prompts beat long ones on both cost *and* answer quality. Skipping unneeded reasoning cuts 40–60% of output tokens. ([buildtolaunch](https://buildtolaunch.substack.com/p/claude-code-token-optimization))
- **Compact/clear strategically.** `/compact` after finishing a sub-task (checkpoint it), `/clear` when switching topics. Compact *proactively* — don't wait to hit the wall. ([Claude Code docs](https://code.claude.com/docs/en/best-practices))
- **Use subagents / cloud agents for exploration.** They read many files in a *separate* context and return a summary, keeping your main session clean. ([ClaudeLog](https://claudelog.com/faqs/how-to-optimize-claude-code-token-usage/))
- **Git commits = free checkpoints.** Commit often so you can `/clear` fearlessly and start fresh without losing progress.

---

## 2. Avoiding Usage / Rate Limits — per tool, "make it last 48h"

> ⚠️ **Spring 2026 reality:** Cursor, Copilot, and Codex all moved from "X requests/month" to **token-based credit consumption**. Limits now scale with *task size*, so big agent runs drain budgets fast. Plan for it. ([GitHub blog](https://github.blog/news-insights/company-news/github-copilot-is-moving-to-usage-based-billing/), [OpenAI Codex rate card](https://help.openai.com/en/articles/20001106-codex-rate-card), [Cursor docs](https://cursor.com/docs/models-and-pricing))

### Claude Code

- **Two limits stack:** a **5-hour rolling window** (starts on first prompt) *and* a **weekly cap**. The pool is **shared** across Claude Code, Claude.ai chat, and Cowork. ([TrueFoundry](https://www.truefoundry.com/blog/claude-code-limits-explained), [TokenMix](https://tokenmix.ai/blog/complete-claude-limits-guide-2026-tokens-uploads-5-hour))
- **Good news (May 2026):** 5-hour limits permanently **doubled**, peak-hour penalty removed on Pro/Max, and weekly caps temporarily **+50% through July 13, 2026**. A hackathon in this window has more headroom than usual. ([Verdent](https://www.verdent.ai/guides/claude-code-limits-doubled-may-2026), [apidog](https://apidog.com/blog/claude-code-weekly-limits-50-percent-increase-july-2026/))
- **Make it last:**
  - **Plan in Opus, implement in Sonnet.** Reserve Opus for the hard reasoning; run execution/boilerplate on Sonnet. ([allthings.how](https://allthings.how/claude-code-usage-limits-explained-pro-max-and-weekly-caps/))
  - In `~/.claude/settings.json`: default to Sonnet, cap thinking tokens, lower the autocompact threshold, route subagents to Haiku.
  - `/clear` per task; disable unused MCP/web search.
  - Watch `/usage` (or `npx ccusage@latest` for accurate local readings — the in-app meter lags).
  - **Tier guidance for a sprint:** Max 5x ($100) is the sweet spot for one heavy driver; Pro ($20) is fine for a lighter user. Enable usage credits as an emergency overflow.

### Cursor

- Paid plans give a **monthly credit pool = plan price** ($20 Pro). Premium model calls (Claude Sonnet, GPT) draw from it; **Auto mode is unlimited and free** and **Tab completion is unlimited**. ([Vantage](https://www.vantage.sh/blog/cursor-pricing-explained), [Finout](https://www.finout.io/blog/what-happened-to-cursor-pricing-2026-guide-5-cost-cutting-tips))
- **Agent Mode and MAX mode cost much more** — each step/file is a separate billed call. ([NxCode](https://www.nxcode.io/resources/news/cursor-ai-pricing-plans-guide-2026))
- **Make it last:**
  - **Default to Auto mode** for routine edits; only hand-pick a premium model for genuinely hard work.
  - Use unlimited **Tab completion** for the bulk of typing.
  - Reserve **Agent/MAX mode** for multi-file features, not small edits.
  - **Disable overages** in settings to hard-cap spend at the plan price.
  - Optional: bring-your-own API key bypasses credits (but BYO-key disables agent mode).

### Gemini CLI / Code Assist

- **Most generous free tier:** sign in with a **personal Google account** → free Code Assist license, Gemini Pro/3 models, **1M-token context**, **60 requests/min, 1,000 requests/day** free. The daily cap is aggregated across all models. ([Google blog](https://blog.google/innovation-and-ai/technology/developers-tools/introducing-gemini-cli-open-source-ai-agent/), [Gemini Code Assist quotas](https://developers.google.com/gemini-code-assist/resources/quotas))
- In agent mode, **one prompt can = multiple requests**, so the 1,000/day goes faster than it looks.
- ⚠️ **Migration deadline:** Gemini CLI / Code Assist for individuals is being folded into **Antigravity CLI**; the individual/free tiers stop serving on **June 18, 2026**. If your hackathon is after that date, use Antigravity CLI instead. ([Gemini CLI quotas](https://geminicli.com/docs/resources/quota-and-pricing/))
- **Make it last:** Use Gemini as your **free overflow tool** and your **huge-context reader** (drop the whole repo in for "explain/where-is" questions). Use **Plan Mode** (read-only) to burn fewer write-requests.

### Codex / GitHub Copilot

- **Copilot:** as of **June 1, 2026**, all plans are usage-based on **GitHub AI Credits** (token-metered). **Code completions + Next Edit suggestions stay free** on every plan and don't touch credits. Pro $10 / Pro+ $39 include matching credit amounts. The old "fall back to a cheaper model when you run out" is **gone** — you're governed by credits now. ([GitHub blog](https://github.blog/news-insights/company-news/github-copilot-is-moving-to-usage-based-billing/), [Plans for Copilot](https://docs.github.com/en/copilot/get-started/plans))
- **Codex:** token-based since April 2026, on **5-hour rolling windows** for local messages + cloud tasks. Per-task cost varies wildly with codebase size. ([Codex rate card](https://help.openai.com/en/articles/20001106-codex-rate-card), [UI Bakery](https://uibakery.io/blog/openai-codex-pricing))
- **Make it last:**
  - Lean on **free Copilot completions** for autocomplete-style work (zero credit cost).
  - In Codex, **switch to a smaller model** (e.g. mini/`-codex`) for boilerplate to stretch the window. ([Codex rate card](https://help.openai.com/en/articles/20001106-codex-rate-card))
  - Add API-key credits as overflow if a plan limit hits.

### Cross-tool "don't get stuck" tactics

- **Fall-back chain:** if one tool rate-limits mid-sprint, **switch the same task to another tool** — they all read the same `AGENTS.md`, so context transfers. A sensible order for a strapped team: free Gemini / Copilot-completion for cheap work → Cursor Auto → Codex/Claude Sonnet → Opus only when stuck.
- **Spread the load across accounts/people.** Four people on four tools = four independent quota pools. Assign the heaviest agentic work to whoever has the biggest plan.
- **Batch boilerplate onto the cheapest model**, save premium models for the genuinely hard 10%.

---

## 3. Low-Error Prompting (cut hallucinated APIs and bugs)

Context: ~20% of AI package suggestions point to **libraries that don't exist**, and 29–45% of AI code has security issues — but good practices cut hallucinations by up to ~96%. The failure mode is unique: AI code *looks* right, so **you must verify what looks right, not what feels wrong.** ([InfoWorld](https://www.infoworld.com/article/3822251/how-to-keep-ai-hallucinations-out-of-your-code/), [diffray](https://diffray.ai/blog/llm-hallucinations-code-review/))

**Techniques that work:**

- **Give it a context file** with your stack, conventions, and the libraries you actually use.
- **Pin exact versions.** Knowledge cutoffs cause hallucinated APIs — name the version (e.g. "FastAPI 0.115, Pydantic v2, React 19, Vite 6") and keep a lockfile. Never blindly install a package the AI invented. ([diffray](https://diffray.ai/blog/llm-hallucinations-code-review/), [InfoWorld](https://www.infoworld.com/article/3822251/how-to-keep-ai-hallucinations-out-of-your-code/))
- **Constrain to typed shapes.** Hand it the Pydantic model / TypeScript interface / OpenAPI schema and say "match this exactly." Typed code is executable and testable, so hallucinations surface fast. ([Addy Osmani](https://medium.com/@addyosmani/my-llm-coding-workflow-going-into-2026-52fe1681325e))
- **Anchor to existing code.** "Here is the current implementation of X; extend it to do Y without breaking Z." Tell it to follow existing patterns, not invent new ones.
- **Tell it to ask, not guess.** Add: *"If unsure or missing context, ask for clarification instead of making something up."* ([InfoWorld](https://www.infoworld.com/article/3822251/how-to-keep-ai-hallucinations-out-of-your-code/))
- **Plan then code.** Have it produce a short plan first; approve it; *then* implement. (Claude Code plan mode / Gemini Plan Mode / Cursor — same idea.) ([Addy Osmani](https://medium.com/@addyosmani/my-llm-coding-workflow-going-into-2026-52fe1681325e))
- **Small diffs.** Ask for one focused change at a time. Big AI diffs are where bugs hide and reviews fail. ([Addy Osmani](https://medium.com/@addyosmani/my-llm-coding-workflow-going-into-2026-52fe1681325e))
- **Make it run/test.** Require it to run the code or tests before declaring done — agents routinely claim success while tests fail. ([Augment Code](https://www.augmentcode.com/guides/how-to-run-a-multi-agent-coding-workspace))
- **Keep a shared "AI lies about these" note.** Track which libraries/patterns your AIs hallucinate and share it with the team.

---

## 4. Per-Tool Strengths — use X for Y

Consensus 2026 rule of thumb: **Claude Code for craftsmanship, Codex for endurance, Gemini for free huge-context, Cursor for in-editor speed & parallel agents.** ([ofox.ai](https://ofox.ai/blog/agentic-coding-claude-codex-gemini-cursor-2026/), [CodeAnt](https://www.codeant.ai/blogs/claude-code-cli-vs-codex-cli-vs-gemini-cli-best-ai-cli-tool-for-developers-in-2025))

| Job to do | Reach for | Why |
|---|---|---|
| Multi-file refactor, wiring frontend↔backend, running commands/tests | **Claude Code** | Highest code quality (~81% SWE-bench), strong multi-file reasoning, runs in terminal with your approval |
| "Explain this whole codebase / where is X / read everything" | **Gemini CLI** | 1M-token context holds a mid-sized repo at once; free 1,000 req/day |
| Fast in-editor edits, multi-file Composer, you're living in the IDE | **Cursor** | VS Code fork, unlimited Tab, Auto mode free; parallel cloud background agents |
| Autocomplete / line-by-line completion while typing | **Copilot** | Completions are free on all plans and don't burn credits |
| Long autonomous "go build this and come back" runs; token-efficient batch work | **Codex CLI** | Built for long unattended runs; ~4× more token-efficient than Claude Code |
| Quick bug fix / script / CI snippet in the terminal | **Claude Code or Codex** | Both shine at terminal automation |
| Free overflow when another tool rate-limits | **Gemini / Copilot completions** | Most generous free allowances |

---

## 5. Multi-Tool / Multi-Person Coordination (the hackathon-killer section)

Git detects **textual** conflicts, not **semantic** ones — and agents don't notice when they've broken each other. Three people each driving a different AI on one repo *will* collide unless you set rails. ([MindStudio](https://www.mindstudio.ai/blog/git-worktrees-parallel-ai-coding-agents), [Augment Code](https://www.augmentcode.com/guides/how-to-run-a-multi-agent-coding-workspace))

**The rails, in priority order:**

1. **One shared rules file, read by all tools.** Use **`AGENTS.md`** as the single source of truth — it's the open standard (Linux Foundation; read natively by Codex, Cursor, Copilot, Gemini CLI, Windsurf, Zed). Then:
   - **Claude Code:** symlink `CLAUDE.md → AGENTS.md` (Claude auto-loads `CLAUDE.md`, AGENTS.md support is not native yet).
   - **Gemini:** point `GEMINI.md` at the same content (or symlink).
   - Don't copy-paste rules into four files — they'll drift. One file, symlinks/pointers. ([benjamincrozat](https://benjamincrozat.com/agents-md), [Augment Code](https://www.augmentcode.com/guides/how-to-build-agents-md), [deployHQ](https://www.deployhq.com/blog/ai-coding-config-files-guide))

2. **Lock the API contract first.** Define the FastAPI/Pydantic schema (or OpenAPI spec) and generate the TypeScript types from it. Frontend and backend agents code against the **contract**, not against each other's in-progress code. This single move removes most cross-person conflicts.

3. **Decompose by boundary, not by file.** Assign each person/AI a **folder or layer** (e.g. backend routes / DuckDB data layer / React pages / shared components). Tasks split by domain rarely touch the same files. ([Augment Code](https://www.augmentcode.com/guides/how-to-run-a-multi-agent-coding-workspace))

4. **Single-writer rule for hotspot files.** Routers, config, `__init__`/registry, route tables, `package.json` — exactly **one person** touches each. These central files are where parallel agents always collide. ([MindStudio](https://www.mindstudio.ai/blog/git-worktrees-parallel-ai-coding-agents))

5. **Small, frequent commits + sequential merges.** Commit every small unit. Merge **one branch at a time**, rebasing the rest on the new `main` so each merge sees prior changes. Run tests at every merge. Don't merge everything at once. ([MindStudio](https://www.mindstudio.ai/blog/parallel-ai-coding-agents-git-worktrees))

6. **Optional but powerful — git worktrees.** If one person wants to run multiple agents, give each its own worktree off `main` so they can't corrupt each other's files. (Note: worktrees isolate files, not databases/Docker/`.env` — and don't run concurrent git commands across them.) Practical sweet spot: **2–3 agents** on a tightly-coupled codebase, up to 5 on loosely-coupled. ([dev.to](https://dev.to/battyterm/how-to-use-git-worktrees-to-run-multiple-ai-agents-on-the-same-repo-1on8))

7. **Shared task list everyone reads.** A simple `TASKS.md` (or board) where each person marks tasks in-progress/done. Filesystem isolation handles *files*; the task list handles *who's doing what*.

---

## 6. DO / AVOID Cheat-Sheet

| ✅ DO | ❌ AVOID |
|---|---|
| One `AGENTS.md` (symlinked to CLAUDE/GEMINI), < 500 tokens | Four divergent rules files, or a 2,000-line context file |
| Lock the API schema before building either side | Frontend & backend agents guessing each other's shapes |
| One writer per file; split work by folder/layer | Two agents editing the same router/config |
| Pin exact library versions; verify packages exist | Installing whatever package the AI suggests |
| Plan → approve → code in small diffs | "Build the whole app" mega-prompts |
| Make the AI run tests before "done" | Trusting "it compiles" / "looks right" |
| Commit small & often; merge sequentially with tests | One giant end-of-day merge |
| Auto/Sonnet/cheap models for boilerplate; premium for hard parts | Opus/MAX/Agent mode for trivial edits |
| `/clear` per task, `/compact` after each phase | One 40-message marathon session |
| Use free tiers (Gemini, Copilot completions) as overflow | Burning one account's quota while others sit idle |

---

## 7. 45-Second Pre-Prompt Checklist (glance before you hit enter)

1. **Right tool?** Hard multi-file/refactor → Claude Code · huge-context read → Gemini · in-editor edit → Cursor · autocomplete → Copilot · long autonomous run → Codex.
2. **Right model?** Cheap/Auto/Sonnet for boilerplate; premium only for hard reasoning.
3. **Scoped?** Did I name the **exact files** and **not** dump the whole repo?
4. **Contract?** Pasted the relevant **schema/types/versions** it must match?
5. **Anchored?** Pointed at the **existing code** to extend, with "don't break X"?
6. **Small?** Is this **one** change, not five? Asked for a small diff?
7. **Verify?** Did I tell it to **run/test** and to **ask if unsure** rather than guess?
8. **Collision?** Is anyone else's AI touching this same file right now?
9. **Context clean?** Should I `/clear` or `/compact` before starting?
10. **Commit.** Last unit committed so I can roll back?

---

### Sources
Limits & pricing: [TrueFoundry](https://www.truefoundry.com/blog/claude-code-limits-explained), [TokenMix](https://tokenmix.ai/blog/complete-claude-limits-guide-2026-tokens-uploads-5-hour), [Verdent](https://www.verdent.ai/guides/claude-code-limits-doubled-may-2026), [apidog](https://apidog.com/blog/claude-code-weekly-limits-50-percent-increase-july-2026/), [Cursor docs](https://cursor.com/docs/models-and-pricing), [Vantage](https://www.vantage.sh/blog/cursor-pricing-explained), [Finout](https://www.finout.io/blog/what-happened-to-cursor-pricing-2026-guide-5-cost-cutting-tips), [Google blog](https://blog.google/innovation-and-ai/technology/developers-tools/introducing-gemini-cli-open-source-ai-agent/), [Gemini Code Assist quotas](https://developers.google.com/gemini-code-assist/resources/quotas), [Gemini CLI quotas](https://geminicli.com/docs/resources/quota-and-pricing/), [GitHub Copilot billing](https://github.blog/news-insights/company-news/github-copilot-is-moving-to-usage-based-billing/), [Copilot plans](https://docs.github.com/en/copilot/get-started/plans), [Codex rate card](https://help.openai.com/en/articles/20001106-codex-rate-card), [Codex pricing](https://uibakery.io/blog/openai-codex-pricing).
Context & prompting: [HumanLayer](https://www.humanlayer.dev/blog/writing-a-good-claude-md), [Claude Code best practices](https://code.claude.com/docs/en/best-practices), [ClaudeLog](https://claudelog.com/faqs/how-to-optimize-claude-code-token-usage/), [MindStudio](https://www.mindstudio.ai/blog/how-to-manage-claude-code-token-usage), [buildtolaunch](https://buildtolaunch.substack.com/p/claude-code-token-optimization), [Addy Osmani](https://medium.com/@addyosmani/my-llm-coding-workflow-going-into-2026-52fe1681325e), [InfoWorld](https://www.infoworld.com/article/3822251/how-to-keep-ai-hallucinations-out-of-your-code/), [diffray](https://diffray.ai/blog/llm-hallucinations-code-review/).
Tool comparison & coordination: [ofox.ai](https://ofox.ai/blog/agentic-coding-claude-codex-gemini-cursor-2026/), [CodeAnt](https://www.codeant.ai/blogs/claude-code-cli-vs-codex-cli-vs-gemini-cli-best-ai-cli-tool-for-developers-in-2025), [Augment Code (multi-agent)](https://www.augmentcode.com/guides/how-to-run-a-multi-agent-coding-workspace), [MindStudio (worktrees)](https://www.mindstudio.ai/blog/git-worktrees-parallel-ai-coding-agents), [AGENTS.md guide](https://benjamincrozat.com/agents-md), [deployHQ config files](https://www.deployhq.com/blog/ai-coding-config-files-guide).

> ⚠️ **Verify before relying:** This space changes monthly. Re-check each tool's current pricing/limits page before the event — especially the **Gemini CLI → Antigravity migration (June 18, 2026)** and **Copilot usage-based billing (June 1, 2026)** cutovers, both of which may affect your event directly.
