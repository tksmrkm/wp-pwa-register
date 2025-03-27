<?php

namespace WpPwaRegister;

use WpPwaRegister\Notifications\NotificationHttpV1;

class Customizer
{
    use traits\Singleton;

    const PREFIX = 'wp-pwa-register-';
    const DEFAULT_PANEL = 'general';
    const DEFAULT_SECTION = 'general';

    private $panels = [
        'general' => [
            'title' => 'WP PWA Register',
            'description' => 'WP PWA Registerに関するカスタマイザー'
        ]
    ];

    private $sections = [
        'general' => [
            'title' => '総合'
        ],
        'firebase' => [
            'title' => 'Firebase'
        ],
        'manifest' => [
            'title' => 'Manifest'
        ],
        'register' => [
            'title' => 'Register'
        ],
        'notification' => [
            'title' => 'Notification'
        ],
    ];

    private $settings = [
        'enable' => [
            'option' => [
                'default' => false
            ],
            'control_option' => [
                'label' => '有効化',
                'type' => 'checkbox'
            ]
        ],
        'enable-on-logged-in' => [
            'option' => [
                'default' => true
            ],
            'control_option' => [
                'label' => 'ログイン時のみ有効化',
                'type' => 'checkbox'
            ]
        ],
        'enable-to-restrict-on-ip' => [
            'option' => [
                'default' => false
            ],
            'control_option' => [
                'label' => '有効化するIPを制限する',
                'type' => 'checkbox'
            ]
        ],
        'accepted-ip-address' => [
            'option' => [],
            'control_option' => [
                'label' => '制限するIPアドレス',
                'description' => '255.255.255.255/32'
            ]
        ],
        'notifications-s-maxage' => [
            'option' => [
                'default' => "600"
            ],
            'control_option' => [
                'label' => 'pwa_notifications Cache-Control',
                'description' => '/wp-json/wp/v2/pwa_notificationsのCache-Control時間（sec）'
            ]
        ],
        'debug' => [
            'option' => [
                'default' => false
            ],
            'control_option' => [
                'label' => 'Debugモード',
                'type' => 'checkbox'
            ]
        ],
        'using-register-dialog' => [
            'option' => [
                'default' => false
            ],
            'control_option' => [
                'section' => 'register',
                'label' => '登録ダイアログを表示する',
                'description' => 'サービスワーカー登録リクエストをユーザーアクション起点へ変更するための登録ダイアログを表示する。',
                'type' => 'checkbox'
            ]
        ],
        'register-icon' => [
            'control_option' => [
                'section' => 'register',
                'label' => 'アイコン画像',
                'description' => '登録ダイアログに表示されるアイコン'
            ],
            'control_class' => 'WP_Customize_Image_Control'
        ],
        'register-message' => [
            'control_option' => [
                'section' => 'register',
                'label' => 'メッセージ',
                'description' => '登録ダイアログに表示されるメッセージ'
            ]
        ],
        Users::CUSTOMIZER_SLUG_KEY => [
            'control_option' => [
                'section' => 'register',
                'label' => 'PWA Userスラッグ',
            ],
            'option' => [
                'default' => Users::POST_SLUG
            ]
        ],
        Firebase::CUSTOMIZER_KEY_APP_ID => [
            'control_option' => [
                'section' => 'firebase',
                'label' => 'App ID'
            ]
        ],
        Firebase::CUSTOMIZER_KEY_SENDER_ID => [
            'control_option' => [
                'section' => 'firebase',
                'label' => 'SenderID'
            ]
        ],
        Firebase::CUSTOMIZER_KEY_API_KEY => [
            'control_option' => [
                'section' => 'firebase',
                'label' => 'apiKey'
            ]
        ],
        Firebase::CUSTOMIZER_KEY_PROJECT_ID => [
            'control_option' => [
                'section' => 'firebase',
                'label' => 'projectId'
            ]
        ],
        Firebase::CUSTOMIZER_KEY_SERVER_KEY => [
            'control_option' => [
                'section' => 'firebase',
                'label' => 'Server Key'
            ]
        ],
        GoogleClient::CUSTOMIZER_CONFIG_PATH_KEY => [
            'control_option' => [
                'section' => 'firebase',
                'label' => 'Certs JSON PATH',
                'description' => '** NOTICE ** put JSON file on private directory.<br>ex) /var/www/html/certs.json'
            ]
        ],
        'name' => [
            'control_option' => [
                'section' => 'manifest',
                'label' => 'Name'
            ]
        ],
        'short_name' => [
            'control_option' => [
                'section' => 'manifest',
                'label' => 'ShortName'
            ]
        ],
        'start-url' => [
            'option' => [
                'default' => '/'
            ],
            'control_option' => [
                'section' => 'manifest',
                'label' => 'Start URL'
            ]
        ],
        'display' => [
            'option' => [
                'default' => 'standalone'
            ],
            'control_option' => [
                'section' => 'manifest',
                'type' => 'select',
                'choices' => [
                    'standalone' => 'standalone',
                    'fullscreen' => 'fullscreen',
                    'minimal-ui' => 'minimal-ui',
                    'browser' => 'browser'
                ],
                'label' => 'display'
            ]
        ],
        'theme-color' => [
            'option' => [
                'default' => '#333'
            ],
            'control_option' => [
                'section' => 'manifest',
                'label' => 'Theme Color'
            ],
            'control_class' => 'WP_Customize_Color_Control'
        ],
        'background-color' => [
            'option' => [
                'default' => '#FFF'
            ],
            'control_option' => [
                'section' => 'manifest',
                'label' => 'Background Color'
            ],
            'control_class' => 'WP_Customize_Color_Control'
        ],
        'icon-src' => [
            'control_option' => [
                'section' => 'manifest',
                'label' => 'ICON src',
                'description' => 'Same Origin, relative path from root (e.g.) /img/to/path.png'
            ]
        ],
        'icon-sizes' => [
            'control_option' => [
                'section' => 'manifest',
                'label' => 'ICON sizes',
                'description' => 'like as 152x152 316x316'
            ]
        ],
        'icon-type' => [
            'control_option' => [
                'section' => 'manifest',
                'label' => 'ICON type',
                'description' => 'To specify the image type (e.g.) image/png'
            ]
        ],
        'enable-dry-mode' => [
            'option' => [
                'default' => false
            ],
            'control_option' => [
                'section' => 'notification',
                'label' => 'DRYモード',
                'type' => 'checkbox'
            ]
        ],
        'enable-deletion' => [
            'option' => [
                'default' => true
            ],
            'control_option' => [
                'section' => 'notification',
                'label' => 'PWA USERの削除処理を実行する',
                'type' => 'checkbox',
                'description' => 'error statusがNotRegisteredのときに削除処理を実行する'
            ]
        ],
        'enable-hard-deletion' => [
            'option' => [
                'default' => false
            ],
            'control_option' => [
                'section' => 'notification',
                'label' => 'PWA USERを物理削除する',
                'type' => 'checkbox',
                'description' => 'error statusがNotRegisteredのときに物理削除する。off時はdeletedフラグによる論理削除'
            ]
        ],
        'deletion-limit' => [
            'control_option' => [
                'section' => 'notification',
                'label' => '削除件数上限',
                'type' => 'number'
            ],
            'option' => [
                'default' => 0
            ]
        ],
        'split-transfer' => [
            'control_option' => [
                'section' => 'notification',
                'label' => '分割送信件数',
                'type' => 'number',
                'description' => 'WIP: プッシュを分割送信するための設定。'
            ],
            'option' => [
                'default' => 1
            ]
        ],
        'split-tick' => [
            'control_option' => [
                'section' => 'notification',
                'label' => '分割送信間隔（秒）',
                'type' => 'number',
                'description' => 'WIP: プッシュを分割送信するための設定。'
            ],
            'option' => [
                'default' => 180
            ]
        ],
        'notifications_fallback_slug' => [
            'control_option' => [
                'section' => 'notification',
                'label' => 'API Fallback Slug',
                'description' => '/wp/v2/${pwa_notifications}'
            ],
            'option' => [
                'default' => 'pwa_notifications'
            ]
        ]
    ];

