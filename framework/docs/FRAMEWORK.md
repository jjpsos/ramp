# (0) Welcome To PHP Framework PRO

This is the documentation for a high-level overview of how modern PHP frameworks handle web requests.

If you want to access all of the code at once, you can clone the public repo here:

https://github.com/GaryClarke/php-framework-pro


# (1) The Front Controller

The Front Controller is a software design pattern which provides just a single entrypoint to a web application.

This pattern is used by all of the PHP frameworks that you can think of and provides many benefits, the main ones being:

* Centralized control
* System maintainability
* Configurability

Any installable tech or non-common PHP extensions, need to be added to the RAMP stack. 

For PHP's built-in server. Simply run this command and you're up and running:

**php -S localhost:8000 public/index.php**

Or use Nginx or Apache.

# (2) Autoloading

Before we start to create Http classes to represent request and response, let's dive into Composer in order to set up autoloading.

The folder structure will be important here because we are creating framework files and classes but also application files and classes (i.e. the kind of files that a framework user would create). I intend to keep these separate by having a src folder and a framework folder.

* Note - I also added the /vendor folder to the .gitignore file. 

Namespaces are used to keep the code organized and to avoid naming conflicts.

For example, you may have a set of classes which describe an HTML table, such as Table, Row and Cell while also having another set of classes to describe furniture, such as Table, Chair and Bed. Namespaces can be used to organize the classes into two different groups while also preventing the two classes Table and Table from being mixed up.