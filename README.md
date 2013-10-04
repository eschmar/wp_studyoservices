#wp_studyoServices

Outputs a wrapped list of Services.

## Usage

Post fields:
* **Title**: Heading, Image alt attribute 
* **Content**: Text
* **Featured Image**: Service Image
* **Custom Meta Box "Attributes":**
  * **Order**: Value for manually ordering the services (ascending order)
  * **CSS Classes**: Will be added to the class attribute of the corresponding li tag for styling and positioning by predefined css styles.

Use this function inside your template:

```php
studyo_services_output($slug, $wrap_class = '', $ul_class = '', $img_class = '' );
```

## License

MIT License
