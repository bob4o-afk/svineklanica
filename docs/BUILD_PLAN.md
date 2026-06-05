# Build plan — maximum scope, prod-ready, without bombing efficiency

> Goal: **ship a prod-ready tool that covers as many bases as possible in ~48h** — without the scope sinking us (our known weak spot). The method is simple: **rank everything by payoff-per-hour and take the best ratio first.** Stop adding when the demo path is solid; everything past that is gravy.
>
> Two facts make "prod-ready + big scope" realistic:
> 1. **The prod infra is already scaffolded** (Docker, CI, Caddy TLS, health checks, prod compose, K8s, monitoring). Prod-readiness is mostly *flipping on* what's already here — cheap.
> 2. **"Biggest scope" = go deep+wide WITHIN procurement** (more sources, more detectors, more polish), **not** spreading across many corruption domains. Depth in one vertical reads as "it works"; breadth across five reads as "nothing works." (See `IDEA.md` Decision 1.)

## Legend
- ⏱️ Speed: ⚡ hours · 🔨 ~half-day · 🏗️ a day+/risky
- 💪 How well it'll come out: 🟢 solid/prod-grade · 🟡 fine · 🔴 fragile (data/time risk)
- 🎯 Payoff (score + scope coverage): ⭐ low · ⭐⭐ good · ⭐⭐⭐ high

---

## Tier 0 — Foundation (do first; mostly fast; unblocks everything)
| Item | ⏱️ | 💪 | 🎯 |
|---|---|---|---|
| Generate Laravel + Vite skeletons (`plan.txt` step 1) | ⚡ | 🟢 | ⭐⭐⭐ |
| `make build && up && migrate` — full stack running | ⚡ | 🟢 | ⭐⭐⭐ |
| Data model: `Tender · ContractingAuthority · Company · Flag` migrations | 🔨 | 🟢 | ⭐⭐⭐ |
| **pgvector enabled + vector columns** (unlocks 3 detectors + search) | ⚡ | 🟢 | ⭐⭐⭐ |
| `ingest:run` command — idempotent upsert from NDJSON | 🔨 | 🟢 | ⭐⭐⭐ |
| **TED scraper** → NDJSON (clean, structured, real BG data) | 🔨 | 🟢 | ⭐⭐⭐ |
| Commit a real `samples/*.ndjson` (demo can't die if upstream does) | ⚡ | 🟢 | ⭐⭐⭐ |

## Tier 1 — Product core (the demo lives or dies here)
| Item | ⏱️ | 💪 | 🎯 |
|---|---|---|---|
| **Flag feed** page (the money shot) | 🔨 | 🟢 | ⭐⭐⭐ |
| Search + **entity pages** (company / authority history) | 🔨 | 🟢 | ⭐⭐⭐ |
| **Overpricing detector** (vector-clusters "same item, 5 spellings") | 🔨 | 🟡 | ⭐⭐⭐ |
| **Serial-winner detector** (vector + joins for shell clusters) | 🔨 | 🟡 | ⭐⭐⭐ |
| **Price-over-time graph** (MUI X charts) — big demo wow | ⚡🔨 | 🟢 | ⭐⭐⭐ |
| Punk theme/tokens + BG i18n scaffolding | ⚡ | 🟢 | ⭐⭐ |
| Source link on every flag (it's already in the contract) | ⚡ | 🟢 | ⭐⭐⭐ |

## Tier 2 — Scope expanders (take once Tier 1 is solid; cheap because the rails exist)
| Item | ⏱️ | 💪 | 🎯 |
|---|---|---|---|
| 2nd source: **data.egov.bg** (more real coverage) | 🔨 | 🟡 | ⭐⭐ |
| **Doc-clone detector** (pure vector similarity — cheap once pgvector's in) | 🔨 | 🟢 | ⭐⭐ |
| **Semantic search** box (reuses the embeddings) | ⚡🔨 | 🟢 | ⭐⭐ |
| Cancelled-after-bids detector | 🔨 | 🟡 | ⭐⭐ |
| **PWA installable** ("mobile version" for ~free — plugin already in deps) | ⚡ | 🟢 | ⭐⭐ |
| Serial-winner **graph view** (high wow, some viz risk) | 🔨🏗️ | 🟡 | ⭐⭐ |

## Tier 3 — Prod-readiness (cheap wins — already scaffolded, just wire/flip on)
| Item | ⏱️ | 💪 | 🎯 |
|---|---|---|---|
| Rate-limiting + CORS lockdown (mostly config) | ⚡ | 🟢 | ⭐⭐ |
| CI green on every push (already scaffolded) | ⚡ | 🟢 | ⭐⭐ |
| Public **HTTPS demo URL** via Cloudflare Tunnel | ⚡ | 🟢 | ⭐⭐ |
| Health checks + restart policies (already there) | ⚡ | 🟢 | ⭐ |
| Prod deploy: VM + `docker-compose.prod.yml` + Caddy TLS (real live URL) | 🔨 | 🟢 | ⭐⭐ |
| Error tracking (Sentry) | ⚡🔨 | 🟢 | ⭐ |

## Tier 4 — Stretch / likely traps (high effort, low score-per-hour → defer or skip)
| Item | ⏱️ | 💪 | 🎯 | Verdict |
|---|---|---|---|---|
| SEBRA late-payments source + detector | 🔨🏗️ | 🟡 | ⭐⭐ | only if Tier 1–2 done |
| Trade Register owners (shell links) | 🏗️ | 🔴 | ⭐⭐ | partly paywalled → **curate**, don't auto |
| Honeypot / tarpit / blacklist | 🏗️ | 🟡 | ⭐ | cool, scores ~0 → **skip for demo** |
| Prometheus / Grafana monitoring | 🏗️ | 🟡 | ⭐ | ~0 demo value → **skip** |
| Kubernetes | 🏗️ | 🟡 | ⭐ | Stage 2 (devops.md §8) → **skip** |
| Full auth / user accounts | 🔨 | 🟢 | ⭐ | public read-only tool barely needs it |
| ЦАИС ЕОП deep web-scrape | 🏗️ | 🔴 | ⭐⭐ | messy HTML, time-bomb → only if TED+egov fall short |

---

## The strategy in one line
**Tier 0 → Tier 1 must be done. Then interleave Tier 2 (scope) with the cheap Tier 3 (prod) wins. Touch Tier 4 only if you're ahead.** At every checkpoint, ask: *"is the demo path still solid?"* — if yes, add scope; if shaky, stop and harden.

## Prod-ready definition of done (the bar)
- [ ] Clean clone → `make up` → migrate → ingest sample → site works, from scratch.
- [ ] Runs on real ingested data (not mock), every flag sourced.
- [ ] CI green; rate-limited; HTTPS; health check passes.
- [ ] Cached real-data snapshot committed so a dead upstream can't kill the demo.
- [ ] Public URL reachable (tunnel or VM). README + LICENSE present.
