FoOlSlide Improved
=========

__THIS IS AN UNOFFICIAL PROJECT__

FoOlSlide is a ridiculously elaborated comic reader meant for users to enjoy reading.</br> 
This is a significant improvement of the original FoOlSlide reader project. This updated version let you organize your comics into categories and tags. There are many new tools and views you can use to make a better exploration of your content.<br />
Note that this project improved the frontend as well, providing a mobile-friendly version of the reader. The admin panel theme was maninly inspired by the work of [chocolatkey's FoOlSlide2](https://github.com/chocolatkey/FoOlSlide2).


Installation
------------
1.  Copy everything in the archive in a public server folder
2.  Create a database (MySQL, MSSQL, MySQLi, SQLite...)
3.  Go to http://yourdomain.com/slidefolder/install
4.  Insert database info and admin account info
5.  Done

Unit tests
----------
- Controller unit tests are in `tests/controllers/` and follow the `*Test.php` suffix.
- Test bootstrap and lightweight CodeIgniter stubs are in `tests/bootstrap.php`.
- Run tests with `./scripts/run-tests.sh`.
- Run tests + Docker Compose E2E smoke checks with `./scripts/run-tests.sh --with-e2e`.
- The script falls back in this order: host `phpunit`, `vendor/bin/phpunit`, Docker (`docker compose run --rm web ...`).
