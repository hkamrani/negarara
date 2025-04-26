<?php
namespace Negarara;
class mihanwpUpdater
{
    private static $_base_api_server;
    private static $_license_key;
    private static $_item_id;
    private static $_current_version;
    private static $_plugin_slug;
    private static $_license_status;
    private static $_msg_inactive_license;
    private static $_msg_update_btn_in_plugins_page;


    private static $_new_version;
    private static $_package_url;
    
    static function init($args=[])
    {
        self::$_base_api_server = isset($args['base_api_server']) ? trailingslashit($args['base_api_server']) : false;
        self::$_license_key = isset($args['license_key']) ? $args['license_key'] : false;
        self::$_item_id = isset($args['item_id']) ? intval($args['item_id']) : false;
        self::$_current_version = isset($args['current_version']) ? $args['current_version'] : false;
        self::$_plugin_slug = isset($args['plugin_slug']) ? $args['plugin_slug'] : false;
        self::$_license_status = isset($args['license_status']) ? $args['license_status'] : false;
        self::$_msg_inactive_license = isset($args['inactive_license_message']) ? $args['inactive_license_message'] : false;
        self::$_msg_update_btn_in_plugins_page = isset($args['update_btn_in_plugins_page_message']) ? $args['update_btn_in_plugins_page_message'] : false;
        self::setHooks();
    }
    static function setHooks()
    {
        if(self::$_license_status)
        {
            add_filter('pre_set_site_transient_update_plugins', [__CLASS__, 'checkUpdate']);
        }else{
            add_filter('plugin_row_meta', [__CLASS__, 'showUpdateBtnOnDeactiveLicenseMode'], 10, 4);
        }

        // update site_transient_update_plugins after plugin updated
        add_action('upgrader_process_complete', [__CLASS__, 'updateSiteTransientUpdatePlugins'], 10, 2);
    }

    static function updateSiteTransientUpdatePlugins($upgrader, $hook_extra)
    {
        if($hook_extra['action'] == 'update' && $hook_extra['type'] == 'plugin')
        {
            foreach($hook_extra['plugins'] as $plugin_file)
            {
                if($plugin_file == self::$_plugin_slug)
                {
                    remove_filter('pre_set_site_transient_update_plugins', [__CLASS__, 'checkUpdate']);
                    delete_site_transient('update_plugins');
                }
            }
        }
    }

    static function showUpdateBtnOnDeactiveLicenseMode($pluginMeta, $pluginFile, $pluginData, $status)
    {
        if(self::$_plugin_slug == $pluginFile)
        {
            if(self::checkNewVersion())
            {
                $styles = [
                    'background-color: #3d4761',
                    'color: white',
                    'display: inline-block',
                    'padding: 10px',
                    'border-radius: 5px',
                ];
                $styles = implode('; ', $styles);
                $btnText = self::$_msg_update_btn_in_plugins_page ?: 'New version available';
                $btn = '<a style="'.$styles.'" id="mw-update-btn-'.self::$_item_id.'" href="#">'.$btnText.'</a>';
                $pluginMeta[] = $btn;
                $msg = self::$_msg_inactive_license ?: 'Please activate License first, then you can update plugin.';
                ?>
                <script>
                    jQuery(document).ready(function($){
                        $(document).on('click', '#mw-update-btn-<?php echo self::$_item_id?>', function(e){
                            e.preventDefault()
                            alert('<?php echo $msg?>')
                        })
                    })
                </script>
                <?php
            }
        }
        return $pluginMeta;
    }
    static function checkUpdate($transient)
    {
        if(empty($transient->checked))
        {
            return $transient;
        }
        // check is new version available
        if(self::checkNewVersion())
        {
            $transient->response[self::$_plugin_slug] = (object) [
                'new_version' => self::$_new_version,
                'package' => self::$_package_url,
                'slug' => self::$_plugin_slug
            ];
        }elseif(isset($transient->response[self::$_plugin_slug])){
            unset($transient->response[self::$_plugin_slug]);
        }
        return $transient;
    }
    static function checkNewVersion()
    {
        $url = self::$_base_api_server;
        $url .= 'api/v2/' . self::$_license_key . '/update/info';
        $args = [
            'body' => [
                'product_id' => self::$_item_id
            ],
            'timeout' => 300
        ];
        $remote = wp_remote_get($url, $args);
        if(wp_remote_retrieve_response_code($remote) != 200)
        {
            return false;
        }
        $response = json_decode(wp_remote_retrieve_body($remote));
        self::$_new_version = isset($response->version) ? $response->version : false;
        self::$_package_url = isset($response->download_url) ? $response->download_url : false;
        return version_compare(self::$_new_version, self::$_current_version) == 1;
    }
}