
Name: app-date
Group: ClearOS/Apps
Version: 6.0
Release: 0.2
Summary: Date and time settings
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Requires: %{name}-core = %{version}-%{release}
Buildarch: noarch

%description
Synchronize the clock and set the date and time zone.

%package core
Summary: Core libraries and install for app-date
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base-core
Requires: app-cron-core
Requires: ntpdate >= 4.2.4p8

%description core
Core API and install for app-date

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/date
cp -r * %{buildroot}/usr/clearos/apps/date/

install -D -m 0644 packaging/app-date.cron %{buildroot}/etc/cron.d/app-date
install -D -m 0755 packaging/timesync %{buildroot}/usr/sbin/timesync

%post
logger -p local6.notice -t installer 'app-date - installing'

%post core
logger -p local6.notice -t installer 'app-date-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/date/deploy/install ] && /usr/clearos/apps/date/deploy/install
fi

[ -x /usr/clearos/apps/date/deploy/upgrade ] && /usr/clearos/apps/date/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-date - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-date-core - uninstalling'
    [ -x /usr/clearos/apps/date/deploy/uninstall ] && /usr/clearos/apps/date/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/date/controllers
/usr/clearos/apps/date/htdocs
/usr/clearos/apps/date/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/date/packaging
%exclude /usr/clearos/apps/date/tests
%dir /usr/clearos/apps/date
/usr/clearos/apps/date/config
/usr/clearos/apps/date/deploy
/usr/clearos/apps/date/language
/usr/clearos/apps/date/libraries
/etc/cron.d/app-date
/usr/sbin/timesync