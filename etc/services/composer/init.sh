#!/bin/sh

# There's no `useradd` command in this container image, so we do it manually.
# There's no need to add the group, as our usage doesn't require that.
echo 'user:x:'$UID':'$GID':user:/home/user:/bin/sh' >> /etc/passwd

mkdir /home/user/.ssh
