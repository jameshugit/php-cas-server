Using Cas Proxy with imap
==========================
you can find a detailed reference for proxy configuration avec cyrus-imap on the following link:
http://www.esup-portail.org/consortium/espace/SSO_1B/tech/imap/CyrusImap-cas.html;
The configurations consists of:
1- Casifiyin Cyrus-imap by the installation of Pam_cas.so Module and configuring the Pam_cas.conf file as explained in : http://www.esup-portail.org/consortium/espace/SSO_1B/tech/cas/cas_pam.html.

		# Prerequisites:
		* pam devel headers (Debian Sarge: libpam0g-dev)
		* openssl devel headers

		# Installation:
		download the latest version from http://www.esup-portail.org/consortium/espace/download/Pam_cas/
		NOTE: users can login case insensitive at the CAS server from v2.0.4


		extract, go to extracted dir
		$ cd sources
		$ cp Makefile.Redhat Makefile
		$ make
		$ make test (to be able to use the "castest" binary)


		check with ldd pam_cas.so


		$ cp pam_cas.so /lib/security/
		$ chmod 644 /lib/security/pam_cas.so

		$ cp ../pam_cas.conf /etc/security/
		$ vi /etc/security/pam_cas.conf
		------/etc/security/pam_cas.conf---------
		# host from CAS server. mandatory
		host www.dev.laclasse.com

		# port from CAS server. Default to 80 or 443, depends from ssl instruction
		#port 443

		# uri to validate ticket. Default to /proxyValidate
		uriValidate /sso/proxyValidate

		# https or no. values on or off. Default to on.
		ssl on

		# debug (on) or no (off). debug in syslog, level LOG_DEBUG. Default to off
		debug on

		# proxy or proxies who deliver Proxy Ticket.
		# If no proxy, pam_cas doesn't control it
		# It may be several proxy instructions
		#SET ONE IN PRODUCTION FOR SECURITY, else none are checked!
		#NOTE: proxy checks are OR
		#proxy https://uportal2.its.yale.edu/CasProxyServlet

		# trusted_ca. mandatory if ssl on.
		# It a file in pem format. It can contents several certificates
		# If the CAS server certificate is auto-signed, the file must content the certificate
		# If the certificate is trusted by an Certificate Autority, The file must content
		#    certificate from high level CA
		trusted_ca /etc/pki/ca_cert.pem
		-------------------------------------------
		

		check whether pam_cas works like it should by running $PAM_CAS_SOURCES/castest <service> <ticket> <configFile>:
		$ ./castest imap://localhost PT-... /etc/security/pam_cas.conf


		$ vi /etc/pam.d/imap
		-------------/etc/pam.d/imap---------------
		#
		# The PAM configuration file for the UW IMAPd `imap' service
		#

		# The standard Unix authentication modules, used with
		# NIS (man nsswitch) as well as normal /etc/passwd and
		# /etc/shadow entries.
		#@include common-auth
		#@include common-account
		#@include common-session

		#pam_cas only responds when it notices a ticket-shaped password, so always put it first
		auth sufficient /lib/security/pam_cas.so -simap://localhost -f/etc/security/pam_cas.conf
		-------------------------------------------
		
		configure imap to use saslauth
		$vi /etc/imapd.conf
		--------- /etc/imapd.conf ---------
		
		sasl_pwcheck_method: saslauthd
		sasl_mech_list: PLAIN

		
		Start saslauth with pam option % saslauthd -a pam or modify the saslauthd configuration file to use pam

		see /var/log/auth.log for pam_cas logging (syslog)

        1-2- note in the configuration /etc/pam.d/imap: a step after recovering a valid ticket by pam_cas.so is to use pam.mysql to lookup the user name in the database or else the system will
	     use pam_unix instead.

2- Casifiying the Web mail (proxy service) in our case roundcubemail: 
	2-1- download to roundcubemail/plugins the latest version of rcera_cas_authentication  from gitlab with PHPCAS client  version = 1.3.1
        2-2- $ vi /rcera_cas_authentication/config.inc.php
		    enable or disable proxy : $rcmail_config['cas_proxy'] = 'true'/ 'false';
	      $rcmail_config['cas_cert'] = '/path/to/cert/file' to add the certificate of CAS server when self signed 'this option does not work' or the CA certifiactes;
	2-3- add the rcera_cas_authentication plugin to roundcube mail by modifiying the file main.inc.php in the config directory: 
		-----/roundcubemail/config/main.inc.php-----------------			
		$rcmail_config['plugins'] = array('rcera_cas_authentication');
        2-4- important: for mode proxy to work add also $rcmail_config['default_host'] to main.inc.php which must be the same as $rcmail_config['cas_imap_name'] 'in /rcera_cas_authentication/config.inc.php;
        2-5- if problems persist make sure that you are using the right certificates and solve errors seen in the syslog file. 