    public function init()
    {
        $this->prepare();
        add_action('customize_register', [$this, 'register']);
    }

    public function get_theme_mod($key = null, $default = null)
    {
        if ($key) {
            return get_theme_mod(self::PREFIX . $key, $default);
        }
    }

    private function prepare()
    {
        foreach ($this->sections as $key => $section) {
            $this->sections[$key]['panel'] = isset($section['panel'])
                ? self::PREFIX . $section['panel']
                : self::PREFIX . self::DEFAULT_PANEL;
        }

        foreach ($this->settings as $key => $setting) {
            $this->settings[$key]['control_option']['section'] = isset($setting['control_option']['section'])
                ? self::PREFIX . $setting['control_option']['section']
                : self::PREFIX . self::DEFAULT_SECTION;

            $this->settings[$key]['control_option']['settings'] = self::PREFIX . $key;
        }
    }

    public function register($customizer)
    {
        foreach($this->panels as $key => $panel) {
            if (preg_match('/^_/', $key)) {
                continue;
            }

            $customizer->add_panel(self::PREFIX . $key, $panel);
        }

        foreach($this->sections as $key => $section) {
            if (preg_match('/^_/', $key)) {
                continue;
            }

            $customizer->add_section(self::PREFIX . $key, $section);
        }

        foreach($this->settings as $key => $setting) {
            if (preg_match('/^_/', $key)) {
                continue;
            }

            $setting_key = self::PREFIX . $key;

            $setting_option = isset($setting['option']) ? $setting['option']: [];
            $control_class = isset($setting['control_class']) ? $setting['control_class'] : 'WP_Customize_Control';
            $control_option = $setting['control_option'];

            $customizer->add_setting($setting_key, $setting_option);
            $customizer->add_control(new $control_class($customizer, $setting_key, $control_option));
        }
    }
}