# Content - Blades Compiler

This library allows you to integrate Blade-like templating functionality into your PHP application. It enables the use of Blade syntax and components, offering a familiar and powerful templating engine for your project.

**Installation**

To get started, install the bundle via Composer:

```
composer require roy404/blades
```

# Blades Feature Documentation

`Blades` is a PHP library designed to provide Blade-inspired templating capabilities. With this library, you can use Blade syntax and custom directives in your PHP applications, facilitating a smooth transition for developers familiar with Laravel's Blade engine. The library enhances your templating workflow with custom methods and functionalities that mimic Blade's behavior while offering additional flexibility for use outside the Laravel ecosystem.

# Blade Custom Methods Usage Guide

This guide explains how to use custom Blade methods, enabling dynamic and reusable templates. The following methods allow you to define custom directives and wrap content with tailored logic.

### **- Using the `Blade::directive` Method**

The `Blade::directive` method allows you to define custom Blade directives. You can use this to create reusable Blade directives that can be used throughout your Laravel views. The Blade::directive method takes two arguments: the name of the directive and a closure that defines the behavior of the directive.

**How to Use:**
1. Define the custom directive using `Blade::directive`.
2. Pass the directive name as the first argument (e.g., `'title'`).
3. Pass a closure as the second argument that processes the expression passed to the directive.
```php
Blade::directive('directive_name', function ($expression) {
    // Return the processed directive output
});
```

**Example Usage:**

**Example 1: Define a @title Directive**

You can create a custom @title directive to output the content inside the `<title>` HTML tag.

```php
Blade::directive('title', function ($expression) {
    return "<title>{$expression}</title>";
});
```

* **Usage in Blade View:**
  ```html
  <head>
    @title('Home Page')
  </head>
  ```

* **Output**
  ```html
  <head>
    <title>Home Page</title>
  </head>
  ```
  
**Explanation**

* The directive @title is replaced with the expression passed to it, wrapped in a `<title>` tag.
* In the Blade view, you can use the directive by simply writing `@title('Your Title')`.


### **- Using the `Blade::wrap` Method**

The `Blade::wrap` method is used to customize the output of content inside the `{{ }}` Blade syntax. It allows you to wrap the content of the expression inside custom PHP code.

**How to Use:**

1. Define the custom wrapper using `Blade::wrap`.
2. Pass the opening and closing delimiters as the first and second arguments (e.g., `"{{"` and `"}}"`).
3. Pass a closure as the third argument that processes the expression passed between the delimiters.

**Syntax**
```php
Blade::wrap('open_delimiter', 'close_delimiter', function ($expression) {
    // Return the processed expression
});
```

**Example Usage:**

***Example 1: Wrap Blade Expressions with htmlentities()***

You can use the `wrap` method to ensure that all content inside the `{{ }}` delimiters is safely encoded using `htmlentities()`.

```php
Blade::wrap("{{", "}}", function ($expression) {
    return "<?= htmlentities($expression ?? '') ?>";
});
```

* **Usage in Blade View:**
  ```html
  <input type="text" value="{{ 'Hi John!' }}" />
  ```
* **Output:**
  ```html
  <input type="text" value="<?= htmlentities('Display me') ?>" />
  ```

**Explanation**

* The `wrap` method customizes the Blade output by wrapping the expression inside `htmlentities()`.
* This ensures that special characters like `<`, `>`, and `&` are encoded and safely rendered as HTML.

**Conclusion**

By using `Blade::directive` and `Blade::wrap`, you can create powerful, reusable Blade directives and customize how Blade expressions are processed and displayed in your Laravel views. This provides flexibility for defining dynamic content and ensuring safe output in your application.