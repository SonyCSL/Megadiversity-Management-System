# Megadiversity-Management-System (MMS)

## About

The MMS is an open-source project that aims to construct the Content Management System (CMS) coupled with Internet of Things (IoT) extensions. It comprises basic framework and assets to construct your own CMS-IoT. The assets include original PHP framework “Artichoke” that can be used to build the CMS as a server-side web application.

The MMS was developed in view of managing diverse complex systems in real world with the following cycle:

1. Build the CMS to store relevant big data of a complex phenomenon.
2. Connect with IoT sensors/actuators for the real-time management of the complex system.
3. Construct machine learning/artificial intelligence with CMS-IoT system during active interaction of the management. This recursive process to construct actively adapting management model is termed as “Open Systems Science” in Sony CSL.
4. Contribute to extend the MMS with your code in order to be used and refined by other multidisciplinary stakeholders under open source initiative.

For example, our synecoculture project tackles the management of the complex vegetation for market gardening with the use of MMS, in order to maximize ecological synergy and yield: [Project description] https://www.sonycsl.co.jp/tokyo/407/ [Article of CMS-IoT] https://hal.inria.fr/hal-01291125/document

If your project has the following property in the real world system, then MMS could be an ICT solution:

1. You know the general principle to solve the problem, but the real-world management is difficult because the information required is too diverse and massive to be treated by human alone.
2. Your system is difficult to manage with modelling-based approach, because there is interventions from external environment that frequently change the premise of the model. This is a common situation of the complex system management in open field, and MMS can adopt statistical models based on the machine learning of big data.
3. You do not need sophisticated model but rather want to widen your choice of management based on the past record and relevant databases with assistive technology.
4. You have an open-source software with analytical modules and you want to connect it with CMS-IoT for real-time big data analysis and management.
5. You are searching for an interactive interface for data acquisition in citizen science with the use of smartphone and/or AR (Augumented Reality) device.

## Quick start

1. Install PHP-CGI environment on your server. (e.g. Apache or nginx, we recommend using nginx)
2. Clone this repository to your server's root directory.
3. `$ cd /your/webroot/path` : Change working directory to cloned root path.
4. `$ composer install` : Install required components.
5. Access to your website (like "localhost" if your server is a desktop computer) on a web browser. If you can see "Welcome to Artichoke", then the assets of MMS were installed successfully. 
6. Follow instructions on welcome page to create your new application.

### Advice

#### Routing

You need to configure the cgi-server so that all of the HTTP requests will be redirected to index.php. This is because the controller in Artichoke is handled by the request parameters instead of cgi-server (Apache / nginx).
Artichoke also supports http/2 connection.

See example of nginx.conf below:

```
server {
    listen 80;
    server_name artichoke.yourhost.com;
    root /var/www/cgi;
    index index.php;
    charset utf-8;

    # redirect
    location / {
        try_files $uri $uri/ /index.php;
    }
    location ^~ /artichoke/ { deny all; }

    # for php-fpm
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php-fpm/php-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

## Dependency

- PHP7 (not working on PHP5 and below)
- Composer
- mongodb/mongodb in Packagist

## LICENCE

The source files written in PHP (\*.php) are available under the terms of Gnu Affelo General Public License version 3. (except for unit testing files).
The directory structure of the source files are as follows.

## Framework structure

```
index.php
tests/ : Unit testing files
artichoke/
    framework/ : Framework basic class folder
        core/ : Core class folder (utility, static class)
            Generator.php : Template engine (page generator)            
            Dispatcher.php : Request parser
            Configurator.php : Environment and database configuration
            Ajax.php : Ajax script generate function
            Requests.php : HTTP request function
            Server.php : Server function
            Session.php : Session handling function
        abstracts/ : MVC abstract class folder
            AsyncBase.php : Ajax host controller abstract class
            AnalyticsBase.php : Analytical controller abstract class
            ControllerBase.php : Controller abstract class
            MariadbBase.php : MariaDB|MySQL abstract class (extended by models/entry/ )
            MongodbBase.php : MongoDB abstract class (extended by models/client/ )
        controllers/ : Basic controller class folder
            ApiController.php : API controller
            ApitesterController.php : API tester controller on the web
            ExceptionController.php : Exception controller
            FileController.php : File controller for MongoGridFS
            TmpfileController.php : File controller for temporary directory
            PhpinfoController.php : Controller for phpinfo()
            IndexController.php : Default controller
            LoginController.php : Log in controller
            LogoutController.php : Log out controller
        util/ : Utility classes
            AccessControllerApi.php : Accessor for Controller class
            ApiResult.php : Generate the return value for ApiController
            GetNameSpace.php : Generate NameSpaces
            GetPaths.php : Generate file paths
            LoadResource.php : File loader
            SearchFile.php : Get file path from file name
        models/ : Basic model class folder
            application/
                AppbuilderModel.php : Automated application deployer
                ~.skeleton,~.sql,~.png : Application default templates
                base.sql : Basic scheme for MariaDB
            entry/
                FileModel.php : File(MongoGridFS)-DB model
                ImageModel.php : Image processing model
            client/
                Album.php : Model for album (Content group on CMS)
                Bookshelf.php : Model for album list
                Device.php : Model for client device management
                User.php : Model for user management
        views/ : Basic view class folder
            template/
                index.html : Default index page template (Welcome Artichoke)
                exception.html : Default exception page template
                _navigation.html : Common HTML navigation parts (HTML header)
                _copyright.html : Common HTML copyright parts (HTML footer)
            css/
                artichoke.css : Default design stylesheet
        resources/
            noimage.png : Alternative image for image contents
            favicon.png : Default favicon
``` 
