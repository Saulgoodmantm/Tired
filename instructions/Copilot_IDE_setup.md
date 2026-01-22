Copilot IDE & Workspace Setup (step-by-step)

1) Install tools on Windows dev machine:
   - Git: https://git-scm.com/downloads
   - Node.js (LTS): https://nodejs.org/
   - PowerShell 7 (optional)
   - VS Code or Copilot IDE (GitHub Copilot extension)
   - GitHub CLI (optional but helpful)

2) Create workspace folder
   - Open PowerShell as admin
   - Run: `C:\Development\scripts\init_workspace.ps1 -RemoteUrl "git@github.com:Saulgoodmantm/tired.git"`
     (If no remote yet, remove -RemoteUrl; clone later manually.)

3) Configure Git and remote:
   - `git config --global user.name "Saulgoodmantm"`
   - `git config --global user.email "your-email@example.com"`

4) Generate SSH keys (if not existing)
   - `ssh-keygen -t ed25519 -C "your-email@example.com"`
   - Add public key to GitHub (Settings → SSH and GPG keys)
   - Add public key to VPS `~/.ssh/authorized_keys`

5) Add GitHub Secrets:
   - On GitHub repo Settings → Secrets → Actions add:
     - `VPS_SSH_KEY` (private key content)
     - `VPS_HOST`
     - `VPS_USER`
     - `R2_ACCESS_KEY`, `R2_SECRET_KEY`, `STRIPE_*`, `GOOGLE_CLIENT_ID`, etc.

6) Copilot IDE settings (maximize):
   - Enable faster suggestions, increase suggestion concurrency if available in settings.
   - Add recommended extensions: ESLint, Prettier, PHP Intelephense (if PHP), Docker.
   - Add the `agents/tiredofdointm-agent.md` to the agents directory. Use Copilot coding agent to "run" that prompt.

7) Running the agent:
   - Open the repository in Copilot IDE.
   - Use the Copilot coding agent / custom agent selection and run the `tiredofdointm-copilot-agent`.
   - Instruct it to scaffold the application skeleton and generate the initial files. Review each commit the agent produces.

8) Deploy to VPS:
   - Ensure VPS has your public key and the folder `/home/<user>/tiredprod` created.
   - Run workflow manually from GitHub Actions (Actions → TiredOfDoinTM - Deploy to VPS → Run workflow).
   - Or SSH to the server and run `bash /home/<user>/tiredprod_deploy.sh` after copying deploy script.

9) Iteration:
   - Use Copilot agent to add features, new pages, or to implement endpoints.
   - For each change, push to GitHub, then run deploy workflow manually.

Security notes:
- Never commit secrets.
- Keep `.env` off the repo and store secrets in GitHub secrets and VPS .env file.