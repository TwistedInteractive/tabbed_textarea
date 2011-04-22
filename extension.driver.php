<?php
/**
 * (c) 2011 Twisted - www.twisted.nl
 * Author: Giel Berkers
 * Date: 21-4-11
 * Time: 15:12
 */

Class extension_tabbed_textarea extends Extension
{

    /**
     * About
     * @return array    Information
     */
    public function about()
    {
        return array(
            'name' => 'Field: Tabbed Textarea',
            'version' => '1.0',
            'release-date' => '2011-04-21',
            'author' => array(
                'name' => 'Giel Berkers',
                'website' => 'http://www.gielberkers.com',
                'email' => 'info@gielberkers.com'
            )
        );
    }

    /**
     * Get the subscribed delegates
     * @return array    The delegates
     */
    public function getSubscribedDelegates()
    {
        return array(
            array(
                'page' => '/backend/',
                'delegate' => 'InitaliseAdminPageHead',
                'callback' => 'addScriptToHead'
            )
        );
    }

    /**
     * Add script to the <head>-section of the admin area
     */
    public function addScriptToHead($context)
    {
        $callback   = $context['parent']->getPageCallback();
        $action     = isset($callback['context']['page']) ? $callback['context']['page'] : false;

        if($callback['driver'] == 'publish' && ($action == 'new' || $action == 'edit'))
        {
            $context['parent']->Page->addScriptToHead(URL.'/extensions/tabbed_textarea/assets/tabbed_textarea.js', 51, false);
            $context['parent']->Page->addStylesheetToHead(URL.'/extensions/tabbed_textarea/assets/tabbed_textarea.css', 'screen', 201);
        }
    }

    /**
     * De-Installation
     * @return void
     */
    public function uninstall()
    {
        Symphony::Database()->query('DROP TABLE `tbl_fields_tabbed_textarea`;');
        // Symphony::Database()->query('DROP TABLE `tbl_tabbed_textarea_values`;');
    }

    /**
     * Installation
     * @return void
     */
    public function install()
    {
        Symphony::Database()->query('
            CREATE TABLE IF NOT EXISTS `tbl_fields_tabbed_textarea` (
              `id` int(11) unsigned NOT NULL auto_increment,
              `field_id` int(11) unsigned NOT NULL,
              `formatter` varchar(100) collate utf8_unicode_ci default NULL,
              `size` int(3) unsigned NOT NULL,
              `default_tabs` tinytext NOT NULL,
              `only_developer` int(1) NOT NULL,
              PRIMARY KEY  (`id`),
              KEY `field_id` (`field_id`)
            );
        ');

        /*
        Symphony::Database()->query('
            CREATE TABLE IF NOT EXISTS `tbl_tabbed_textarea_values` (
              `id` int(11) unsigned NOT NULL auto_increment,
              `entry_id` int(11) unsigned NOT NULL,
              `tab` tinytext,
              `value` text,
              `value_formatted` text,
              PRIMARY KEY  (`id`),
              KEY `field_id` (`entry_id`)
            );
        ');
        */
        
    }
}
