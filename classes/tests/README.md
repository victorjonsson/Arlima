# PHPUnit tests for Arlima

Running the test suite for Arlima requires PHP version >= 5.3.2. Some of these tests will write to
the database so you **should not run** them on production servers. None of the tests requires that the
Arlima plugin is installed.

1. Install composer (http://getcomposer.org/)

2. Navigate to the plugin directory of Arlima (../wp-content/plugins/arlima)

3. Install the dev dependencies `$ composer install --dev`

4. Now you can run the tests!

*This test suite is a work in progress. We're currently having a long way to go before having a
descent code coverage*