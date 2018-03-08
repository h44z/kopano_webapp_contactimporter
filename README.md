CONTACT IMPORTER AND EXPORTER:
===

## Building the contact importer plugin from source:

### Dependencies
 - Kopano WebApp Source Code (https://stash.kopano.io/projects/KW/repos/kopano-webapp/browse)
 - PHP >= 5 (7 or higher recommended)
 - composer (https://getcomposer.org/)
 - JDK 1.8 (http://www.oracle.com/technetwork/java/javase/downloads/jdk8-downloads-2133151.html)
 - ant (http://ant.apache.org/)

Add JAVA_HOME (e.g. C:\Program Files\Java\jdk1.8.0_161) to your path. Also add Ant, Composer, PHP and Java to the global PATH variable!

### Compiling the plugin
Unzip (or use git clone) the sourcecode of Kopano WebApp to a new directory. In this README we call the source folder of WebApp "kopano-webapp-source".

Then generate the WebApp build utils:
```
cd kopano-webapp-source
ant tools
```

Next clone the plugin to the WebApp plugin directory:
```
cd kopano-webapp-source\plugins
git clone https://git.sprinternet.at/zarafa_webapp/contactimporter.git
```

Now lets build the plugin:
```
cd kopano-webapp-source\plugins\contactimporter\php
composer install
cd kopano-webapp-source\plugins\contactimporter
ant deploy
```

The compiled plugin is saved to `kopano-webapp-source\deploy\plugins\contactimporter`.

## Installing the plugin

### From compiled source
Copy the whole folder "contactimporter" to your production WebApp (`kopano-webapp-production\plugins\contactimporter`)

For example:
```
cp -r kopano-webapp-source\deploy\plugins\contactimporter kopano-webapp-production\plugins\
```

### From precompiled download
Download the newest release from https://git.sprinternet.at/zarafa_webapp/contactimporter/tree/master/DIST.

Unzip the downloaded file and copy the plugin folder to your production WebApp.

For example:
```
cp -r Downloads\contactimporter kopano-webapp-production\plugins\
```

## Configuration
Edit the config.php file in the plugin root path to fit your needs.

Available configuration values:

| Configuration Value        | Type           | Default  | Desctription |
| ------------- |:-------------:| ----- | ----- |
| PLUGIN_CONTACTIMPORTER_USER_DEFAULT_ENABLE     | boolean | false | Set to true to enable the plugin for all users |
| PLUGIN_CONTACTIMPORTER_DEFAULT     | string | "Kontakte" | Default contact folder name (might be "Contacts" on english installations) |
| PLUGIN_CONTACTIMPORTER_TMP_UPLOAD     | string | "/var/lib/kopano-webapp/tmp/" | Temporary path to store uploaded v-Cards |


## Usage

The plugin add context menu entries to contact folders.

![Plugin Context Menus](https://git.sprinternet.at/zarafa_webapp/contactimporter/raw/master/usage.png "Kopano Webapp Context Menu")