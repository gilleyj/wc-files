=== WC Files ===
Contributors: gilleyj
Donate link: http://example.com/
Tags: files, uploading, share
Requires at least: 3.0.1
Tested up to: 4.2.2
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A small simple wordpress plugin to add a "file" post type with associated administrative panels and integration with wordpress via shortcodes.

== Description ==

A simple wordpress plugin to add a "File" post type that allows you to easily add and share files without using the media tool and editing a post.  Just add a short code to a page/post and you can display/list any files uploaded easily.


Example Shortcodes that can be used, but this is not an exhaustive list:

```
[wc-files]
```

```
[wc-files numberposts="200" orderby="title" order="ASC" category_name="test"]
```

```
[wc-files numberposts="200" orderby="title" order="ASC"]
```



== Installation ==

1. Upload `plugin-name.php` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress


== Screenshots ==

1. The new menu and file type screen.
2. Same as above but with data filled out.
3. The media picker in use.
4. An example post with the shortcode in use.

== Changelog ==

= 0.0.1 =
* Initial Release

