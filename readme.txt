=== Sticky Tax ===
Contributors: liquidweb, stevegrunwell, norcross
Tags: sticky, taxonomies, sticky posts, categories, tags
Requires at least: 4.6
Tested up to: 4.8.1
Requires PHP: 5.3
Stable tag: 1.0.0
License: MIT
License URI: https://opensource.org/licenses/MIT

Make posts sticky within the context of a single category, tag, or custom taxonomy term.


== Description ==

Sticky Tax enables posts to be made "sticky" within a specific category or tag. Ideal for cornerstone content or important stories, Sticky Tax lets blogs maintain chronological post archives, while keeping the most important content at the top of the list.


=== Why might I want this? ===

Let's imagine Chris, a long-time blogger with lots of traffic coming to his site. He's worked hard to categorize all of his content, but there are a few posts that he wants to make sure are the first thing people see when they hit a category landing page.

Using WordPress' default behavior, these important pieces of content will eventually get lost behind pagination, just because they're not the newest posts in the category.

With Sticky Tax, Chris can highlight the most important posts in a category, whether they were written last week or last year!


=== Usage ===

After installing and activating Sticky Tax, a new "Promote Post" meta box will appear on the post edit screen, with a list of terms for any public taxonomies registered on your site.

![The Sticky Tax meta box on a WordPress post edit screen, showing a list of categories](plugin_assets/screenshot-1.jpg)


==== On the front-end ====

archive. Each sticky post even gets a `.sticky-tax` class added to it, enabling you to apply custom styling for your sticky content!

![A post, made sticky via Sticky Tax, styled separately and at the top of the category archive page](plugin_assets/screenshot-2.jpg)


== Installation ==

1. Upload the `sticky-tax` directory into `wp-content/plugins/`
2. Activate the plugin through the "Plugins" menu in WordPress


== Frequently Asked Questions ==

= Can I use Sticky Tax for other post types and/or taxonomies =

Absolutely! Sticky Tax includes a number of filters that make customizing Sticky Tax a snap; for full details, [please visit the plugin's README on GitHub](https://github.com/liquidweb/sticky-tax/tree/build-steps#advanced-usage).


= Can I style sticky posts separately from regular, non-sticky posts? =

When a post is appearing at the front an archive thanks to Sticky Tax, the `sticky-tax` class will be applied to the post via [the `post_class` filter](https://developer.wordpress.org/reference/hooks/post_class/). As long as your theme is using the `<?php post_class(); ?>` function in the loop, you should be able to target sticky posts in your CSS using the `.sticky-tax` selector.


== Screenshots ==

1. The Sticky Tax meta box on a WordPress post edit screen, showing a list of categories.
2. A post, made sticky via Sticky Tax, styled separately and at the top of the category archive page.


== Changelog ==

For a full list of changes, please [see the Sticky Tax change log on GitHub](https://github.com/liquidweb/sticky-tax/blob/develop/CHANGELOG.md).

= 1.0.0 =
* Initial public release.
