# More Menu Fields [![Latest Stable Version](https://poser.pugx.org/inpsyde/more-menu-fields/v/stable)](https://packagist.org/packages/inpsyde/more-menu-fields) [![Project Status](http://opensource.box.com/badges/active.svg)](http://opensource.box.com/badges) [![Build Status](https://travis-ci.org/inpsyde/more-menu-fields.svg?branch=master)](http://travis-ci.org/inpsyde/more-menu-fields) [![License](https://poser.pugx.org/inpsyde/more-menu-fields/license)](https://packagist.org/packages/inpsyde/more-menu-fields)

> Package to add more fields to WordPress menu edit screen.

---

## What / Why

WordPress provides a nice UI for editing navigation menus.

However, it is quite opinionated about the available settings for each menu item. They are:

- "Navigation Label"
- "Title Attribute"
- "Open link in a new tab"
- "CSS Classes"
- "Link Relationship (XFN)"
- "Description"

For our clients we needed additional fields, e.g. "data" attributes or "rel" attributes ("noopener", "nofollow"...).

Issue is WordPress <5.4.0 provides **no** filter to edit the default fields and there's also no action hook to allow 
echoing custom form fields HTML, like happens in many other parts of WP backend.

Since WordPress 5.4.0 there is a new Hook `wp_nav_menu_item_custom_fields` implemented which allows you to filter the current item and add custom fields.

This package exists, because we needed in WordPress <5.4.0 a way to add more fields that could work if used from more plugins. You can still use this library with newer WordPress version to work in an object oriented way on custom navigation items attributes.

---

## Usage


### First Step

Because the use target of "More Menu Fields" is to be used from plugins, it is not a plugin itself, but a *package*
that can be required by plugins via Composer.

When the package is required via Composer and Composer autoload has been loaded, it is needed to *bootstrap* the package.

It can be done inside a plugin by just calling a function:

```php
Inpsyde\MoreMenuFields\bootstrap();
```

There's no need to wrap the call in any hook and if called more than once (by different plugins) nothing bad will happen.


### The Field Interfaces

To add more fields it is necessary to create a PHP class for each of them. The class has to implement the interface
`Inpsyde\MoreMenuFields\EditField` which looks like this:

```php
interface EditField {

	public function name(): string;

	public function field_markup(): string;
}
```

The first method, `name()`, has to return the field name, it can be any string, but must be unique.
This will also be used later on to retrieve the value that is entered in the input field.

The second and last method, `field_markup()`, has to return the HTML markup for the field, as it will appear on the UI.

In the HTML markup it will very likely be necessary to use the input name, its id and its current stored value, if any.
Those information can be obtained via an object of type `Inpsyde\MoreMenuFields\EditFieldValue`. More on this soon.

Very often (if not always) the value users enter in the generated input field needs to be sanitized before being saved. 
This is why the package ships another interface `Inpsyde\MoreMenuFields\SanitizedEditField` which looks like this:

```php
interface SanitizedEditField extends EditField {

	public function sanitize_callback(): callable;
}
```

The interface extends `EditField` and its only method, `sanitize_callback()`, can be used to return a callback used to 
sanitize the users input. It is recommended to implement this interface to create fields and only use `EditField` for 
form input fields that don't actually take input, like buttons.


### Field Class Example

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
`EditFieldValue` that the field class receives in the constructor. But where does it come from?


### Adding a Field

Just having the field class above will do nothing if the package does not know about it, and to make the package aware
of the class we need to add an instance of it to the array passed by filter hook stored in the constant 
`Inpsyde\MoreMenuFields\FILTER_FIELDS`.

That filter passes to hooking callbacks the array of currently added filters, and as second argument an instance of 
`EditFieldValueFactory`: an object that can be used to obtain instances of `EditFieldValue` to be used in field classes.

Let's see an usage example:

```php
add_filter(
	Inpsyde\MoreMenuFields\FILTER_FIELDS,
	function ( array $items, EditMenuFieldValueFactory $value_factory )
	{
		$fields[] = new My\Plugin\NofollowField( $value_factory->create( 'nofollow' ) );

		return $fields;
	},
	10,
	2
);
```

When hooking `Inpsyde\MoreMenuFields\FILTER_FIELDS` the passed `EditMenuFieldValueFactory` is used to obtain an
instance of `EditFieldValue` that is injected in the field object (nothing more than what is shown above).

To obtain the `EditFieldValue` instance the `create()` method is called on the factory, passing to it the name of the 
field, that must be the exact same name returned by field object `name()` method.

That's it. The filter right above, plus the class in previous section is really all it takes to print the field
and also save it.

The benefit of this can be seen when there are many fields added. Moreover, the `Inpsyde\MoreMenuFields\FILTER_FIELDS` 
filter can be used by many plugins that know nothing about each other and all will work just fine.


### Retrieving Saved Values

At some point there will be the need to use the value stored by the added fields.

The package stores them as post meta of the related menu item post. So to retrieve them it is possible to just use
`get_post_meta()`.

The only thing needed for it is to know the meta key, which is generated by the package prepending to the return value
of field object `name()` method a fixed prefix stored in the `EditFieldValue::KEY_PREFIX` class constant:

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

Where  [`'nav_menu_link_attributes'`](https://developer.wordpress.org/reference/hooks/nav_menu_link_attributes/) filter 
is used to add the `rel="nofollow"` attribute to a menu item if the checkbox we previously added on backend is checked.


### On Escaping Retrieved Value

When retrieving a value via `Inpsyde\MoreMenuFields\field_value()` or via (`get_post_meta()`) the value is *not*
sanitized with the callback returned via field object `sanitize_callback()` method, which is only called to sanitize
the value before saving in DB.

So the value needs to be escaped before being used. In the usage example above the obtained value is only used for a 
boolean check, so there's no need to escape, but in case the value is printed to page it needs to be escaped via 
`esc_attr`, `esc_html` or anything fits better.

---

## About Customizer

WordPress provides a menu editing UI in the customizer. This package is **not** integrated there.

The reason is that many times the fields we add via this package are nothing "visual" (take as example the "nofollow"
example in this readme), so having a live preview to edit them is not really helpful, so we (and our clients) never
felt the need to have these fields in the customizer UI.

To whoever is interested in such a feature, we can say "maybe later", but PRs are always open ;)

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
