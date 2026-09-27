#!/usr/bin/env python3
"""
pull_updates_plesk.py — Automated Git to Plesk deployment tool.
Pulls latest changes from Git and synchronizes them with Plesk server.
Supports:
  1. Automated file sync via Plesk REST API (diff-based)
  2. Plesk Git Webhook trigger (if configured)
  3. Status inspection (Local Git vs GitHub vs Plesk)
"""
import argparse
import base64
import json
import os
import ssl
import subprocess
import sys
import urllib.parse
import urllib.request

# Ensure UTF-8 output on Windows console
if sys.stdout.encoding != 'utf-8':
    try:
        sys.stdout.reconfigure(encoding='utf-8')
    except Exception:
        pass

CONFIG_PATH = os.path.expanduser(r"C:\Users\User\.gemini\config\ftp_credentials.json")

# Files and directories to never overwrite or delete on Plesk
PROTECTED_PATHS = {
    ".env",
    ".env.production",
    ".plesk_commit",
    "storage",
    "public/storage",
    "public/signin-logs-lab.php"
}

def load_config():
    if not os.path.exists(CONFIG_PATH):
        print(f"[Error] Configuration file not found at {CONFIG_PATH}")
        sys.exit(1)
    with open(CONFIG_PATH, "r", encoding="utf-8") as f:
        return json.load(f)

class PleskSyncClient:
    def __init__(self, cfg):
        self.host = cfg.get("host")
        self.port = int(cfg.get("port", 8443))
        self.username = cfg.get("username")
        self.password = cfg.get("password")
        self.domain = cfg.get("domain", "trimongkol.com")
        self.webhook_url = cfg.get("webhook_url", "")
        self.base_url = f"https://{self.host}:{self.port}/api/v2"

        self.ctx = ssl.create_default_context()
        self.ctx.check_hostname = False
        self.ctx.verify_mode = ssl.CERT_NONE

        auth_bytes = f"{self.username}:{self.password}".encode("utf-8")
        self.auth_header = f"Basic {base64.b64encode(auth_bytes).decode('utf-8')}"
        self.domain_id = 1533
        self.remote_httpdocs = f"/var/www/vhosts/{self.domain}/httpdocs"

    def _request(self, endpoint, method="GET", data=None, content_type="application/json"):
        url = f"{self.base_url}{endpoint}"
        headers = {
            "Authorization": self.auth_header,
            "Accept": "application/json"
        }
        if data is not None:
            headers["Content-Type"] = content_type

        req = urllib.request.Request(url, data=data, headers=headers, method=method)
        with urllib.request.urlopen(req, context=self.ctx, timeout=30) as resp:
            return resp.status, resp.read()

    def get_deployed_commit(self):
        """Read the last deployed commit hash recorded on Plesk."""
        try:
            q = urllib.parse.urlencode({"path": f"{self.remote_httpdocs}/.plesk_commit"})
            status, content = self._request(f"/domains/{self.domain_id}/fs/content?{q}")
            return content.decode("utf-8").strip()
        except Exception:
            return None

    def set_deployed_commit(self, commit_hash):
        """Record the new deployed commit hash on Plesk."""
        q = urllib.parse.urlencode({"path": f"{self.remote_httpdocs}/.plesk_commit"})
        self._request(
            f"/domains/{self.domain_id}/fs/content?{q}",
            method="PUT",
            data=f"{commit_hash}\n".encode("utf-8"),
            content_type="application/octet-stream"
        )

    def upload_file(self, local_abs_path, remote_rel_path):
        """Upload a file to Plesk."""
        if not os.path.exists(local_abs_path):
            raise FileNotFoundError(f"Local file not found: {local_abs_path}")
        
        with open(local_abs_path, "rb") as f:
            data = f.read()
            
        remote_full = f"{self.remote_httpdocs}/{remote_rel_path.replace(os.sep, '/')}"
        q = urllib.parse.urlencode({"path": remote_full})
        self._request(
            f"/domains/{self.domain_id}/fs/content?{q}",
            method="PUT",
            data=data,
            content_type="application/octet-stream"
        )

    def delete_file(self, remote_rel_path):
        """Delete a file from Plesk."""
        remote_full = f"{self.remote_httpdocs}/{remote_rel_path.replace(os.sep, '/')}"
        q = urllib.parse.urlencode({"path": remote_full})
        try:
            self._request(f"/domains/{self.domain_id}/fs?{q}", method="DELETE")
        except Exception as e:
            # 404 is acceptable when deleting
            pass

    def trigger_webhook(self):
        """Trigger Plesk Git Webhook URL if configured."""
        if not self.webhook_url:
            return False, "No webhook_url found in ftp_credentials.json"
        try:
            req = urllib.request.Request(self.webhook_url, method="POST")
            with urllib.request.urlopen(req, context=self.ctx, timeout=30) as r:
                return True, f"Webhook triggered successfully: HTTP {r.status}"
        except Exception as e:
            return False, f"Failed to trigger webhook: {e}"

def get_git_info(repo_dir):
    """Retrieve Git branch and commit info from local repo."""
    try:
        branch = subprocess.check_output(
            ["git", "-C", repo_dir, "rev-parse", "--abbrev-ref", "HEAD"],
            text=True
        ).strip()
        commit = subprocess.check_output(
            ["git", "-C", repo_dir, "rev-parse", "HEAD"],
            text=True
        ).strip()
        commit_short = commit[:7]
        return branch, commit, commit_short
    except Exception as e:
        print(f"[Error] Failed to read git repo at {repo_dir}: {e}")
        sys.exit(1)

