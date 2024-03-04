# (0) Welcome To PHP Framework PRO

This is the documentation for a high-level overview of how modern PHP frameworks handle web requests.  

These are my notes and observations as I learn the ins and outs of modern PHP frameworks.

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

*  "Invo\\": "src/"
*  "App\\": "src/app/",
*  "JimSos\\Framework\\": "framework/"

** Warning \\\\ double backslash in the namespace are not showing in above expression. **

Mapping namespaces to file folders.

For example, you may have a set of classes which describe an HTML table, such as Table, Row and Cell while also having another set of classes to describe furniture, such as Table, Chair and Bed. Namespaces can be used to organize the classes into two different groups while also preventing the two classes Table and Table from being mixed up.

# (3) Application Structure

Now that the basic setup is complete, the Application class is used to initialize and run a Phalcon application. It takes in the dependency injection container ($di) as input. 

The purpose of this code is to create a new Application instance and call its handle() and send() methods to process the request and send the response back to the client.

The handle() method takes the $_SERVER['REQUEST_URI'] as input, which contains the request URL. It will route this request to the appropriate controller/action and generate the response.

The send() method outputs the response content to the client. 

To summarize the logic:

* Create the Application instance with the DI container
* Call handle() and pass in the request URI
* This will route the request and execute the controller/action
* The response is generated
* Call send() to output the response to the client

So in simple terms, this code initializes the Phalcon application, handles the incoming request, executes the required controllers/actions, generates the response and returns it back to the caller. The Application class brings together all the components needed to accept the request, route it, execute it, create the response and send it back.

For this framework/app example case, I will build the Application class from scratch to mimic the structure of a real-world application.

# (4) STRICT TYPES

What is declare(strict_types=1)?
The declare(strict_types=1) is a directive in PHP that enforces strict type checking for function and method arguments and return values within a specific PHP file or code block. When you enable strict type checking, PHP ensures that the data types of function arguments and return values match exactly with the declared types. Exactly the same way as Java does.

Place this declaration at the very beginning of your PHP file or code block (before any other code) to enable strict typing.

# (5) SUMMARY

Section 1-4 are the basic setup for a PHP framework, not the framework itself.

# (6) THE REQUEST -> RESPONSE CYCLE

## (6.1) Request Class

All PHP frameworks use objects to represent the incoming request. One of the greatest advantages of this is encapsulation: we can store all of the superglobal values as properties on our request object and those values will be preserved and protected from being tampered with unlike the superglobals which can have their values altered.

We will be able to use some of the properties on our request object in order to perform important operations such as routing the request to the correct handler (controller) etc.

The Request class which I create here is a superlight model based on the Symfony Http Foundation request class

**$request = \JimSos\Framework\Http\Request::createFromGlobals();**

This is an interface that can be swapped out for a more complex implementation without affecting the rest of the framework code.

## (6.2) Response Class

In the same way that we did with the request, let's also encapsulate the response data by creating a response class. There are 3 main pieces of data associated with a response and they are:

* Content
* Status (code)
* Headers

**$response = new Response(content: $content, status: 200, headers: []);**

The content will always be a string (or null) so we can send it by echoing it from a $response->send() method.

## (6.3) Http Kernel

We've looked at the Request class and the Response class so now let's consider a class which is responsible for taking that Request and returning a Response.

For this we are going to create a HTTP Kernel class which is the heart of your application. This class will be composed of the main components that we are going to need to complete the request -> response cycle.

**$response = $kernel->handle($request);**

**$response->send();**

# (7) Routing and Controllers

## (7.1) Routing Key Concepts: Dispatchers

The dispatcher is the component which is responsible for taking the incoming request and routing it to the correct controller/action.

(For our routing we are going to use a 3rd party package called FastRoute which uses regular expressions to match URI's to routes and their handlers.

You can find FastRoute here: https://github.com/nikic/FastRoute and we will install it using composer.)

Definition: what's exactly a dispatcher ?

The name can be a bit misleading and make it sound more complex than it is but it's an object or a method which determines which piece of code is going to handle the request. 

In-depth explanation 

1. Understanding Routing: In web development, routing refers to the process of directing an HTTP request to the correct piece of code, usually a function or a method in a class. Routes are typically defined in terms of URLs and HTTP methods (like GET, POST, etc.). For example, a route could be defined to handle all GET requests for "/home".

2. Role of a Route Dispatcher: The route dispatcher is responsible for taking an incoming HTTP request, analyzing its URL and method, and then determining which piece of code should handle the request based on the routes that have been defined.

3. Process of Dispatching:
   * Request Analysis: When a request arrives, the dispatcher examines the request's URL and HTTP method.
   * Matching Route: It then matches these against the set of defined routes. Each route specifies a URL pattern and often an HTTP method.
   * Executing the Handler: Once a match is found, the dispatcher executes the associated handler (function or class method), which generates the response to the request.
   * Handling No Match: If no matching route is found, the dispatcher can trigger a default action, like displaying a 404 error page.

4. Why 'Dispatcher'?: The term "dispatcher" might seem confusing, but it's analogous to a dispatcher in other fields (like emergency services) who directs calls to the right place. Here, the "calls" are HTTP requests, and the "right place" is the code that should handle them.

By understanding the dispatcher as a sort of 'traffic controller' for HTTP requests, you may find it easier to grasp its role in the routing process. It's not about sending out requests or data but about directing incoming requests to the right destination in the application.

## (7.2) Routing Key Concepts: File Structure for my example application

At first all the routes are defined in the kernel.php file.  Since we don't want to hard code the routes in the kernel file we are going to create a routes directory within of our application (app) folder.  There can be many types of routes, for now we are going to use a simple route file called web.php which will be used to define the routes for our web application.

**ramp/src/app/routes/web.php**

Setting up the correct namespaces is a bit trickier.

(Setting up the correct namespaces and directory structure for both my PHP Phalcon Contractor application, *Invo*, and my PHP Framework and example application, *App*, is dependant on my personal directory structure of this custom github project.  It does not follow any kind of particular framework directory structure, but my own custom directory structure.)


| directory  | namespace |
| --- | ---  |
| /framework | JimSos\\\\Framework\\\\ |
| /src/app   | App\\\\   |
| /src       | Invo\\\\  |



