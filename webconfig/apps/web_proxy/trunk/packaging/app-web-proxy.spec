
Name: app-web-proxy
Group: ClearOS/Apps
Version: 6.1.0.beta2
Release: 1%{dist}
Summary: Web Proxy
License: GPLv3
Packager: ClearFoundation
Vendor: ClearFoundation
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base
Requires: app-network

%description
Web proxy description...

%package core
Summary: Web Proxy - APIs and install
Group: ClearOS/Libraries
License: LGPLv3
Requires: app-base-core
Requires: app-network-core
Requires: app-firewall-core
Requires: squid >= 3.1.10

%description core
Web proxy description...

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/web_proxy
cp -r * %{buildroot}/usr/clearos/apps/web_proxy/

install -d -m 0755 %{buildroot}/var/clearos/web_proxy
install -d -m 0755 %{buildroot}/var/clearos/web_proxy/backup/
install -D -m 0644 packaging/squid.php %{buildroot}/var/clearos/base/daemon/squid.php

%post
logger -p local6.notice -t installer 'app-web-proxy - installing'

%post core
logger -p local6.notice -t installer 'app-web-proxy-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/web_proxy/deploy/install ] && /usr/clearos/apps/web_proxy/deploy/install
fi

[ -x /usr/clearos/apps/web_proxy/deploy/upgrade ] && /usr/clearos/apps/web_proxy/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-web-proxy - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-web-proxy-core - uninstalling'
    [ -x /usr/clearos/apps/web_proxy/deploy/uninstall ] && /usr/clearos/apps/web_proxy/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/web_proxy/controllers
/usr/clearos/apps/web_proxy/htdocs
/usr/clearos/apps/web_proxy/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/web_proxy/packaging
%exclude /usr/clearos/apps/web_proxy/tests
%dir /usr/clearos/apps/web_proxy
%dir /var/clearos/web_proxy
%dir /var/clearos/web_proxy/backup/
/usr/clearos/apps/web_proxy/deploy
/usr/clearos/apps/web_proxy/language
/usr/clearos/apps/web_proxy/libraries
/var/clearos/base/daemon/squid.php
