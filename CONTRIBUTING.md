# Contributing

Contributions are **welcome** and will be fully **credited**.

Accept contributions via Pull Requests on [Github](https://github.com/docta/mercadolibre).


## Pull Requests

- **[PSR-2 Coding Standard](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md)** - The easiest way to apply the conventions is to install [PHP Code Sniffer](http://pear.php.net/package/PHP_CodeSniffer).

- **Ensure no coding standards violations** - Please run PHP Code Sniffer using the PSR-2 standard (see below) before submitting your pull request. A violation will cause the build to fail, so please make sure there are no violations. Don't accept a patch if the build fails.

- **Add tests** - Your patch won't be accepted if it doesn't have tests.

- **Ensure tests pass** - Please run the tests (see below) before submitting your pull request, and make sure they pass. Don't accept until all tests pass.

- **Consider the launch cycle** - Follow [SemVer](https://semver.org/). Randomly breaking public APIs is not an option.

- **Create topic branches** - Don't ask to get out of your main branch.

- **One pull request per feature** - If you want to do more than one thing, send multiple pull requests.

- **Send coherent history** - Make sure each individual commit in your pull request is meaningful. If you had to make multiple intermediate commits while developing, please squash them before submitting.

- **Document any change in behaviour** - make sure all relevant documentation is kept up-to-date.

## Testing

The following tests must pass for a build to be considered successful. If contributing, please ensure these pass before submitting a pull request.

``` bash
$ composer all
```

**Happy coding**!
