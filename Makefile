# $Id$

PEARCMD = $(shell which pear)
PKGXML = package.xml
PKGNAME = $(shell $(PEARCMD) info $(PKGXML) | grep ^Package | awk '{print $$2}')
PKGVER = $(shell $(PEARCMD) info $(PKGXML) | grep ^Version | awk '{print $$2}')
PACKAGE = $(PKGNAME)-$(PKGVER).tgz

.PHONY: validate tag forcetag clean

all: $(PACKAGE)

release: all tag

validate: $(PKGXML)
	$(PEARCMD) package-validate $<
    
pretty: validate
	XMLLINT_INDENT='    ' xmllint --format $(PKGXML) > temp.xml
	mv temp.xml $(PKGXML)
    
$(PACKAGE): $(PKGXML) validate
	$(PEARCMD) package $(PKGXML)

tag: $(PKGXML) $(PACKAGE)
	$(PEARCMD) cvstag $(PKGXML)
    
forcetag: $(PKGXML) $(PACKAGE)
	$(PEARCMD) cvstag -F $(PKGXML)
    
clean:
	rm -f *~ $(PACKAGE)