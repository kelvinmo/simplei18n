⚠️ **IMPORTANT NOTICE - This library is no longer being maintained.  Please use the [PHP Gettext library](https://github.com/php-gettext/Gettext) as an alternative.** ⚠️

---

# SimpleI18N

SimpleI18N is a simple internationalization library written in PHP.

SimpleI18N uses Gettext mo files to store translated strings.  However, it
only offers a subset of the full capabilities of the Gettext library.

In particular:

- categories are not supported
- .mo files are assumed to be encoded in the same character set as your
  application - if you are not using UTF-8 you will need to convert the
  character set manually

## License

BSD 3 clause