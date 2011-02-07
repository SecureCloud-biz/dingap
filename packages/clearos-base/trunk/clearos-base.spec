#------------------------------------------------------------------------------
# P A C K A G E  I N F O
#------------------------------------------------------------------------------

Name: clearos-base
Version: 6.0
Release: 0.4%{dist}
Summary: Initializes the system environment
License: GPLv3 or later
Group: ClearOS/Core
Source: %{name}-%{version}.tar.gz
Vendor: ClearFoundation
Packager: ClearFoundation
# Base product release information
Requires: clearos-release = 6.0
# Core system 
Requires: cronie
Requires: gnupg
Requires: grub
Requires: kernel >= 2.6.32
Requires: mdadm
Requires: mlocate
Requires: ntpdate
Requires: openssh-server
Requires: openssh-clients
Requires: perl
Requires: selinux-policy-targeted
Requires: sudo
Requires: rsyslog
# Common tools used in install and upgrade scripts for app-* packages
Requires: bc
Requires: chkconfig
Requires: coreutils
Requires: findutils
Requires: gawk
Requires: grep
Requires: sed
Requires: shadow-utils
Requires: util-linux
Requires: which
Requires: /usr/bin/logger
Requires: /sbin/pidof
# TODO: remove Provides: perl(functions)
Provides: perl(functions)
Provides: indexhtml
Provides: cc-setup
Obsoletes: cc-setup
Obsoletes: cc-shell
Obsoletes: cc-support
Obsoletes: indexhtml
BuildArch: noarch
BuildRoot: %_tmppath/%name-%version-buildroot

%description
Initializes the system environment

#------------------------------------------------------------------------------
# B U I L D
#------------------------------------------------------------------------------

%prep
%setup
%build

#------------------------------------------------------------------------------
# I N S T A L L  F I L E S
#------------------------------------------------------------------------------

%install
[ "$RPM_BUILD_ROOT" != "/" ] && rm -rf $RPM_BUILD_ROOT

mkdir -p -m 755 $RPM_BUILD_ROOT/usr/clearos
mkdir -p -m 755 $RPM_BUILD_ROOT/usr/sbin
mkdir -p -m 755 $RPM_BUILD_ROOT/usr/share/clearos/base/scripts
mkdir -p -m 755 $RPM_BUILD_ROOT/etc/clearos
mkdir -p -m 755 $RPM_BUILD_ROOT/etc/logrotate.d
mkdir -p -m 755 $RPM_BUILD_ROOT/etc/cron.d
mkdir -p -m 755 $RPM_BUILD_ROOT/etc/init.d

install -m 644 etc/cron.d/app-servicewatch $RPM_BUILD_ROOT/etc/cron.d/app-servicewatch
install -m 644 etc/logrotate.d/compliance $RPM_BUILD_ROOT/etc/logrotate.d/
install -m 644 etc/logrotate.d/system $RPM_BUILD_ROOT/etc/logrotate.d/
install -m 755 etc/init.d/functions-automagic $RPM_BUILD_ROOT/etc/init.d/
install -m 755 sbin/addsudo $RPM_BUILD_ROOT/usr/sbin/addsudo
install -m 755 scripts/* $RPM_BUILD_ROOT/usr/share/clearos/base/scripts/

#------------------------------------------------------------------------------
# I N S T A L L  S C R I P T
#------------------------------------------------------------------------------

%post
logger -p local6.notice -t installer "clearos-base - installing"

# Syslog customizations
#----------------------

if [ -z "`grep ^local6 /etc/rsyslog.conf`" ]; then
	logger -p local6.notice -t installer "clearos-base - adding system log file to rsyslog"
	echo "local6.*  /var/log/system" >> /etc/rsyslog.conf
	sed -i -e 's/[[:space:]]*\/var\/log\/messages/;local6.none \/var\/log\/messages/' /etc/rsyslog.conf
	/sbin/service rsyslog restart >/dev/null 2>&1
fi

if [ -z "`grep ^local5 /etc/rsyslog.conf`" ]; then
	logger -p local5.notice -t installer "clearos-base - adding compliance log file to rsyslog"
	echo "local5.*  /var/log/compliance" >> /etc/rsyslog.conf
	sed -i -e 's/[[:space:]]*\/var\/log\/messages/;local5.none \/var\/log\/messages/' /etc/rsyslog.conf
	/sbin/service rsyslog restart >/dev/null 2>&1
fi

# Disable SELinux
#----------------

# if [ -d /etc/selinux ]; then
#	CHECK=`grep ^SELINUX= /etc/selinux/config 2>/dev/null | sed 's/.*=//'`
#	if [ -z "$CHECK" ]; then
#		logger -p local6.notice -t installer "clearos-base - disabling SELinux with new configuration"
#		echo "SELINUX=disabled" >> /etc/selinux/config
#	elif [ "$CHECK" != "disabled" ]; then
#		logger -p local6.notice -t installer "clearos-base - disabling SELinux"
#		sed -i -e 's/^SELINUX=.*/SELINUX=disabled/' /etc/selinux/config
#	fi
#fi

