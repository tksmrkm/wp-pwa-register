<?php

namespace WpPwaRegister;

class Customizer
{
    use Singleton;

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
        ]
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
        'application-user' => [
            'control_option' => [
                'section' => 'register',
                'label' => 'Application User',
                'description' => 'Appliation Passwords を使用するユーザー名'
            ]
        ],
        'application-password' => [
            'control_option' => [
                'section' => 'register',
                'label' => 'Application Passwords',
                'description' => 'Application Passwordsで発行されたパスワード'
            ]
        ],
        'sender-id' => [
            'control_option' => [
                'section' => 'firebase',
                'label' => 'SenderID'
            ]
        ],
        'api-key' => [
            'control_option' => [
                'section' => 'firebase',
                'label' => 'apiKey'
            ]
        ],
        'project-id' => [
            'control_option' => [
                'section' => 'firebase',
                'label' => 'projectId'
            ]
        ],
        'server-key' => [
            'control_option' => [
                'section' => 'firebase',
                'label' => 'Server Key'
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