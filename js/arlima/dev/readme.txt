
# Arlima ==> 3.0
 - jquery-tmpl -> mustasch.js
 - nestedSortable -> knockout.js nested... (http://jsfiddle.net/rniemeyer/uhZEL/)
 - remove deprecated stuff
 - tmpl object/db object -> db object
 - css/markup -> less.js + less verbose class names and id's
 - arlima.js -> ....

# ui-objects.png

Maps different parts of the UI to js objects

# ui-objects-methods.png

Describes some of the functionality of each UI-object

# Coding style guide lines

- Each object/class has its own file
- All objects/classes uses the modular pattern
- jQuery instantiation is only made in an init-function
- Methods considered "private" has a name prefixed with "_"
- All variables/methods is named in camel case with first letter being in lower case
- All classes/object is named in camel case with first letter being in upper case
