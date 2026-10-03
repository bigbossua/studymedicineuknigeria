#!/usr/bin/env bash
# Prepares SSH for the Hostinger server in a Claude cloud session.
# Reads the private key from the environment secret HOSTINGER_SSH_PRIVATE_KEY (full PEM/OpenSSH text,
# newlines preserved or encoded as literal "\n"), writes it to ~/.ssh with safe permissions, and
# writes an SSH config entry named "hostinger" from HOSTINGER_SSH_HOST / HOSTINGER_SSH_PORT / HOSTINGER_SSH_USER.
# Never prints the private key. Usage:  source ops/ssh-setup.sh && ssh hostinger 'uname -a'
set -euo pipefail
: "${HOSTINGER_SSH_HOST:?set HOSTINGER_SSH_HOST (server hostname or IP from hPanel → SSH Access)}"
: "${HOSTINGER_SSH_USER:?set HOSTINGER_SSH_USER (e.g. u123456789)}"
PORT="${HOSTINGER_SSH_PORT:-65002}"
install -d -m 700 ~/.ssh
KEY=~/.ssh/smukn_hostinger_ed25519
if [ -n "${HOSTINGER_SSH_PRIVATE_KEY:-}" ]; then
  printf '%b\n' "$HOSTINGER_SSH_PRIVATE_KEY" | sed 's/\r$//' > "$KEY"
  chmod 600 "$KEY"
elif [ ! -f "$KEY" ]; then
  echo "No private key: set HOSTINGER_SSH_PRIVATE_KEY as an environment secret, or generate a key pair and add its public key in hPanel." >&2
  exit 1
fi
ssh-keygen -y -f "$KEY" > "$KEY.pub" 2>/dev/null || { echo "Private key is not readable by ssh-keygen (format or newline problem)." >&2; exit 1; }
cat > ~/.ssh/config <<CFG
Host hostinger
  HostName $HOSTINGER_SSH_HOST
  Port $PORT
  User $HOSTINGER_SSH_USER
  IdentityFile $KEY
  IdentitiesOnly yes
  StrictHostKeyChecking accept-new
  ServerAliveInterval 30
CFG
chmod 600 ~/.ssh/config
echo "SSH alias 'hostinger' → $HOSTINGER_SSH_USER@$HOSTINGER_SSH_HOST:$PORT (key fingerprint: $(ssh-keygen -lf "$KEY.pub" | awk '{print $2}'))"
