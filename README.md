## YCP SilkStart to Glue Up Transition

### Commands

#### Importing

```
php artisan silkstart:importContacts {dir} {--dry}
```

Takes a directory of a set of CSV files with Silkstart exports in them and imports the contacts

```
php artisan silkstart:importCompanies {dir} {--dry}
```

Takes a directory of CSV files with SilkStart company exports in them and imports the companies and contacts

```injectablephp
php artisan email:importValidation {file}
```

Imports a file of emails with their validation records done by quickemailverification.com

#### Exporting

```
php artisan glueup:exportMembers
```

Exports a set of csvs by member type.

```
php artisan glueup:exportCompanies
```

In progress need to think of how to do inactive

```
php artisan glueup:exportContacts
```

Exports a list of contacts with all custom fields and information. These are exclusively non members

#### TODOS

* Stripe special characters from names: email names. remove commans from names
* make chapter interest list valid.
* fix chapter but no home chapter on belong import
* no semicolons in spiritual assessment
* YCP Cleveland and Ycp - Cleveland issu
* Run final import
* Mark Sekaz phone: 571-289-5701
