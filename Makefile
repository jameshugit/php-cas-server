.PHONY: all clean doc fixmerms lang mbtest publish 

ARCH:=$(shell uname)

ifeq ($(ARCH),Darwin)
	DOXYGEN=/Applications/Doxygen.app/Contents/Resources/doxygen
else
	DOXYGEN=doxygen
endif

all: clean doc lang publish

clean:	
	rm -rf doc/generated/html
	rm -rf doc/generated/html

doc:
	@echo $(ARCH)
	${DOXYGEN} Doxyfile

lang:
	@echo -n Builfing translations...
	@for i in locale/*/LC_MESSAGES; do echo -n $$i | sed -e 's/.*\/\(.*\)\/.*/\1/'; echo -n "..."; msgfmt -o $$i/traductions.mo $$i/traductions.po; done
	@echo "done"

publish: send fixperms

send:
	rsync -avz . root@cas-erasme.erasme.lan:/var/www/cas/ --exclude .git --exclude .gitignore --exclude doc/

mbtest: ticket-test fixperms

ticket-test:
	rsync -avz ./lib/ticket.php  root@cas-erasme.erasme.lan:/var/www/cas/lib/
	rsync -avz ./tests/test-ticket.php   root@cas-erasme.erasme.lan:/var/www/cas/tests/

fixperms:
	ssh root@cas-erasme.erasme.lan "chown -R www-data:www-data /var/www/cas/"

trans: updatetrans
	msgfmt locale/fr.po -o locale/fr.mo
	msgfmt locale/en.po -o locale/en.mo

newtrans:
	xgettext *.php lib/*.php -o locale/en.pot
	cd locale && rm -f fr.po && msginit -l fr_FR 

updatetrans:
	xgettext *.php lib/*.php -o locale/en.pot
	msgmerge locale/fr.po locale/en.pot > locale/fr.po
