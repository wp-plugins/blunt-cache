=== Blunt Cache ===
Contributors: Hube2
Tags: cache, caching, performance, object, fragment, transient, persistent
Requires at least: 3.5
Tested up to: 4.0
Stable tag: 0.0.2
Donate link:  https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=hube02%40earthlink%2enet&lc=US&item_name=Donate%20to%20Blunt%20Cache%20WordPress%20Plugin&no_note=0&cn=Add%20special%20instructions%20to%20the%20seller%3a&no_shipping=1&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Simple Fragment and Object Caching using WP Transients API

== Description ==

Blunt Cache is a persistent fragment and object chache for those of us that cannot use full page caching.

**This plugin is meant for developers and requires code changes to your theme *(and/or plugins)*. Please be sure to read the [Documentation](http://wordpress.org/plugins/blunt-cache/other_notes/).**

= Fragment Caching =

Capture and cache the HTML output of any section of code. Useful for storing HTML that is expensive to generate while leaving portions of the page that do not take much time or contain dynamic portions alone.

= Object Caching =

Capture and cache any object. Run a WP_query and cache the results. Store any variable that is time consuming to generate.

Most object caching scripts I've seen that override [WP_Object_Cache](http://codex.wordpress.org/Class_Reference/WP_Object_Cache) are all or nothing, or require you to define what not to cache, I think. Seriously, I just find them a PITA to use. I don't want to do complex configurations or coding to do something that should be really simple. This plugin will let you pick and choose what to cache persistantly without the hassle. *Although this means that we can't cache the main query, so it has its downside.*

= WP Transients API =

Uses the [WP Transients API](http://codex.wordpress.org/Transients_API) to store cached objects and html fragments. This means that the cache data is stored in the options table in the DB and does require some queries. The small number of simple DB qureies used during the caching process should take less time.

= Uses Filters and Actions =

You use the cache by using [apply_filters](http://codex.wordpress.org/Function_Reference/apply_filters) and [do_action](http://codex.wordpress.org/Function_Reference/do_action) functions instead of calling functions of the plugin or instantiating a new object for every fragment and object to be cached. This means that you do not need to worry about checking to see that functions exist before you can use them. It also means that you can deactivate the plugin without worrying about your site breaking if you do. Need to do some work on the site and test, don't want the cache to work while your doing it, just deactivate it, no files to remove.

= Set Expirations =

You can set the default experation time and the experation of individual fragments and object. Set the experation time for 1 second to... well... whatever floats your boat.

= Unique Keys =

You supply the unique key names for storing fragments and objects. Share the same fragments and objects in a single request or across mulitple requests, a single template file or multiple template files.

= Clearing the Cache = 

Clear the entire cache at any time by adding ?blunt-query=clear to any url on your site.

Clear individual fragments or objects from the cache.

I have not added any mechanism to detect when items are updated or need to be cleared. I assume that you'll know when you need to clear the cache or that you'll write code that can use the action to clear individual fragments or objects when this needs to be accomplished. We'll see how much use this gets. If there's a lot of people using it then I'll consider figuring out how to add something.

= Cleans Up After Itself =

Clearing the cache or deactivating this plugin will remove all transient data that it has created so you don't need to worry about crap building up in you DB.

= Works all by itself (almost) =

No need to install any other caching plugin to make it work, but it does require adding code to your templates and/or plugins. This is not much different than the transient api.

= Visit GitHub =

Visit the [GitHub](https://github.com/Hube2/blunt-cache) repo for this plugin.

= Add to Themes and Plugins =

Safe to add to themes and plugins, does its own checking to see if another instance of Blunt Cache is already running.

== Installation ==

**As a Plugin**

1. Upload the Blunt Cache plugin to the plugin folder of your site
2. Activate it from the Plugins Page

**Include within your theme or plugin**

1. Copy the Blunt Cache folder to your theme or plugin folder
2. Add the following code to your theme or plugin
`include(dirname(__FILE__).'/blunt-cache/blunt-cache.php');`

**[Read the Documentation](http://wordpress.org/plugins/blunt-cache/other_notes/)**

== Screenshots ==

There are no screenshots. This is purely for development and there is no user interface. See [Other Notes](http://wordpress.org/plugins/blunt-cache/other_notes/) for documentation.

== Other Notes ==


= Documentation =


***This plugin does not work on multsite installations.***

***This plugin is not for those that are already using another caching system or plugin.***

***This plugin is for developers that need or want to decide what is cached when and for how long.***

**Standard Variables**
The following variables are used throughout this documentation:

* **$key** = A unique key value for the fragment or object to be cached.
* **$ttl** = Time to Live or Experation Time. The time in seconds that a fragment or object should be stored before it expires. $ttl is an optional parameter in all of the code below. If you do not specify $ttl then the default $ttl value will be used.
* **$type** = The type of object. Valid values are "Fragment" and "Object".
* **$object** = An object to store in the object cache.

**A Note About Unique Keys**

You can pass any string value as a key for your fragment of object. The actual key used to store your object will be an MD5 hash generated from your key value. This ensures that the key is both the correct length and that it is safe to use for a key value.

Some Examples of Unique Keys to Use:

* A key for a fragment or object generated in a specific file: `$key = __FILE__;`
* The 3rd key generated in a specific file: `$key = __FILE__.'-3';`
* A key for a fragment or object generated for a specific URL: `$key = $_SERVER['REQUEST_URI'];`
* The 4th key generated for a specific URL: $key = `$_SERVER['REQUEST_URI'].'-4';`
* A key for a fragment or object generated in a specific file for a specific URL: `$key = __FILE__.$_SERVER['REQUEST_URI'];`
* The 2nd key generated in a specific file for a specific URL: `$key = __FILE__.$_SERVER['REQUEST_URI'].'-2';`

**Set Default $ttl**

The built in default $ttl value is 3600 (1 hour). You can set the default $ttl value to whatever you'd like by including the following code in your function.php file, plugin, or whatever. 

You should adjust this up depending on how much trafic you recieve on the site and how often you make changes. Low traffic sites or sites that are updated infrequently should have a longer time that the cache is valid for.

`
function set_blunt_cache_ttl($ttl) {
  $ttl = 60 * 60 * 6; // 6 hours
  return $ttl;
}
add_filter('blunt_cache_ttl', 'set_blunt_cache_ttl');
`

**Fragment Cache**

To store a fragment in the fragment cache:

`
$key = 'My Unique Key';
if (!apply_filters('blunt_cache_frag_check', false, $key)) {
  
  ////////////////////////////////////////////
  //  This HTML Output of this code block   //
  //  will be stored in the fragment cache  //
  ////////////////////////////////////////////
  
}
do_action('blunt_cache_frag_output_save', $key, $ttl);
`

**Object Cache**

To store an object in the object cache:

`
$key = 'My Unique Key';
if (($object = apply_filters('blunt_cache_get_object', false, $key)) === false) {
  
  $object = get_object();
  
  ////////////////////////////////////////////////
  //  Whatever $object is set to in this code   //
  //  block will be stored in the object cache  //
  ////////////////////////////////////////////////
  
  do_action('blunt_cache_object_save', $object, $key, $ttl);
}
`

**Clearing Cache Values**

To remove a single item from the item cache

`
$type = 'fragment'; // or object
$key = 'My Unique Key';
do_action('blunt_cache_uncache', $type, $key);
`

To clear the entire cache

`
http://www.yoursite.com/?blunt-cache=clear
`

== Frequently Asked Questions == 

= Another Caching Plugin? =
I needed:

* Something that was easy to use without complex coding
* Something that I could add quickly to existing sites
* Something that I can quickly add in on new sites
* Something that would not require any work other than deactivation to remove
* Something that was a persistent fragment and object cache

Add all that up and find something, I couldn't. Even if you can find most of it the fact that most caching plugins try to be all things to all people make them extremely complicated to use for fragments and objects.

= Isn't Object Caching in this plugin just a wrapper for the Transients API? =

Yes, it is. But I was building a plugin for caching fragments using hooks and filters and I decided that I would include object caching as well. Besides the Transients API does not give you any easy way to delete all of your transients. This plugin keeps track of what has been cached and allows you to clear that cache easily, or turn it off. If you use the transient functions then they are always on, good luck debuging or seeing changes to your site before they expire naturally unless you plan to go through the added effort of adding actions to all of the admin save and update hooks.

== Changelog ==

= 0.0.2 =
* Added check to plugin php file to ensure file is not accessed directly

= 0.0.1 =
* initial release of public code
