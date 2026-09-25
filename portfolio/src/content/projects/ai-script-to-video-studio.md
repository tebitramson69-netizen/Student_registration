---
title: AI Script-to-Video Studio
summary: A Laravel orchestration layer that turns a written script into a narrated, multi-scene video — built against interfaces so it could be finished from a country its providers do not serve.
role: Sole architect and developer
category: Media pipeline
order: 2
featured: true
year: '2026'
status: Pipeline complete end to end — live providers pending payment access
tech:
  - Laravel 13
  - PHP 8.3
  - Queues & jobs
  - MySQL / SQLite
  - PHPUnit
  - FFmpeg
  - Blade
  - Git
repo: https://github.com/tebitramson69-netizen/ai-script-to-video-studio
metrics:
  - value: '364'
    label: Automated tests
  - value: '~20k'
    label: Lines of PHP
  - value: '18'
    label: Schema migrations
---

## The problem

Turning a script into a finished video is not one task, it is eight, and they
have to agree with each other: break the script into scenes, work out who
appears in it, generate a visual for each character and keep that character
looking like themselves across every shot, render each shot, narrate the whole
thing, score it, mix it so the music does not bury the narrator, and stitch the
result into one playable file.

Each of those steps is a call to a different external service that can be slow,
can fail halfway, and charges money every time it runs. The interesting
engineering is not any single generation call. It is the coordination.

## The goal

Build the orchestration layer — a system that turns a script into a structured
plan, drives external providers for each media type, survives their failures,
keeps a running cost ceiling, and assembles the pieces into one exported `.mp4`.

Explicitly **not** a video model. This project generates nothing itself.

## The constraint that shaped everything

I could not pay for the providers.

fal.ai, ElevenLabs and the rest require payment methods that are not
straightforwardly available from Cameroon. That constraint was not a footnote —
it was the central architectural fact. I had a choice between waiting until
access was solved and building nothing, or building the whole system against
interfaces it could not yet call.

I built against the interfaces.

Every capability is defined as a PHP contract — `VideoGenerator`,
`SpeechSynthesizer`, `ImageGenerator`, `MusicGenerator`, `SoundEffectGenerator`
— with two implementations behind each: a real fal.ai adapter, and a local
`Fake` driver that emits genuine PNG, WAV and MP4 files. The fake drivers are
not stubs that return `true`. They produce real media, so the assembly stage,
the timing maths and the FFmpeg mixing are all exercised against actual bytes.

The result is that `php artisan test` runs the entire pipeline end to end,
offline, at zero cost, and produces a genuinely playable `.mp4` on every run.
Going live becomes a `.env` change, not a rewrite.

## Architecture

```
Controllers ──▶ Jobs (queued) ──▶ Services ──▶ Contracts ──▶ Integrations
                                                              ├── Fal/    (real)
                                                              └── Fake/   (local)
```

Every generation step is a queued job — `StructureScriptJob`, `PlanShotsJob`,
`RenderShotJob`, `GenerateNarrationJob`, `GenerateMusicJob`, `AssembleProjectJob`
— because these operations take minutes and no HTTP request should be holding
the line while they run. A `ProjectStateMachine` governs which transitions are
legal, so a project cannot be assembled before its shots exist.

A `GenerationLedger` records every provider request with an idempotency key. If
a job is retried — and queued jobs are retried — the ledger is what stops the
system paying twice for the same second of video.

## Two decisions I had to defend

**Narration is the master clock.** The obvious design generates video first and
fits the voiceover into it. That is backwards: viewers forgive a shot held a
beat too long, and do not forgive a narrator cut off mid-sentence. So narration
timing is estimated first and shot durations are planned to fit it, with
explicit trim-and-hold rules where a provider's fixed clip length does not
divide neatly into the line being read.

**The script structurer is deterministic, not an LLM.** Breaking a script into
scenes is the one step that needs no provider at all, so making it an API call
would have meant paying for — and waiting on — something a well-written splitter
does reliably. It is also the only capability whose *default* implementation is
real rather than faked.

Music is where I spent the most research time and it turned out to be the place
a naive choice costs the most. The price spread across the available music
models is roughly **125×** for the same job, and the expensive ones generate
*songs* — vocals that fight the narrator and that audio ducking cannot rescue.
So the default is instrumental-only by construction, and the generator refuses
to call a vocal-capable model that nothing has explicitly told to stop singing.

## Testing

364 automated tests, including an end-to-end test that asserts the exported file
is a real h264 + AAC video rather than merely a file that exists. Provider
adapters are tested against faked HTTP, and the response mapper is written to
tolerate being wrong — it locates output by structure as well as by field name,
because I could not verify the live response shape from here.

## What I learned

Building against a constraint you cannot remove is a design skill, not a
consolation prize. The payment barrier forced an abstraction layer I might
otherwise have skipped as over-engineering — and that layer is now the reason
the project is testable, portable between providers, and cheap to run.

I also learned to be precise about what I actually know. The repository grades
its own assumptions: things the account owner verified directly, and things
corroborated only by third-party documentation I could not check against a live
account. Marking the difference is more useful to the next developer than
pretending the whole thing is equally certain.
