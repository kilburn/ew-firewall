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
administrator to maintain a whitelist of allowed outgoing connections, using two
different mechanisms:

First, EWF allows iptables blocking based on
combinations of user, destination, and port. Hence, it blocks everything that is
not whitelisted, promptly notifying the system administrator whenever this
happens. Thereafter, sysadmins can quickly discover infected websites and take
appropiate measures, or extend the whitelist if the connection attempt was
legit. Additionally, since malicious connections are being blocked locally, the
server's reputation will remain intact. This is, it will not be suddently listed
in any rbls or similar blacklists affecting the whole server's user base.

Second, EWF also integrates with tinyproxy, to provide better detection of
blocked remote websites. Because iptables is a low level firewall, it only knows
about destination IP and port of the connections. However, when local websites
try to open remote URLs, the administrator needs to know the actual URL to
decide if that was a legit attempt, or one from a blocked website. Easy Web
Firewall solves this issue by forcing local websites to make their HTTP(S)
requests through a local tinyproxy installation. Thereafter, EWF combines the
logs generated by iptables and tinyproxy to report both the local website that
originated each blocked request, and its destination URL.

Theory of operation
-------------------

Easy Web Firewall's iptables component operates by adding a set of rules to
iptables that block (and log) any outgoing connection attemps, except the
whitelisted ones specified through the `rules` configuration file. Additionally,
it includes a cron task to scan through the iptables generated logs and notify the
sysadmin by e-mail whenever it detects a blocked connection so that she can take
the appropiate action.

For the tinyproxy integration to work, it is only necessary to setup tinyproxy
as described below, and force all websites to make their requests through it. For
websites using php in a debian server, this is surprisingly easy to do. Check the
installation section for detailed instructions.

Requirements
------------

 * `iptables`, including support for the `owner` module.
 * `bash` shell (it might work with dash or sh, but it's not tested).
 * php-cli, the command line version of the php interpreter.
 * `PEAR Console_Getopt`, to parse command line arguments (it should already be
   installed in your system, because it is part of the base PEAR installation).
 * (optional) `tinyproxy`, with the configuration detailed below.

Installation
------------

At the time of this writing, ew-firewall has only been tested in Debian. To
install it, start by downloading the program sources either from the [github]
repository. Thereafter, execute `make install` to install its files:

  * `/etc/ew-firewall/rules`:
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

    A UID		Destination		Port

Where:

 - `A` is the action to take on connections matching this rule. Allowed actions:
   `A` : Allow the connection (no logs generated).
   `I` : Drop the connection without logging.

 - `UID` is the UID or range of UIDs of the matching users, while an asterisk
   (`*`) matches any user. 
   Examples: `1000`, `1000-200`, `*`.

 - `Destination` is an IP address or CIDR block matching the connection's
   destination IP, while an asterisk (`*`) matches any destination IP.
   Examples: 127.0.0.1/8, 1.2.3.4, *.

 - `Port` is the port or port range of the connection, while an asterisk `(*`)
   matches any port.
   Examples: 80, 8000-8005, *.

### Sample firewall rules

Allow all loopback connections to all users.

    A *			127.0.0.1/8			*

Allow user 1000 to send e-mails through gmail.

    A 1000		209.85.229.0/24		25


Tinyproxy integration
---------------------

To receive the full URL of blocked outgoing http requests, it is necessary to
install tinyproxy, and force all websites to use it. Unfortunately, it is not
easy to automate this intallation task, so you will have to perform both of
these steps manually.

### Installing tinyproxy

On debian systems, installing tinyproxy is as easy as running:

    apt-get install tinyproxy

Once apt-get has finished, you need to adjust the following tinyproxy 
configuration settings in `/etc/tinyproxy.conf`:

  * (optional) `Listen 127.0.0.1`, so that tinyproxy can no be accessed from
    outside.
  * `LogFile` should be disabled, because it is incompatible with the required
    `Syslog` directive.
  * `Syslog` must be set to `On`, because EWF looks for tinyproxy's log lines
    in the syslog.
  * `LogLevel` must be set to `Notice` or higher, or tinyproxy will not log
    connections being blocked.
  * `FilterURLs` is recommended for the extra flexibility when specifying what
    to block.
  * `FilterExtended` is also recommended for flexibility.
  * `FilterDefaultDeny` must be enabled.

You are free to modify any of the other configuration settings to better suit
your installation. Remember to restart tinyproxy after making any configuration
changes.

### Forcing php websites to go through the proxy

After installing tinyproxy, we need to tell all our websites to use it whenever
making external connections. This could be done by setting up a tinyproxy as a
transparent proxy. However, it is impossible to make SSL connections through a
transparent proxy, because the proxy is effectively a man-in-the-middle attacker,
against which SSL is designed to protect. Therefore, we encourage you to setup
users' websites to explicitly use tinyproxy for their outgoing connections.

In the case of PHP websites, and when running under debian, you can force all
outgoing connections to go through the proxy in the following way:

  1. Copy the `etc/php/proxy.php` file to `/usr/share/php`. This php file,
     when included, sets up the local proxy for any php functions that make
     external http(s) connections.
  2. Copy the `etc/php/proxy.ini` file to `/etc/php5/conf.d/`. This ini file
     forces the execution of the above `proxy.php` before running any php script,
     effectively enabled the proxy system-wide.

### Allowing connections through tinyproxy

Tinyproxy includes its own list of allowed domains/URLs, usually located in
`/etc/tinyproxy/filter`. Hence, when using the tinyproxy integration, you should
not allow direct connections from the local websites to remote ones using EWF's
`/etc/ew-firewall/rules`. Instead, you should allow any legit domains/URLs using
tinyproxy's `filter` file. Also, remember to reload tinyproxy's configuration
whenever you change the filters.
