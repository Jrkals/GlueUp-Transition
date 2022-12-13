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

* test contact import with all custom fields
* Is there a way to import contacts to chapters?
    * individually import at chapter level
* why did last contact import fail?
    * blank names
* run email validation and import
* import email unsubscribe
    * can't do with batch
* assistant email:
* mark sekaz phone: 571-289-5701
