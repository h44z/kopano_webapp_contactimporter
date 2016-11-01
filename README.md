CONTACT IMPORTER AND EXPORTER:
===

Building the contact importer plugin for Zarafa WebApp:

```
cd /zarafa/webapp/
ant tools
cd /zarafa/webapp/plugins/
git clone https://git.sprinternet.at/zarafa_webapp/contactimporter.git
cd contactimporter
ant deploy
```

Make sure to run "composer install" in the php directory!

### Usage
Rightclick a contactfolder or contact entry to export it as vCard.

Rightclick a contactfolder to import a vCard.

Rightclick a vCard attachment to import it.
