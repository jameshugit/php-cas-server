.PHONY: all clean doc lang publish pubpilou pubmb

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

fixperms:
	ssh root@cas-erasme.erasme.lan "chown -R www-data:www-data /var/www/cas/"

