---
type: Reference
title: Ecosystem docs
description: "Published ScrapyardIO ecosystem docs for microscrap/mpsse 0.7.x."
resource: "https://scrapyard-io.projectsaturnstudios.com/ecosystem/microscrap/mpsse/0.7.x/overview"
tags: [orientation, docs, ecosystem, 0.7]
generated: { by: "okf-documentation-generator/cursor", at: "2026-08-10T21:28:00Z" }
status: draft
sources:
  - id: composer-homepage
    resource: composer.json
    title: composer.json homepage / support.docs fields
  - id: overview
    resource: "https://scrapyard-io.projectsaturnstudios.com/ecosystem/microscrap/mpsse/0.7.x/overview"
    title: Ecosystem overview page
  - id: readme
    resource: README.md
    title: README docs badge and link
---

# Entrypoint

Human-facing package docs live on the ScrapyardIO ecosystem site:[^overview]

[https://scrapyard-io.projectsaturnstudios.com/ecosystem/microscrap/mpsse/0.7.x/overview](https://scrapyard-io.projectsaturnstudios.com/ecosystem/microscrap/mpsse/0.7.x/overview)

`composer.json` `homepage` and `support.docs` point at that overview.[^composer-homepage][^readme]

# How agents should use it

- Prefer this OKF bundle for **in-repo** agent rules (wrap layer, traps, enums).
- Prefer the ecosystem site for **published** narrative docs aimed at humans.
- When either drifts from `src/`, update the stale side and note it in [log.md](../log.md).

# Related

* [Package (0.7)](package.md)

[^composer-homepage]: composer.json homepage / support.docs fields
[^overview]: Ecosystem overview page
[^readme]: README docs badge and link
