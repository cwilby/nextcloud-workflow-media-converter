app_name=$(notdir $(CURDIR))
build_tools_directory=$(CURDIR)/build/tools
build_dir=$(CURDIR)/build/artifacts
source_build_directory=$(CURDIR)/build/artifacts/source
source_package_name=$(source_build_directory)/$(app_name)
appstore_build_directory=$(CURDIR)/build/artifacts/appstore
appstore_package_name=$(appstore_build_directory)/$(app_name)
npm=$(shell which npm 2> /dev/null)
composer=$(shell which composer 2> /dev/null)

sign_dir=$(build_dir)/sign
cert_dir=$(HOME)/.nextcloud/certificates


default: build

clean-dist:
	rm -rf node_modules/

install-deps: install-deps-js
	composer install

install-deps-nodev: install-deps-js
	composer install --no-dev

install-deps-js:
	npm ci

build: clean-dist install-deps build-js

release: clean-dist install-deps-nodev build-js

build-js: install-deps-js
	npm run build

build-js-dev: install-deps
	npm run dev

watch:
	npm run watch

test: test-unit

test-unit:
	mkdir -p build/
ifeq (, $(shell which phpunit 2> /dev/null))
	@echo "No phpunit command available, downloading a copy from the web"
	mkdir -p $(build_tools_directory)
	curl -sSL https://phar.phpunit.de/phpunit-9.5.phar -o $(build_tools_directory)/phpunit.phar
	php $(build_tools_directory)/phpunit.phar --coverage-html tests/coverage
else
	phpunit --coverage-html tests/coverage
endif


# Builds the source package for the app store, ignores php and js tests
.PHONY: appstore
appstore:
	rm -rf $(appstore_build_directory)
	mkdir -p $(appstore_build_directory)
	mkdir -p $(cert_dir)
	php ./bin/tools/file_from_env.php "app_private_key" "$(cert_dir)/$(app_name).key"
	php ./bin/tools/file_from_env.php "app_public_crt" "$(cert_dir)/$(app_name).crt"
	
	@if [ -f $(cert_dir)/$(app_name).key ]; then \
		echo "Signing app files…"; \
		php ../../occ integrity:sign-app \
			--privateKey=$(cert_dir)/$(app_name).key\
			--certificate=$(cert_dir)/$(app_name).crt\
			--path=$(appstore_sign_dir)/$(app_name); \
		echo "Signing app files ... done"; \
	fi
	tar cvzf $(appstore_package_name).tar.gz \
		--exclude-vcs \
		--exclude="../$(app_name)/build" \
		--exclude="../$(app_name)/tests" \
		--exclude="../$(app_name)/Makefile" \
		--exclude="../$(app_name)/*.log" \
		--exclude="../$(app_name)/phpunit*xml" \
		--exclude="../$(app_name)/composer.*" \
		--exclude="../$(app_name)/js/node_modules" \
		--exclude="../$(app_name)/node_modules" \
		--exclude="../$(app_name)/js/tests" \
		--exclude="../$(app_name)/js/test" \
		--exclude="../$(app_name)/js/*.log" \
		--exclude="../$(app_name)/js/package.json" \
		--exclude="../$(app_name)/js/bower.json" \
		--exclude="../$(app_name)/js/karma.*" \
		--exclude="../$(app_name)/js/protractor.*" \
		--exclude="../$(app_name)/package.json" \
		--exclude="../$(app_name)/bower.json" \
		--exclude="../$(app_name)/karma.*" \
		--exclude="../$(app_name)/protractor\.*" \
		--exclude="../$(app_name)/translationfiles" \
		--exclude="../$(app_name)/.*" \
		--exclude="../$(app_name)/js/.*" \
		../$(app_name)