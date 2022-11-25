## YCP SilkStart to Glue Up Transition

### Commands

```php artisan silkstart:importContacts {dir} {--dry}```
Takes a directory of a set of CSV files with Silkstart exports in them and imports the contacts

```php artisan silkstart:importCompanies {dir} {--dry}```
Takes a directory of CSV files with SilkStart company exports in them and imports the companies and contacts
```php artisan glueup:exportMembers```
Exports a set of csvs by member type.
```php artisan glueup:exportCompanies```
In progress need to think of how to do inactive
```php artisan glueup:exportContacts```
Exports a list of contacts with all custom fields and information. These are exclusively non members

#### TODOS

* test company export exp. inactive
* test glue up inactive company import
* include custom fields for contacts
* add event data to person in export.

