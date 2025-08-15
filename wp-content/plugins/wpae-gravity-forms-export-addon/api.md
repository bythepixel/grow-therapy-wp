# WP All Export API Documentation

## Instantiating the add-on

The add-on constructor takes 4 arguments:
 * Add-On Name
 * Add-On Slug
 * Add-On Post Type (this will appear in the post to export list)
 * Minimum WP All Export version that this add-on works with

```
$add_on = new RapidExportAddon('Add-On name', 'add_on_slug', 'Post Name', 'Minimum WPAE Version');
```

## Define records

The define_records method allows you to register a record source. You can define a custom table, related tables and meta tables.

The define records method takes as an argument an array with the following elements:

 * record_source - This can be a custom table ('custom_table')
 * record_table - This is the name of the mysql table from which the records will be exported
 * record_id_column - This is the primary key of the record_table
 * sub_post_column - This is used for filtering the main record table using a sub post type (i.e. form for which to export records in the GF Add-On), this is an edge parameter that is not needed at all times
 * record_date_modified_column - This is used for checking modified posts when exporting posts that have been only modified since last export
 * related_tables - This is used to define related tables to the main table (for example meta tables)

 The related tables array has the following keys:
 * element_source - This is just 'custom_table' for now
 * element_table - The name of the related table (mysql name)
 * element_id_column - The name of the primary key of the related table
 * record_id_column - This is the name of the column that defines the relation with the main table
 * is_meta (optional) - If set to true, this related table will be handled as a meta table (meta key, meta value)

Example:

```
        $add_on->define_records(
            [
                'record_source' => 'custom_table',
                'record_table' => 'gf_entry',
                'record_id_column' => 'id',
                'sub_post_column' => 'form_id',
                'record_date_modified_column' => 'date_updated',
                'related_tables' => [
                    [
                        'element_source' => 'custom_table',
                        'element_table' => 'gf_entry_meta',
                        'element_id_column' => 'id',
                        'record_id_column' => 'entry_id',
                        'is_meta' => true
                    ],
                    [
                        'element_source' => 'custom_table',
                        'element_table' => 'gf_entry_notes',
                        'element_id_column' => 'id',
                        'record_id_column' => 'entry_id',
                    ]
                ]
            ]
        );
```

## Define data elements

The data elements are how individual fields are rendered in the export file.

The define_data_element method should receive an array with the following keys:
 * element_location - This tells wpae where the field to be exported can be found, you can use the main table name, meta table name or related table names
 * record_id' - This is the name of the record column,
 * element_meta_key - In case this element is in a meta table, this field specifies the meta key of the field
 * element_label - This is the label of the element, as it appears in the "Available Data" section
 * section - This is the main section where the element will be placed in "Available Data"
 * filterable - This should be set to true if the element should be filterable. If the elemen is a date, and you want to render the filters as date, use the 'date' value for this parameter
 * default - This specifies if the element should be added by default in the  export template
 * auto - This specifies if the element should be present when migrating records

## Remove post types

WP All Export will render custom post types in the available posts to export list. If you want to write a custom add-on, you need to remove those entries from being rendered by deafult in WP All Export.
The rapid add-on exposes the method 'remove_post_type' that is used to remove the entries from the default export list, so they can be handled by the add-on.