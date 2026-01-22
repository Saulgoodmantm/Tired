# TiredOfDoinTM — Project Starter & Copilot Agent

This repository contains the Copilot agent prompt and scaffolding for the TiredOfDoinTM photography platform and the scripts to set up your Windows development workspace (C:\Development\tiredprod), GitHub Actions deploy workflow and VPS deploy helper.

Important: This repo contains placeholders only. Do NOT commit secrets. Set real secrets in GitHub repository settings (Secrets) and on your VPS .env files.

Contents overview
- agents/tiredofdointm-agent.md — Copilot agent prompt and instructions
- scripts/init_workspace.ps1 — PowerShell script to create C:\Development\tiredprod and clone repo
- .github/workflows/deploy.yml — GitHub Actions manual (or push) deploy to VPS via SSH
- vps/deploy_pull.sh — Place on VPS to update code on demand
- .env.example — Template environment variables
- .gitignore — Recommended ignores
- README.md — this file
- skeleton/ — initial skeleton (public/, src/, api/, assets/, migrations/)

Quickstart (high-level)
1. On Windows dev machine:
   - Install Git, PowerShell (>= 7), Node.js, (optional PHP), and GitHub Copilot (Copilot IDE).
   - Open PowerShell as Administrator and run `scripts\init_workspace.ps1` (edit remote placeholder before running).
2. Create a private repo on GitHub (Saulgoodmantm/tired recommended).
3. Add GitHub secrets: `VPS_SSH_KEY`, `VPS_HOST`, `VPS_USER`, `R2_ACCESS_KEY`, `R2_SECRET`, `STRIPE_SECRET`, `STRIPE_PUBLISHABLE`, `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, etc.
4. Push this repo to GitHub.
5. Set up VPS:
   - Provision Ubuntu 22.04 droplet.
   - Add your SSH public key to `~/.ssh/authorized_keys`.
   - Create folder `/home/<vps_user>/tiredprod`.
   - Copy `vps/deploy_pull.sh` to server (or let GitHub Actions SSH and place it).
6. Use GitHub Actions Deploy (workflow can be run manually) to SSH into VPS and pull repo, install deps.

Copilot IDE setup (recommended)
- Install Copilot in VS Code or Copilot IDE.
- Open this repo folder as project root.
- Copy the `agents/tiredofdointm-agent.md` into `agents/`.
- Use Copilot coding agent to "Run" the agent to scaffold additional files (the agent is configured to use read/edit/search tools).
- Recommended editor settings:
  - Enable `files.watcherExclude` for node_modules, .git, storage
  - Enable format-on-save and Prettier/ESLint extensions
  - Configure Copilot suggestions aggressive mode (in Copilot settings)

Deployment options
- Manual: SSH to VPS and run `/home/<vps_user>/tiredprod/vps/deploy_pull.sh`
- CI: Use `/.github/workflows/deploy.yml` to deploy on demand (workflow_dispatch) or on push to `main`.

See below for more detailed instructions and the scripts themselves.