def is_protected(rel_path):
    normalized = rel_path.replace("\\", "/").strip("/")
    for p in PROTECTED_PATHS:
        if normalized == p or normalized.startswith(f"{p}/"):
            return True
    return False

def action_status(client, repo_dir):
    branch, commit, commit_short = get_git_info(repo_dir)
    deployed = client.get_deployed_commit()
    
    print("\n" + "="*50)
    print(" 🚀 Plesk Git Synchronization Status")
    print("="*50)
    print(f"  • Local Repository : {repo_dir}")
    print(f"  • Current Branch   : {branch}")
    print(f"  • Local Commit     : {commit_short} ({commit})")
    print(f"  • Plesk Deployed   : {deployed if deployed else 'Unknown (No .plesk_commit)'}")
    
    if deployed == commit or (deployed and commit.startswith(deployed)):
        print("\n  ✅ Status: Plesk is completely up-to-date with Git!")
    else:
        print("\n  ⚠️  Status: Plesk is BEHIND local Git. Run 'pull' to update.")
        if deployed:
            try:
                diff_summary = subprocess.check_output(
                    ["git", "-C", repo_dir, "diff", "--stat", deployed, commit],
                    text=True
                ).strip()
                print("\n  Changes to be deployed:")
                for line in diff_summary.splitlines()[-10:]:
                    print("   ", line)
            except Exception:
                pass
    print("="*50 + "\n")

def action_pull(client, repo_dir, dry_run=False, force=False):
    branch, commit, commit_short = get_git_info(repo_dir)
    deployed = client.get_deployed_commit()
    
    print(f"\n[Plesk Pull Updates] Target: {client.domain}")
    print(f"Local Git commit: {commit_short} ({branch})")
    print(f"Plesk Git commit: {deployed if deployed else 'Initial/None'}")

    # 1. Check if Webhook is configured
    if client.webhook_url:
        print("\n--> Triggering Plesk Git Webhook...")
        ok, msg = client.trigger_webhook()
        print(f"    {msg}")
        if ok:
            client.set_deployed_commit(commit_short)
            print("✅ Pull Updates triggered via Plesk Webhook successfully!\n")
            return

    # 2. Automated File Sync via Plesk API
    if not deployed or force:
        print("\n[Notice] No previous commit record or force enabled. Comparing with HEAD~1...")
        base_commit = "HEAD~1"
    else:
        base_commit = deployed

    if base_commit == commit:
        print("\n✅ Plesk is already at the latest commit. No files to update.")
        return

    try:
        raw_diff = subprocess.check_output(
            ["git", "-C", repo_dir, "diff", "--name-status", base_commit, commit],
            text=True
        ).strip()
    except Exception as e:
        print(f"[Warning] git diff failed with {base_commit}: {e}. Checking status against HEAD...")
        raw_diff = ""

    if not raw_diff:
        print("No file changes detected between commits.")
        client.set_deployed_commit(commit_short)
        print("Updated .plesk_commit to current commit.")
        return

    changes = []
    for line in raw_diff.splitlines():
        parts = line.strip().split("\t")
        if len(parts) >= 2:
            status = parts[0]
            rel_file = parts[1]
            if not is_protected(rel_file):
                changes.append((status, rel_file))
            else:
                print(f"  [Protected - Skipped] {rel_file}")

    print(f"\nFound {len(changes)} files to synchronize:")
    for status, rel_file in changes:
        action_name = "UPLOAD" if status in ["A", "M"] else ("DELETE" if status == "D" else status)
        print(f"  [{action_name:<6}] {rel_file}")

    if dry_run:
        print("\n[Dry-run] No changes made to server.")
        return

    print("\n--> Uploading / Synchronizing files to Plesk...")
    success_count = 0
    fail_count = 0

    for status, rel_file in changes:
        local_path = os.path.join(repo_dir, rel_file)
        try:
            if status in ["A", "M"]:
                client.upload_file(local_path, rel_file)
                success_count += 1
            elif status == "D":
                client.delete_file(rel_file)
                success_count += 1
        except Exception as e:
            print(f"  [ERROR] Failed to sync {rel_file}: {e}")
            fail_count += 1

    client.set_deployed_commit(commit_short)
    print(f"\n✅ Synchronization complete: {success_count} succeeded, {fail_count} failed.")
    print(f"Plesk deployed commit updated to: {commit_short}\n")

def main():
    parser = argparse.ArgumentParser(description="Plesk Pull Updates / Git Deployer")
    parser.add_argument("command", choices=["pull", "deploy", "status"], default="pull", nargs="?",
                        help="Action to perform: 'pull' (sync updates), 'status' (check diff)")
    parser.add_argument("--repo-dir", default=r"c:\xampp\htdocs\trimongkol_service",
                        help="Path to local git repository (default: c:\\xampp\\htdocs\\trimongkol_service)")
    parser.add_argument("--dry-run", action="store_true", help="Preview file changes without uploading")
    parser.add_argument("--force", action="store_true", help="Force sync all files from last commit")
    args = parser.parse_args()

    cfg = load_config()
    client = PleskSyncClient(cfg)

    repo_dir = os.path.abspath(args.repo_dir)
    if not os.path.exists(repo_dir):
        # Fallback to current working directory
        repo_dir = os.getcwd()

    if args.command == "status":
        action_status(client, repo_dir)
    elif args.command in ["pull", "deploy"]:
        action_pull(client, repo_dir, dry_run=args.dry_run, force=args.force)

if __name__ == "__main__":
    main()
