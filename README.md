# Installation

First install the composer plugin in your project (or globally)

```bash
composer require --dev tofandel/wpml-updater
```

Then create an `auth.json` file in which you will add your WPML credentials 
```json
{
  "get-parameter": {
    "wpml.org": {
      "user_id": "",
      "subscription_key": ""
    }
  }
}
```
You can retrieve those credentials by going to https://wpml.org/account/downloads/ 
and copying the url of any of the plugin's download button and then picking the correct query parameters

Finally you can now install/upgrade any WPML plugin by running

```composer require wpml/sitepress-multilingual-cms```

They will also get upgraded during `composer upgrade` if you do not change the version constraint

> [!IMPORTANT]
> We do not support rolling back to arbitrary versions, only the latest version is available by default because of the way we retrieve the repository information, so if you want to rollback to a specific version you will need to do so editing your composer.lock manually with the desired version and then running `composer install`


### List of available packages

- wpml/sitepress-multilingual-cms
- wpml/wpml-string-translation
- wpml/wpml-translation-management
- wpml/gravityforms-multilingual
- wpml/contact-form-7-multilingual
- wpml/wpml-ninja-forms
- wpml/wpml-wpforms
- wpml/acfml
- wpml/wpml-all-import
- wpml/wpml-mailchimp-for-wp
- wpml/wpml-media-translation
- wpml/wp-seo-multilingual
- wpml/wpml-graphql
- wpml/wpml-elasticpress
- wpml/wpml-import
- wpml/wpml-sticky-links
- wpml/wpml-cms-nav

We do not provide the plugins available for free on wordpress.org because they are better served by wpackagist.org
