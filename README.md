# More Menu Fields

> Package to add more fields to WordPress menu edit screen.

---

## What / Why

WordPress provides a nice UI for editing navigation menus.

However, it is quite opinionated on the available settings for each menu item. They are:

- "Navigation Label"
- "Title Attribute"
- "Open link in a new tab"
- "CSS Classes"
- "Link Relationship (XFN)"
- "Description"

One might think that these settings are more than enough, but often for our clients we needed additional fields,
e.g. "data" attributes (useful for click tracking) or "rel" attributes ("noopener", "nofollow"...).

The issue is tha there's **no** filter provided by WordPress to edit provided fields and there's no action hook to allow 
echoing custom from fields HTML, like happens in many other parts of WP backend.

The only possible customization is the [`"wp_edit_nav_menu_walker"`](https://developer.wordpress.org/reference/hooks/wp_edit_nav_menu_walker/)
filter hook which allows to return the class name of a custom walker.

To write a custom walker that outputs custom form fields each time a field is needed is annoying, but not a big deal.

But things get worse when that filter is hooked from more than one plugin.

Because `"wp_edit_nav_menu_walker"` expects a walker *class name* it is only possible to completely overrides the class 
name, so if two or more plugins use that filter, only one of them will get its walker class used, the others will do nothing.

This package exists because we needed a way to add more fields that could work if used from more *unrelated* places.

---

## Usage


### First step

Because the use target of "More Menu Fields" is to be used from plugins, it is not a plugin itself, but a *package*
that can be required by plugins via Composer.

When the package is required via Composer and Composer autoload has been loaded, it is needed to *bootstrap* the package.

It can be done inside a plugin by just calling a function:

```php
Inpsyde\MoreMenuFields\bootstrap();
```

There's no need to wrap the call in any hook and if called more than once (by different plugins) nothing bad will happen.


### The field interfaces

To add more fields it is necessary to create a PHP class for each of them. The class has to implement the interface
`Inpsyde\MoreMenuFields\EditField` which looks like this:

```php
interface EditField {

	public function name(): string;

	public function field_markup(): string;
}
```

The first method, `name()`, has to return the field name. It is important this name is unique. This will also be used to
later retrieve the value that is entered via the field.

The second and last method, `field_markup()`, has to return the HTML markup for the field, as it will appear on the UI.

In the HTML markup will very likely be necessary to use the input name, id and current stored value, if any.
Those information can be obtained via an object of type `Inpsyde\MoreMenuFields\EditFieldValue`. More on this soon.

Very often (if not always) the value users enter in the generated input needs to be sanitized before being saved. 
This is why the package ships another interface `Inpsyde\MoreMenuFields\SanitizedEditField` which looks like this:

```php
interface SanitizedEditField extends EditField {

	public function sanitize_callback(): callable;
}
```

The interface extends `EditField` and its only method, `sanitize_callback`, can be used to return a callback used to 
sanitize the users input in the field. It is recommended to implement this interface to create fields and
only use `EditField` for form fields that don't actually take input, like buttons.


### Field class example

Nothing is better than an example to see how things work.

Below there's a real-world example of a class that will render a checkbox to add a "nofollow" attribute on a menu item link.

```php
namespace My\Plugin;

class NofollowField implements Inpsyde\MoreMenuFields\SanitizedEditField
{
	private $value;

	public function __construct( Inpsyde\MoreMenuFields\EditFieldValue $value )
	{
		$this->value = $value;
	}

	public function name(): string
	{
		return 'nofollow';
	}

	public function field_markup(): string
	{
		if ( ! $this->value->is_valid() ) {
			return '';
		}
		ob_start();
		?>
		<p class="<?= $this->value->form_field_class() ?>">
			<label>
				<?= esc_html__( 'Enable nofollow?', 'my-plugin' ) ?>
				<input
					type="checkbox"
					name="<?= $this->value->form_field_name() ?>"
					id="<?= $this->value->form_field_id() ?>"
					value="1"<?php checked( 1, $this->value->value() ) ?>/>
			</label>
		</p>
		<?php

		return ob_get_clean();
	}

	public function sanitize_callback(): callable {
		return 'intval';
	}
}
```

Quite easy. Even because many of the "hard work" used in the generation of field HTML is done by the instance of 
`EditFieldValue` that the field class receives in constructor. But where does it come from?


### Adding a field

Just having the field class above will do nothing if the package does not know about it.

It can be done via the filter hook stored in the constant `Inpsyde\MoreMenuFields\FILTER_FIELDS`.

That filters passes to hooking callbacks the array of currently added filters, and an instance of `EditFieldValueFactory`:
an object that can be used to obtain instances of `EditFieldValue` to be used in field classes.

Let's see an usage example:

```php
add_filter(
	Inpsyde\MoreMenuFields\FILTER_FIELDS,
	function ( array $items, EditMenuFieldValueFactory $value_factory )
	{
		$items[] = new My\Plugin\NofollowField( $value_factory->create( 'nofollow' ) );

		return $items;
	},
	10,
	2
);
```

When hooking `Inpsyde\MoreMenuFields\FILTER_FIELDS` the passed `EditMenuFieldValueFactory` is used to obtain an
instance of `EditFieldValue` that is injected in the field object (nothing more than what shown above).

For that we call the `create()` method on the factory (it is its only method) passing the name of the field, that
must be the exact same name returned by field object `name()` method.

That's it. The filter right above, plus the class in previous section is really all it takes to print the field
and save it.

The benefit of this can be seen when there are add many fields. Moreover, the `Inpsyde\MoreMenuFields\FILTER_FIELDS` 
filter can be used by many plugins that know nothing about each other and all will work just fine.


### Retrieving saved value

At some point there will be the need to use the value stored by the added fields.

The package stores them as post meta of the related menu item post. So to retrieve them it is possible to just use
`get_post_meta()`.
The only thing needed for it is to know the meta key which is obtained prepending what the field object 
`name()` method returns with a fixed prefix stored in the `EditFieldValue::KEY_PREFIX` class constant:

```php
$no_follow = get_post_meta( $menu_item_id, Inpsyde\MoreMenuFields\EditFieldValue::KEY_PREFIX . 'nofollow', TRUE );
```

Considering this is quite verbose, the package provides a shorter way to do the exact same thing:

```php
$no_follow = Inpsyde\MoreMenuFields\field_value( $menu_item, 'nofollow' );
```

For example, to make actual use of the "nofollow" field from above it is possible to do:

```php
add_filter( 'nav_menu_link_attributes', function ( array $attributes, $item )
{
	$current_rel = $attributes[ 'rel' ] ?? '';

	if ( Inpsyde\MoreMenuFields\field_value( $item, 'nofollow' ) ) {
		$attributes[ 'rel' ] = trim( $current_rel .= ' nofollow' );
	}
	
    return $attributes;
}
```

Where we make use of [`'nav_menu_link_attributes'`](https://developer.wordpress.org/reference/hooks/nav_menu_link_attributes/) 
to add the `rel="nofollow"` attribute to a menu item if the checkbox we previously added on backend is checked.


### On escaping retrieved value

When retrieving a value via `Inpsyde\MoreMenuFields\field_value()` or via (`get_post_meta()`) the value is *not*
sanitized with the callback returned via field object `sanitize_callback()` method, which is only called to sanitize
the value before saving in DB.

So the value needs to be escaped before being used. In the usage example above the obtained value is only used for a 
boolean check, so there's no need to escape, but in case the value is printed to page it needs to be escaped via 
`esc_attr`, `esc_html` or anything fits better.

---

## About customizer

WordPress provides a menu editing UI in the customizer. This package does **not** integrated there.

The reason is that many times the fields we add via this package are nothing "visual" (take as example the "nofollow"
example in this readme), so having a live preview to edit them is not really helpful, so we (and our clients) never
felt the need to have these fields in the customizer UI.

To who is interested in such feature, we can say "maybe later", but PR are always open ;)

---

## Requirements

- PHP 7+
- Composer to install

---

## Installation

Via Composer, package name is **`inpsyde/more-menu-fields`**.

---

## License and Copyright

Copyright (c) 2017 Inpsyde GmbH.

"More Menu Fields" code is licensed under [MIT license](https://opensource.org/licenses/MIT).

The team at [Inpsyde](https://inpsyde.com) is engineering the Web since 2006.
