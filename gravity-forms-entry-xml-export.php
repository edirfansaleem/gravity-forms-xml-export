<?php
/**
 * Plugin Name: Gravity Forms Entry XML Export
 * Plugin URI:  https://github.com/edirfansaleem/
 * Description: Attach entry's XML data as a file to Gravity Forms notifications.
 * Version: 1.1
 * Author: Irfan Saleem
 * Author URI: https://github.com/edirfansaleem/
 * License: GPLv2 or later
 * Text Domain: gravity-forms-xml-export
 * Domain Path: /languages/
 */

// Prevent direct access to the plugin file
defined( 'ABSPATH' ) || exit;

add_filter( 'gform_notification_settings_fields', 'add_xml_export_setting', 1, 3 );

function add_xml_export_setting( $fields, $notification, $form ) {
    // Add the XML export checkbox setting
    $value = ! empty( $notification['xml_file_export'] );
    $fields[] = array(
        'title'  => esc_html__( 'XML Export', 'gravity-forms-xml-export' ),
        'fields' => array(
            array(
                'label'   => esc_html__( 'Enable XML Support', 'gravity-forms-xml-export' ),
                'type'    => 'checkbox',
                'name'    => 'xml_file_excel_export',
                'tooltip' => esc_html__( 'Check this box to enable XML File export for this notification.', 'gravity-forms-xml-export' ),
                'choices' => array(
                    array(
                        'label' => esc_html__( 'Enable XML File Export', 'gravity-forms-xml-export' ),
                        'name'  => 'xml_file_excel_export',
                        'value' => $value,
                    ),
                ),
            ),
        ),
    );

    return $fields;
}

add_filter( 'gform_notification', 'attach_xml_export_file', 10, 3 );

function attach_xml_export_file( $notification, $form, $entry ) {
    $xml_export_enabled = $notification['xml_file_excel_export'];

    if ( $xml_export_enabled == '1' ) {
        // Start building the XML content
        $xml_content = '<?xml version="1.0" encoding="UTF-8"?><FormData>';

        // Include form title as an attribute
        $xml_content .= '<FormTitle>' . htmlspecialchars( $form['title'] ) . '</FormTitle>';

        // Loop through entry fields and append them to the XML content
        foreach ( $entry as $field_id => $field_value ) {
            $field = GFFormsModel::get_field( $form, $field_id );
            if ( $field && ! in_array( $field_id, array( 'id', 'status', 'form_id', 'ip', 'source_url', 'currency', 'post_id', 'date_created', 'date_updated', 'is_starred', 'is_read', 'user_agent', 'payment_status', 'payment_date', 'payment_amount', 'payment_method', 'transaction_id', 'is_fulfilled', 'created_by', 'transaction_type' ) ) ) {
                $field_label = $field->label;
                $xml_content .= "<Field><Label>$field_label</Label><Value>" . htmlspecialchars( $field_value ) . "</Value></Field>";
            }
        }

        $xml_content .= '</FormData>';

        // Generate a unique filename for the XML file
        $filename = 'entry_' . $entry['id'] . '_data.xml';

        // Path to the temporary directory to store the XML file
        $upload_dir = wp_upload_dir();
        $xml_file_path = $upload_dir['path'] . '/' . $filename;

        // Save the XML content to a file
        file_put_contents( $xml_file_path, $xml_content );

        // Attach the XML file to the notification
        $notification['attachments'][] = $xml_file_path;
    }

    return $notification;
}