# Changed default group on useradd
#---------------------------------

# FIXME: move to app-users
#CHECK=`grep "^GROUP=100$" /etc/default/useradd 2>/dev/null`
#if [ -n "$CHECK" ]; then
#	logger -p local6.notice -t installer "clearos-base - changing default group ID"
#	sed -i -e 's/^GROUP=100$/GROUP=63000/' /etc/default/useradd
#fi

# Sudo policies
#--------------

CHECKSUDO=`grep '^Defaults:webconfig !syslog' /etc/sudoers 2>/dev/null`
if [ -z "$CHECKSUDO" ]; then
    logger -p local6.notice -t installer "clearos-base - adding syslog policy for webconfig"
    echo 'Defaults:webconfig !syslog' >> /etc/sudoers
    chmod 0440 /etc/sudoers
fi

CHECKSUDO=`grep '^Defaults:root !syslog' /etc/sudoers 2>/dev/null`
if [ -z "$CHECKSUDO" ]; then
    logger -p local6.notice -t installer "clearos-base - adding syslog policy for root"
    echo 'Defaults:root !syslog' >> /etc/sudoers
    chmod 0440 /etc/sudoers
fi

CHECKTTY=`grep '^Defaults.*requiretty' /etc/sudoers 2>/dev/null`
if [ -n "$CHECKTTY" ]; then
    logger -p local6.notice -t installer "clearos-base - removing requiretty from sudoers"
	sed -i -e 's/^Defaults.*requiretty/# Defaults    requiretty/' /etc/sudoers
    chmod 0440 /etc/sudoers
fi

# slocate/mlocate upgrade
#------------------------

CHECK=`grep '^export' /etc/updatedb.conf 2>/dev/null`
if [ -n "$CHECK" ]; then
	CHECK=`grep '^export' /etc/updatedb.conf.rpmnew 2>/dev/null`
	if ( [ -e "/etc/updatedb.conf.rpmnew" ] && [ -z "$CHECK" ] ); then
    	logger -p local6.notice -t installer "clearos-base - migrating configuration from slocate to mlocate"
		cp -p /etc/updatedb.conf.rpmnew /etc/updatedb.conf
	else
    	logger -p local6.notice -t installer "clearos-base - creating default configuration for mlocate"
		echo "PRUNEFS = \"auto afs iso9660 sfs udf\"" > /etc/updatedb.conf
		echo "PRUNEPATHS = \"/afs /media /net /sfs /tmp /udev /var/spool/cups /var/spool/squid /var/tmp\"" >> /etc/updatedb.conf
	fi
fi

exit 0

#------------------------------------------------------------------------------
# U N I N S T A L L  S C R I P T
#------------------------------------------------------------------------------

%preun
if [ $1 -eq 0 ]; then
	logger -p local6.notice -t installer "clearos-base - uninstalling"
fi

#------------------------------------------------------------------------------
# F I L E S
#------------------------------------------------------------------------------

%files
%defattr(-,root,root)
%dir /etc/clearos
%dir /usr/clearos
/etc/cron.d/app-servicewatch
/etc/logrotate.d/compliance
/etc/logrotate.d/system
/etc/init.d/functions-automagic
/usr/sbin/addsudo
%dir /usr/share/clearos/base
%dir /usr/share/clearos/base/scripts
/usr/share/clearos/base/scripts/*
