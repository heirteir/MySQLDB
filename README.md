# MySQLDB -Simple - Easy - SQL Injection Safe Library
MySQLDB is a lightweight MySQL database management library that keeps your project SQL Injection safe while reducing your lines of code. Every input put into this library is escaped to make sure you SQL database stays safe. You don't have to remember to use `mysqli_real_escape_string();` anymore this library handles that for you.
## Setup
Include the MySQLDB.php into your project then your set!
```PHP
include_once('MySQLDB.php');
$database = new DB('localhost', 'root', 'password', 'heirteirDB');
```

## Examples
### Create a table
```PHP
    $db->create_table('heirteirtesting', array(
        'userid VARCHAR(16)',
        'username VARCHAR(24)',
        'passwordhash VARCHAR(72)',
        'email VARCHAR(80)'
    ));
```
### Insert a new row 
```PHP
$db->add_row('heirteirstring', array(
    'userid' => 'aa89734',
    'username' => 'Heirteir',
    'password' => 'kfdjsakfj.jkjfdksjfd.j32432',
    'email' => 'heirteir@github.com'
    ));
```
### Grab a row
`$db->get_row('heirteirstring', 'userid', 'aa89734');`
### Get all the rows in a table
`$db->get_all_rows('heirteirstring');`
## Conclusion
This is just a small amount of what you can do with MySQLDB I am actively working on this project and I am happy to hear feedback so let me know if you have any suggestions and thanks for using MYSQLDB.
