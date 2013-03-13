<?php

class shopPluginsSaveController extends waJsonController
{
    public function execute()
    {
        $plugin_id = waRequest::get('id');
        if (!$plugin_id) {
            throw new waException(_ws("Can't save plugin settings: unknown plugin id"));
        }
        $namespace = 'shop_'.$plugin_id;
        /**
         * @var shopPlugin $plugin
         */
        $plugin = waSystem::getInstance()->getPlugin($plugin_id);
        $settings = $this->getRequest()->post($namespace);
        $files = waRequest::file($namespace);
        $settings_defenitions = $plugin->getSettings();
        foreach ($files as $name => $file) {
            if (isset($settings_defenitions[$name])) {
                $settings[$name] = $file;
            }
        }
        try {
            $plugin->saveSettings($settings);
        } catch (Exception $e) {
            $this->errors = $e->getMessage();
        }
    }
}
