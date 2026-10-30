#!/bin/sh

# There's no `useradd` command in this container image, so we do it manually.
# There's no need to add the group, as our usage doesn't require that.
echo 'user:x:'$UID':'$GID':user:/home/user:/bin/sh' >> /etc/passwd

mkdir /home/user/.ssh

# The checkout may belong to another user than the one running composer (e.g. a deploy user),
# and git (which composer uses to guess the root version) would refuse it.
git config --system --add safe.directory /app
