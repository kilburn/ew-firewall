Easy Web Firewall
=================

Easy Web Firewall is a lightweight iptables-based firewall solution to mitigate
problems resulting from hacked websites in shared virtual hosting servers. In
such servers, vulnerable web applications are usually exploited by "hackers" to
either:

* Scan for additional vulnerabilities, both in the local server and remote ones.
* Send spam mails through a cgi that avoids using the system mailer.

Since this actions do not directly prevent the server from operating normally,
sysadmins do not usually notice that their server is infected until other
servers start taking counter-measures against it. This is, their e-mails begin
being rejected as comming from a spam source or their connections get blocked as
vulnerability scanners. 

Easy Web Firewall prevents these issues by easily allowing the system
administrator to maintain a whitelist of allowed outgoing connections based on
combinations of user, destination, and port. Hence, it blocks everything that is
not whitelisted, promptly notifying the system administrator whenever this
happens. Thereafter, sysadmins can quickly discover infected websites and take
appropiate measures, or extend the whitelist if the connection attempt was
legit. Additionally, since malicious connections are being blocked locally, the
server's reputation will remain intact. This is, it will not be suddently listed
in any rbls or similar blacklists affecting the whole server's user base.

Theory of operation
-------------------

Easy Web Firewall operates by adding a set of rules to iptables that block (and
log) any outgoing connection attemps, except the whitelisted ones specified
through the `outgoing-whitelist` configuration file. Additionally, it includes a
cron task to scan through the iptables generated logs and notify the sysadmin by
e-mail whenever it detects a blocked connection so that she can take the
appropiate action. 

Requirements
------------

 * `iptables`, including support for the `owner` module.
 * `bash` shell (it might work with dash or sh, but it's not tested).
 * php-cli, the command line version of the php interpreter.

Installation
------------

At the time of this writing, ew-firewall has only been tested in Debian. To
install it, start by downloading the program sources either from the [github]
repository. Thereafter, execute `make install` to install its fies:

  * `/etc/ew-firewall/outgoing-whitelist`:
    Contains the rules defining the allowed outgoing connections.
  * `/etc/default/ew-firewall`:
    Base configuration directives.
  * `/etc/init.d/ew-firewall`:
    Init script to start/stop the firewall.
  * `/usr/local/sbin/ew-firewall`:
    Main program logic (rule parser and blocked connections detector),
    implemented in php.
  * `/etc/cron.d/ew-firewall`:
    Cron definition that runs the block detection every 5 minutes.

Next, adapt the `/etc/default/ew-firewall` configuration file according to your
system's setup. We recommend you to keep the `BLOCK` variable to `0` to avoid
actually blocking any connections until you are confident to have already
whitelisted most legit connections. Then, launch the firewall by running
`/etc/init.d/ew-firewall start` and setup it to run at startup (this is
distribution-specific, `update-rc.d ew-firewall defaults` in Debian). Once
started, the firewall will begin logging the connections that would be blocked,
and send them to you by e-mail.

Keep adjusting your rules file whenever you receive an e-mail with blocked
connection reports until you are confindent that all legit connections are
already allowed by your rules. Finally, enable real blocking by setting `BLOCK`
to `1` in the `/etc/defaults/ew-firewall` configuration file, and enjoy your
newly enhanced security!

[github]: http://github.com/kilburn/ew-firewall "ew-firewall in GitHub"

Rules definition file
---------------------

The rules definition file is designed to be as easy as possible. Lines starting
with a dash (`#`) are ignored, whereas each other line defines a rule using the
following format (fields are separated by any number of spaces or tabs):

    UID		Destination		Port

Where:

 - `UID` is the UID or range of UIDs of the matching users, while an asterisk
   (`*`) matches any user. 
   Examples: `1000`, `1000-200`, `*`.

 - `Destination` is an IP address or CIDR block matching the connection's
   destination IP, while an asterisk (`*`) matches any destination IP.
   Examples: 127.0.0.1/8, 1.2.3.4, *.

 - `Port` is the port or port range of the connection, while an asterisk `(*`)
   matches any port.
   Examples: 80, 8000-8005, *.

### Sample rules

Allow all loopback connections to all users.

    *		127.0.0.1/8		*

Allow user 1000 to send e-mails through gmail.

    *		209.85.229.0/24		25

