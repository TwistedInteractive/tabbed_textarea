<?php

Class extension_tabbed_textarea extends Extension
{
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
	 * @param $context
	 */
    public function addScriptToHead($context)
    {
        $callback   = Administration::instance()->getPageCallback();
        $action     = isset($callback['context']['page']) ? $callback['context']['page'] : false;

        if($callback['driver'] == 'publish' && ($action == 'new' || $action == 'edit'))
        {
            Administration::instance()->Page->addScriptToHead(URL.'/extensions/tabbed_textarea/assets/tabbed_textarea.js', 551, false);
	        Administration::instance()->Page->addStylesheetToHead(URL.'/extensions/tabbed_textarea/assets/tabbed_textarea.css', 'screen', 201);
        }
    }

    /**
     * De-Installation
     * @return void
     */
    public function uninstall()
    {
        Symphony::Database()->query('DROP TABLE `tbl_fields_tabbed_textarea`;');
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
    }
}
