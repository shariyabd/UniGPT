# Claude Code Skills — Installation Guide

> This file is **git-ignored** (see `.gitignore`) — local reference only.

How to install Claude Code skills from a GitHub repo, into a **project** or **globally**.
Worked example: the [ui-ux-pro-max-skill](https://github.com/nextlevelbuilder/ui-ux-pro-max-skill) repo.

---

## Where skills live

| Scope | Path | Available in |
|-------|------|--------------|
| **Project** | `<project>/.claude/skills/<name>/` | only that repo |
| **Global**  | `~/.claude/skills/<name>/` | all your projects |

A skill is just a folder containing a `SKILL.md` (with `name:` + `description:` frontmatter),
optionally plus `scripts/`, `data/`, `references/`, `templates/`.
Claude Code auto-discovers them — no restart/command needed; new sessions pick them up.

---

## Steps

### 1. Clone the repo to a temp location
```bash
git clone --depth 1 https://github.com/nextlevelbuilder/ui-ux-pro-max-skill /tmp/ui-ux-pro-max-skill
```

### 2. Find the skills (each = a folder with a SKILL.md)
```bash
find /tmp/ui-ux-pro-max-skill -name SKILL.md
```

### 3. Create the skills directory
```bash
mkdir -p .claude/skills        # PROJECT
mkdir -p ~/.claude/skills      # GLOBAL
```

### 4. Copy a skill in — ALWAYS use `cp -rL` (capital L)
```bash
# project
cp -rL /tmp/ui-ux-pro-max-skill/.claude/skills/ui-ux-pro-max .claude/skills/

# global
cp -rL /tmp/ui-ux-pro-max-skill/.claude/skills/ui-ux-pro-max ~/.claude/skills/
```

> ⚠️ **Gotcha:** this repo **symlinks** `data/` and `scripts/` into `src/`.
> A plain `cp -r` copies the *broken* links (empty folders).
> `cp -rL` **dereferences** symlinks and copies the real files. `-L` is always safe.

### 5. Install many at once
```bash
SRC=/tmp/ui-ux-pro-max-skill/.claude/skills
for s in banner-design brand design design-system slides ui-styling ui-ux-pro-max; do
  cp -rL "$SRC/$s" ~/.claude/skills/      # or .claude/skills/ for project-only
done
```

### 6. Verify
```bash
ls ~/.claude/skills/
head -5 ~/.claude/skills/ui-ux-pro-max/SKILL.md   # should show name: / description:
# optional smoke test for ui-ux-pro-max:
python3 ~/.claude/skills/ui-ux-pro-max/scripts/search.py "dashboard dark mode" --max-results 1
```

### 7. Clean up
```bash
rm -rf /tmp/ui-ux-pro-max-skill
```

---

## This repo's skills (all installed globally)

| Skill | Notes |
|-------|-------|
| `ui-ux-pro-max` | Stdlib-only Python; fully working (also installed project-local) |
| `banner-design`, `brand`, `design`, `design-system`, `slides`, `ui-styling` | Guidance works as-is |

**Optional Python deps** — only if you run certain skills' image/codegen scripts:
```bash
pip install pillow google-genai
```
(The `pytest` imports inside skills are their bundled tests, not runtime requirements.)

---

## Keeping skills out of git

`.gitignore` contains:
```
.claude            # ignores everything under .claude/ (incl. skills)
.claude/skills/    # explicit, for clarity
/SKILLS_INSTALL.md # this guide
```

To **share** a skill with your team instead, move it out of `.claude/skills/` into a
tracked path, or force-add it: `git add -f .claude/skills/<name>`.
