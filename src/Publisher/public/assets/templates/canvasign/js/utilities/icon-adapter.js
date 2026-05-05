/**
 * Force Icon Fix - Font Awesome to Bootstrap Icons
 * Comprehensive mapping for ALL CanvaStack Theme Engine icons
 * Auto-converts FA classes to Bootstrap Icons classes in DOM
 */
(function() {
    'use strict';

    const iconMapping = {

        // =============================================
        // BRAND / SOCIAL ICONS
        // =============================================
        'fa-500px':               'bi-person-circle',
        'fa-amazon':              'bi-bag',
        'fa-android':             'bi-android2',
        'fa-angellist':           'bi-person-badge',
        'fa-apple':               'bi-apple',
        'fa-behance':             'bi-behance',
        'fa-behance-square':      'bi-behance',
        'fa-bitbucket':           'bi-git',
        'fa-bitbucket-square':    'bi-git',
        'fa-bitcoin':             'bi-currency-bitcoin',
        'fa-btc':                 'bi-currency-bitcoin',
        'fa-black-tie':           'bi-person-badge',
        'fa-buysellads':          'bi-megaphone',
        'fa-chrome':              'bi-browser-chrome',
        'fa-codepen':             'bi-code-slash',
        'fa-connectdevelop':      'bi-diagram-3',
        'fa-contao':              'bi-c-circle',
        'fa-creative-commons':    'bi-cc-circle',
        'fa-css3':                'bi-filetype-css',
        'fa-dashcube':            'bi-grid',
        'fa-delicious':           'bi-bookmark',
        'fa-deviantart':          'bi-palette',
        'fa-digg':                'bi-hand-thumbs-up',
        'fa-dribbble':            'bi-dribbble',
        'fa-dropbox':             'bi-dropbox',
        'fa-drupal':              'bi-globe',
        'fa-empire':              'bi-shield',
        'fa-expeditedssl':        'bi-shield-lock',
        'fa-facebook':            'bi-facebook',
        'fa-facebook-f':          'bi-facebook',
        'fa-facebook-official':   'bi-facebook',
        'fa-facebook-square':     'bi-facebook',
        'fa-firefox':             'bi-browser-firefox',
        'fa-flickr':              'bi-camera',
        'fa-fonticons':           'bi-fonts',
        'fa-forumbee':            'bi-chat-dots',
        'fa-foursquare':          'bi-geo-alt',
        'fa-ge':                  'bi-building',
        'fa-get-pocket':          'bi-bookmark',
        'fa-gg':                  'bi-circle',
        'fa-gg-circle':           'bi-circle',
        'fa-git':                 'bi-git',
        'fa-git-square':          'bi-git',
        'fa-github':              'bi-github',
        'fa-github-alt':          'bi-github',
        'fa-github-square':       'bi-github',
        'fa-gittip':              'bi-gift',
        'fa-google':              'bi-google',
        'fa-google-plus':         'bi-google',
        'fa-google-plus-square':  'bi-google',
        'fa-google-wallet':       'bi-wallet2',
        'fa-gratipay':            'bi-heart',
        'fa-hacker-news':         'bi-newspaper',
        'fa-houzz':               'bi-house',
        'fa-html5':               'bi-filetype-html',
        'fa-instagram':           'bi-instagram',
        'fa-internet-explorer':   'bi-browser-edge',
        'fa-ioxhost':             'bi-server',
        'fa-joomla':              'bi-globe',
        'fa-jsfiddle':            'bi-code-slash',
        'fa-lastfm':              'bi-music-note',
        'fa-lastfm-square':       'bi-music-note',
        'fa-leanpub':             'bi-book',
        'fa-linkedin':            'bi-linkedin',
        'fa-linkedin-square':     'bi-linkedin',
        'fa-linux':               'bi-terminal',
        'fa-maxcdn':              'bi-lightning',
        'fa-meanpath':            'bi-diagram-3',
        'fa-medium':              'bi-medium',
        'fa-odnoklassniki':       'bi-person-circle',
        'fa-odnoklassniki-square':'bi-person-circle',
        'fa-opencart':            'bi-cart',
        'fa-openid':              'bi-key',
        'fa-opera':               'bi-browser-edge',
        'fa-optin-monster':       'bi-megaphone',
        'fa-pagelines':           'bi-leaf',
        'fa-paypal':              'bi-paypal',
        'fa-pied-piper':          'bi-music-note-beamed',
        'fa-pied-piper-alt':      'bi-music-note-beamed',
        'fa-pinterest':           'bi-pinterest',
        'fa-pinterest-p':         'bi-pinterest',
        'fa-pinterest-square':    'bi-pinterest',
        'fa-qq':                  'bi-chat',
        'fa-ra':                  'bi-shield',
        'fa-rebel':               'bi-shield',
        'fa-reddit':              'bi-reddit',
        'fa-reddit-square':       'bi-reddit',
        'fa-registered':          'bi-r-circle',
        'fa-renren':              'bi-people',
        'fa-safari':              'bi-browser-safari',
        'fa-sellsy':              'bi-shop',
        'fa-shirtsinbulk':        'bi-bag',
        'fa-simplybuilt':         'bi-tools',
        'fa-skyatlas':            'bi-map',
        'fa-skype':               'bi-skype',
        'fa-slack':               'bi-slack',
        'fa-slideshare':          'bi-easel',
        'fa-soundcloud':          'bi-soundwave',
        'fa-spotify':             'bi-spotify',
        'fa-stack-exchange':      'bi-stack',
        'fa-stack-overflow':      'bi-stack-overflow',
        'fa-steam':               'bi-controller',
        'fa-steam-square':        'bi-controller',
        'fa-stumbleupon':         'bi-hand-thumbs-up',
        'fa-stumbleupon-circle':  'bi-hand-thumbs-up',
        'fa-tencent-weibo':       'bi-chat',
        'fa-trademark':           'bi-tm',
        'fa-trello':              'bi-trello',
        'fa-tripadvisor':         'bi-compass',
        'fa-tumblr':              'bi-tumblr',
        'fa-tumblr-square':       'bi-tumblr',
        'fa-twitch':              'bi-twitch',
        'fa-twitter':             'bi-twitter-x',
        'fa-twitter-square':      'bi-twitter-x',
        'fa-viacoin':             'bi-currency-bitcoin',
        'fa-vimeo':               'bi-vimeo',
        'fa-vimeo-square':        'bi-vimeo',
        'fa-vine':                'bi-camera-video',
        'fa-vk':                  'bi-chat',
        'fa-wechat':              'bi-wechat',
        'fa-weibo':               'bi-chat',
        'fa-weixin':              'bi-wechat',
        'fa-whatsapp':            'bi-whatsapp',
        'fa-wikipedia-w':         'bi-wikipedia',
        'fa-windows':             'bi-windows',
        'fa-wordpress':           'bi-wordpress',
        'fa-xing':                'bi-x-circle',
        'fa-xing-square':         'bi-x-circle',
        'fa-y-combinator':        'bi-y-circle',
        'fa-y-combinator-square': 'bi-y-circle',
        'fa-yahoo':               'bi-yahoo',
        'fa-yc':                  'bi-y-circle',
        'fa-yc-square':           'bi-y-circle',
        'fa-yelp':                'bi-star',
        'fa-youtube':             'bi-youtube',
        'fa-youtube-play':        'bi-youtube',
        'fa-youtube-square':      'bi-youtube',
        'fa-adn':                 'bi-person-circle',

        // =============================================
        // CURRENCY ICONS
        // =============================================
        'fa-cny':                 'bi-currency-yen',
        'fa-dollar':              'bi-currency-dollar',
        'fa-eur':                 'bi-currency-euro',
        'fa-euro':                'bi-currency-euro',
        'fa-gbp':                 'bi-currency-pound',
        'fa-ils':                 'bi-currency-exchange',
        'fa-inr':                 'bi-currency-rupee',
        'fa-jpy':                 'bi-currency-yen',
        'fa-krw':                 'bi-currency-exchange',
        'fa-rmb':                 'bi-currency-yen',
        'fa-rouble':              'bi-currency-exchange',
        'fa-rub':                 'bi-currency-exchange',
        'fa-ruble':               'bi-currency-exchange',
        'fa-rupee':               'bi-currency-rupee',
        'fa-shekel':              'bi-currency-exchange',
        'fa-sheqel':              'bi-currency-exchange',
        'fa-try':                 'bi-currency-exchange',
        'fa-turkish-lira':        'bi-currency-exchange',
        'fa-usd':                 'bi-currency-dollar',
        'fa-won':                 'bi-currency-exchange',
        'fa-yen':                 'bi-currency-yen',

        // =============================================
        // PAYMENT / CREDIT CARD ICONS
        // =============================================
        'fa-cc':                  'bi-credit-card',
        'fa-cc-amex':             'bi-credit-card-2-front',
        'fa-cc-diners-club':      'bi-credit-card-2-front',
        'fa-cc-discover':         'bi-credit-card-2-front',
        'fa-cc-jcb':              'bi-credit-card-2-front',
        'fa-cc-mastercard':       'bi-credit-card-2-front',
        'fa-cc-paypal':           'bi-paypal',
        'fa-cc-stripe':           'bi-credit-card',
        'fa-cc-visa':             'bi-credit-card-2-front',
        'fa-credit-card':         'bi-credit-card',
        'fa-money':               'bi-cash',

        // =============================================
        // GENDER ICONS
        // =============================================
        'fa-genderless':          'bi-gender-neuter',
        'fa-intersex':            'bi-gender-ambiguous',
        'fa-mars':                'bi-gender-male',
        'fa-mars-double':         'bi-gender-male',
        'fa-mars-stroke':         'bi-gender-male',
        'fa-mars-stroke-h':       'bi-gender-male',
        'fa-mars-stroke-v':       'bi-gender-male',
        'fa-mercury':             'bi-gender-ambiguous',
        'fa-neuter':              'bi-gender-neuter',
        'fa-transgender':         'bi-gender-trans',
        'fa-transgender-alt':     'bi-gender-trans',
        'fa-venus':               'bi-gender-female',
        'fa-venus-double':        'bi-gender-female',
        'fa-venus-mars':          'bi-gender-ambiguous',
        'fa-female':              'bi-gender-female',
        'fa-male':                'bi-gender-male',

        // =============================================
        // MEDICAL / HEALTH ICONS
        // =============================================
        'fa-ambulance':           'bi-truck',
        'fa-h-square':            'bi-hospital',
        'fa-heartbeat':           'bi-activity',
        'fa-hospital-o':          'bi-hospital',
        'fa-medkit':              'bi-briefcase',
        'fa-stethoscope':         'bi-heart-pulse',
        'fa-user-md':             'bi-person-badge',
        'fa-wheelchair':          'bi-person-wheelchair',
        'fa-life-bouy':           'bi-life-preserver',
        'fa-life-buoy':           'bi-life-preserver',
        'fa-life-ring':           'bi-life-preserver',
        'fa-life-saver':          'bi-life-preserver',
        'fa-support':             'bi-life-preserver',

        // =============================================
        // BATTERY ICONS
        // =============================================
        'fa-battery-0':              'bi-battery',
        'fa-battery-1':              'bi-battery',
        'fa-battery-2':              'bi-battery-half',
        'fa-battery-3':              'bi-battery-half',
        'fa-battery-4':              'bi-battery-full',
        'fa-battery-empty':          'bi-battery',
        'fa-battery-full':           'bi-battery-full',
        'fa-battery-half':           'bi-battery-half',
        'fa-battery-quarter':        'bi-battery',
        'fa-battery-three-quarters': 'bi-battery-charging',

        // =============================================
        // HOURGLASS ICONS
        // =============================================
        'fa-hourglass':           'bi-hourglass',
        'fa-hourglass-1':         'bi-hourglass-top',
        'fa-hourglass-2':         'bi-hourglass-split',
        'fa-hourglass-3':         'bi-hourglass-bottom',
        'fa-hourglass-end':       'bi-hourglass-bottom',
        'fa-hourglass-half':      'bi-hourglass-split',
        'fa-hourglass-o':         'bi-hourglass',
        'fa-hourglass-start':     'bi-hourglass-top',

        // =============================================
        // HAND ICONS
        // =============================================
        'fa-hand-grab-o':         'bi-hand-index',
        'fa-hand-lizard-o':       'bi-hand-index',
        'fa-hand-o-down':         'bi-hand-index-thumb',
        'fa-hand-o-left':         'bi-hand-index-thumb',
        'fa-hand-o-right':        'bi-hand-index-thumb',
        'fa-hand-o-up':           'bi-hand-index-thumb',
        'fa-hand-paper-o':        'bi-hand-raised',
        'fa-hand-peace-o':        'bi-peace',
        'fa-hand-pointer-o':      'bi-hand-index',
        'fa-hand-rock-o':         'bi-hand-index',
        'fa-hand-scissors-o':     'bi-scissors',
        'fa-hand-spock-o':        'bi-hand-raised',
        'fa-hand-stop-o':         'bi-hand-raised',
        'fa-thumbs-down':         'bi-hand-thumbs-down',
        'fa-thumbs-o-down':       'bi-hand-thumbs-down',
        'fa-thumbs-o-up':         'bi-hand-thumbs-up',
        'fa-thumbs-up':           'bi-hand-thumbs-up',

        // =============================================
        // CALENDAR ICONS
        // =============================================
        'fa-calendar':            'bi-calendar3',
        'fa-calendar-o':          'bi-calendar',
        'fa-calendar-check-o':    'bi-calendar-check',
        'fa-calendar-minus-o':    'bi-calendar-minus',
        'fa-calendar-plus-o':     'bi-calendar-plus',
        'fa-calendar-times-o':    'bi-calendar-x',

        // =============================================
        // MAP / LOCATION ICONS
        // =============================================
        'fa-map':                 'bi-map',
        'fa-map-o':               'bi-map',
        'fa-map-marker':          'bi-geo-alt',
        'fa-map-pin':             'bi-pin-map',
        'fa-map-signs':           'bi-signpost-split',
        'fa-location-arrow':      'bi-cursor',
        'fa-compass':             'bi-compass',
        'fa-globe':               'bi-globe',
        'fa-street-view':         'bi-person-walking',

        // =============================================
        // TRANSPORT / VEHICLE ICONS
        // =============================================
        'fa-ambulance':           'bi-truck',
        'fa-automobile':          'bi-car-front',
        'fa-bicycle':             'bi-bicycle',
        'fa-bus':                 'bi-bus-front',
        'fa-cab':                 'bi-taxi-front',
        'fa-car':                 'bi-car-front',
        'fa-fighter-jet':         'bi-airplane',
        'fa-motorcycle':          'bi-bicycle',
        'fa-plane':               'bi-airplane',
        'fa-rocket':              'bi-rocket',
        'fa-ship':                'bi-water',
        'fa-space-shuttle':       'bi-rocket-takeoff',
        'fa-subway':              'bi-train-front',
        'fa-taxi':                'bi-taxi-front',
        'fa-train':               'bi-train-front',
        'fa-truck':               'bi-truck',
        'fa-road':                'bi-sign-turn-right',

        // =============================================
        // STICKY NOTE / DOCUMENT ICONS
        // =============================================
        'fa-sticky-note':         'bi-sticky',
        'fa-sticky-note-o':       'bi-sticky',
        'fa-newspaper-o':         'bi-newspaper',
        'fa-clipboard':           'bi-clipboard',
        'fa-paste':               'bi-clipboard-check',
        'fa-files-o':             'bi-files',
        'fa-copy':                'bi-copy',

        // =============================================
        // TECHNOLOGY / DEVICES
        // =============================================
        'fa-television':          'bi-tv',
        'fa-tv':                  'bi-tv',
        'fa-desktop':             'bi-display',
        'fa-laptop':              'bi-laptop',
        'fa-tablet':              'bi-tablet',
        'fa-mobile':              'bi-phone',
        'fa-mobile-phone':        'bi-phone',
        'fa-keyboard-o':          'bi-keyboard',
        'fa-mouse-pointer':       'bi-cursor',
        'fa-i-cursor':            'bi-cursor-text',
        'fa-hdd-o':               'bi-hdd',
        'fa-server':              'bi-server',
        'fa-database':            'bi-database',
        'fa-plug':                'bi-plug',
        'fa-wifi':                'bi-wifi',
        'fa-signal':              'bi-signal',
        'fa-tty':                 'bi-telephone',
        'fa-fax':                 'bi-printer',
        'fa-print':               'bi-printer',
        'fa-qrcode':              'bi-qr-code',
        'fa-barcode':             'bi-upc-scan',

        // =============================================
        // INDUSTRY / BUILDING
        // =============================================
        'fa-industry':            'bi-building',
        'fa-building':            'bi-building',
        'fa-building-o':          'bi-building',
        'fa-bank':                'bi-bank',
        'fa-institution':         'bi-bank',
        'fa-university':          'bi-mortarboard',
        'fa-graduation-cap':      'bi-mortarboard',
        'fa-mortar-board':        'bi-mortarboard',
        'fa-hotel':               'bi-building',
        'fa-bed':                 'bi-house',

        // =============================================
        // FILE ICONS
        // =============================================
        'fa-file':                'bi-file',
        'fa-file-o':              'bi-file',
        'fa-file-text':           'bi-file-text',
        'fa-file-text-o':         'bi-file-text',
        'fa-file-archive-o':      'bi-file-zip',
        'fa-file-audio-o':        'bi-file-music',
        'fa-file-code-o':         'bi-file-code',
        'fa-file-excel-o':        'bi-file-excel',
        'fa-file-image-o':        'bi-file-image',
        'fa-file-movie-o':        'bi-file-play',
        'fa-file-pdf-o':          'bi-file-pdf',
        'fa-file-photo-o':        'bi-file-image',
        'fa-file-picture-o':      'bi-file-image',
        'fa-file-powerpoint-o':   'bi-file-ppt',
        'fa-file-sound-o':        'bi-file-music',
        'fa-file-video-o':        'bi-file-play',
        'fa-file-word-o':         'bi-file-word',
        'fa-file-zip-o':          'bi-file-zip',
        'fa-floppy-o':            'bi-floppy',
        'fa-save':                'bi-floppy',

        // =============================================
        // FOLDER ICONS
        // =============================================
        'fa-folder':              'bi-folder',
        'fa-folder-o':            'bi-folder',
        'fa-folder-open':         'bi-folder-open',
        'fa-folder-open-o':       'bi-folder-open',
        'fa-archive':             'bi-archive',

        // =============================================
        // TEXT EDITOR ICONS
        // =============================================
        'fa-align-center':        'bi-text-center',
        'fa-align-justify':       'bi-justify',
        'fa-align-left':          'bi-text-left',
        'fa-align-right':         'bi-text-right',
        'fa-bold':                'bi-type-bold',
        'fa-chain':               'bi-link',
        'fa-chain-broken':        'bi-link-45deg',
        'fa-columns':             'bi-layout-split',
        'fa-cut':                 'bi-scissors',
        'fa-dedent':              'bi-text-indent-right',
        'fa-eraser':              'bi-eraser',
        'fa-font':                'bi-fonts',
        'fa-header':              'bi-type-h1',
        'fa-indent':              'bi-text-indent-left',
        'fa-italic':              'bi-type-italic',
        'fa-link':                'bi-link',
        'fa-list':                'bi-list',
        'fa-list-alt':            'bi-list-ul',
        'fa-list-ol':             'bi-list-ol',
        'fa-list-ul':             'bi-list-ul',
        'fa-outdent':             'bi-text-indent-right',
        'fa-paperclip':           'bi-paperclip',
        'fa-paragraph':           'bi-paragraph',
        'fa-repeat':              'bi-arrow-clockwise',
        'fa-rotate-left':         'bi-arrow-counterclockwise',
        'fa-rotate-right':        'bi-arrow-clockwise',
        'fa-scissors':            'bi-scissors',
        'fa-strikethrough':       'bi-type-strikethrough',
        'fa-subscript':           'bi-subscript',
        'fa-superscript':         'bi-superscript',
        'fa-table':               'bi-table',
        'fa-text-height':         'bi-text-paragraph',
        'fa-text-width':          'bi-text-paragraph',
        'fa-underline':           'bi-type-underline',
        'fa-undo':                'bi-arrow-counterclockwise',
        'fa-unlink':              'bi-link-45deg',

        // =============================================
        // GRID / LAYOUT ICONS
        // =============================================
        'fa-th':                  'bi-grid',
        'fa-th-large':            'bi-grid-1x2',
        'fa-th-list':             'bi-list',
        'fa-bars':                'bi-list',
        'fa-navicon':             'bi-list',
        'fa-reorder':             'bi-list',

        // =============================================
        // OBJECT / SHAPE ICONS
        // =============================================
        'fa-object-group':        'bi-bounding-box',
        'fa-object-ungroup':      'bi-bounding-box-circles',
        'fa-clone':               'bi-copy',
        'fa-cube':                'bi-box',
        'fa-cubes':               'bi-boxes',
        'fa-diamond':             'bi-gem',
        'fa-circle':              'bi-circle-fill',
        'fa-circle-o':            'bi-circle',
        'fa-circle-o-notch':      'bi-arrow-repeat',
        'fa-circle-thin':         'bi-circle',
        'fa-square':              'bi-square-fill',
        'fa-square-o':            'bi-square',
        'fa-dot-circle-o':        'bi-record-circle',

        // =============================================
        // ARROW ICONS
        // =============================================
        'fa-angle-double-down':   'bi-chevron-double-down',
        'fa-angle-double-left':   'bi-chevron-double-left',
        'fa-angle-double-right':  'bi-chevron-double-right',
        'fa-angle-double-up':     'bi-chevron-double-up',
        'fa-angle-down':          'bi-chevron-down',
        'fa-angle-left':          'bi-chevron-left',
        'fa-angle-right':         'bi-chevron-right',
        'fa-angle-up':            'bi-chevron-up',
        'fa-arrow-circle-down':   'bi-arrow-down-circle',
        'fa-arrow-circle-left':   'bi-arrow-left-circle',
        'fa-arrow-circle-o-down': 'bi-arrow-down-circle',
        'fa-arrow-circle-o-left': 'bi-arrow-left-circle',
        'fa-arrow-circle-o-right':'bi-arrow-right-circle',
        'fa-arrow-circle-o-up':   'bi-arrow-up-circle',
        'fa-arrow-circle-right':  'bi-arrow-right-circle',
        'fa-arrow-circle-up':     'bi-arrow-up-circle',
        'fa-arrow-down':          'bi-arrow-down',
        'fa-arrow-left':          'bi-arrow-left',
        'fa-arrow-right':         'bi-arrow-right',
        'fa-arrow-up':            'bi-arrow-up',
        'fa-arrows':              'bi-arrows-move',
        'fa-arrows-alt':          'bi-fullscreen',
        'fa-arrows-h':            'bi-arrows-expand',
        'fa-arrows-v':            'bi-arrows-collapse',
        'fa-caret-down':          'bi-caret-down-fill',
        'fa-caret-left':          'bi-caret-left-fill',
        'fa-caret-right':         'bi-caret-right-fill',
        'fa-caret-up':            'bi-caret-up-fill',
        'fa-caret-square-o-down': 'bi-caret-down-square',
        'fa-caret-square-o-left': 'bi-caret-left-square',
        'fa-caret-square-o-right':'bi-caret-right-square',
        'fa-caret-square-o-up':   'bi-caret-up-square',
        'fa-chevron-circle-down': 'bi-chevron-down',
        'fa-chevron-circle-left': 'bi-chevron-left',
        'fa-chevron-circle-right':'bi-chevron-right',
        'fa-chevron-circle-up':   'bi-chevron-up',
        'fa-chevron-down':        'bi-chevron-down',
        'fa-chevron-left':        'bi-chevron-left',
        'fa-chevron-right':       'bi-chevron-right',
        'fa-chevron-up':          'bi-chevron-up',
        'fa-long-arrow-down':     'bi-arrow-down',
        'fa-long-arrow-left':     'bi-arrow-left',
        'fa-long-arrow-right':    'bi-arrow-right',
        'fa-long-arrow-up':       'bi-arrow-up',
        'fa-level-down':          'bi-arrow-return-right',
        'fa-level-up':            'bi-arrow-return-left',
        'fa-toggle-down':         'bi-chevron-down',
        'fa-toggle-left':         'bi-chevron-left',
        'fa-toggle-right':        'bi-chevron-right',
        'fa-toggle-up':           'bi-chevron-up',
        'fa-exchange':            'bi-arrow-left-right',
        'fa-random':              'bi-shuffle',
        'fa-retweet':             'bi-repeat',
        'fa-mail-forward':        'bi-forward',
        'fa-mail-reply':          'bi-reply',
        'fa-mail-reply-all':      'bi-reply-all',
        'fa-reply':               'bi-reply',
        'fa-reply-all':           'bi-reply-all',
        'fa-share':               'bi-share',
        'fa-share-alt':           'bi-share',
        'fa-share-alt-square':    'bi-share',
        'fa-share-square':        'bi-box-arrow-up-right',
        'fa-share-square-o':      'bi-box-arrow-up-right',
        'fa-external-link':       'bi-box-arrow-up-right',
        'fa-external-link-square':'bi-box-arrow-up-right',

        // =============================================
        // MEDIA PLAYER ICONS
        // =============================================
        'fa-backward':            'bi-skip-backward',
        'fa-compress':            'bi-fullscreen-exit',
        'fa-eject':               'bi-eject',
        'fa-expand':              'bi-fullscreen',
        'fa-fast-backward':       'bi-skip-start',
        'fa-fast-forward':        'bi-skip-end',
        'fa-forward':             'bi-skip-forward',
        'fa-pause':               'bi-pause',
        'fa-play':                'bi-play',
        'fa-play-circle':         'bi-play-circle',
        'fa-play-circle-o':       'bi-play-circle',
        'fa-step-backward':       'bi-skip-backward-btn',
        'fa-step-forward':        'bi-skip-forward-btn',
        'fa-stop':                'bi-stop',
        'fa-film':                'bi-film',
        'fa-video-camera':        'bi-camera-video',
        'fa-music':               'bi-music-note',
        'fa-headphones':          'bi-headphones',
        'fa-microphone':          'bi-mic',
        'fa-microphone-slash':    'bi-mic-mute',
        'fa-volume-down':         'bi-volume-down',
        'fa-volume-off':          'bi-volume-mute',
        'fa-volume-up':           'bi-volume-up',

        // =============================================
        // DASHBOARD / NAVIGATION
        // =============================================
        'fa-dashboard':           'bi-speedometer2',
        'fa-tachometer':          'bi-speedometer2',
        'fa-home':                'bi-house',
        'fa-sitemap':             'bi-diagram-3',
        'fa-inbox':               'bi-inbox',
        'fa-tasks':               'bi-list-check',
        'fa-sliders':             'bi-sliders',

        // =============================================
        // USER / PEOPLE ICONS
        // =============================================
        'fa-user':                'bi-person',
        'fa-user-plus':           'bi-person-plus',
        'fa-user-secret':         'bi-person-badge',
        'fa-user-times':          'bi-person-x',
        'fa-users':               'bi-people',
        'fa-group':               'bi-people',
        'fa-child':               'bi-person',
        'fa-street-view':         'bi-person-walking',

        // =============================================
        // SETTINGS / TOOLS
        // =============================================
        'fa-cog':                 'bi-gear',
        'fa-cogs':                'bi-gear-wide-connected',
        'fa-gear':                'bi-gear',
        'fa-gears':               'bi-gear-wide-connected',
        'fa-wrench':              'bi-wrench',
        'fa-tools':               'bi-tools',
        'fa-magic':               'bi-magic',
        'fa-paint-brush':         'bi-brush',
        'fa-eyedropper':          'bi-eyedropper',
        'fa-adjust':              'bi-circle-half',
        'fa-crop':                'bi-crop',
        'fa-crosshairs':          'bi-crosshair',
        'fa-sliders':             'bi-sliders',

        // =============================================
        // COMMUNICATION
        // =============================================
        'fa-envelope':            'bi-envelope',
        'fa-envelope-o':          'bi-envelope',
        'fa-envelope-square':     'bi-envelope',
        'fa-comment':             'bi-chat',
        'fa-comment-o':           'bi-chat',
        'fa-comments':            'bi-chat-dots',
        'fa-comments-o':          'bi-chat-dots',
        'fa-commenting':          'bi-chat-text',
        'fa-commenting-o':        'bi-chat-text',
        'fa-phone':               'bi-telephone',
        'fa-phone-square':        'bi-telephone',
        'fa-bell':                'bi-bell',
        'fa-bell-o':              'bi-bell',
        'fa-bell-slash':          'bi-bell-slash',
        'fa-bell-slash-o':        'bi-bell-slash',
        'fa-rss':                 'bi-rss',
        'fa-rss-square':          'bi-rss',
        'fa-feed':                'bi-rss',
        'fa-send':                'bi-send',
        'fa-send-o':              'bi-send',
        'fa-paper-plane':         'bi-send',
        'fa-paper-plane-o':       'bi-send',

        // =============================================
        // SECURITY / AUTH
        // =============================================
        'fa-lock':                'bi-lock',
        'fa-unlock':              'bi-unlock',
        'fa-unlock-alt':          'bi-unlock',
        'fa-key':                 'bi-key',
        'fa-shield':              'bi-shield',
        'fa-eye':                 'bi-eye',
        'fa-eye-slash':           'bi-eye-slash',
        'fa-sign-in':             'bi-box-arrow-in-right',
        'fa-sign-out':            'bi-box-arrow-right',
        'fa-power-off':           'bi-power',

        // =============================================
        // STATUS / INDICATORS
        // =============================================
        'fa-check':               'bi-check',
        'fa-check-circle':        'bi-check-circle',
        'fa-check-circle-o':      'bi-check-circle',
        'fa-check-square':        'bi-check-square',
        'fa-check-square-o':      'bi-check-square',
        'fa-times':               'bi-x',
        'fa-times-circle':        'bi-x-circle',
        'fa-times-circle-o':      'bi-x-circle',
        'fa-close':               'bi-x',
        'fa-remove':              'bi-x',
        'fa-ban':                 'bi-slash-circle',
        'fa-exclamation':         'bi-exclamation',
        'fa-exclamation-circle':  'bi-exclamation-circle',
        'fa-exclamation-triangle':'bi-exclamation-triangle',
        'fa-warning':             'bi-exclamation-triangle',
        'fa-info':                'bi-info',
        'fa-info-circle':         'bi-info-circle',
        'fa-question':            'bi-question',
        'fa-question-circle':     'bi-question-circle',
        'fa-spinner':             'bi-arrow-repeat',
        'fa-toggle-off':          'bi-toggle-off',
        'fa-toggle-on':           'bi-toggle-on',

        // =============================================
        // ACTIONS / CRUD
        // =============================================
        'fa-plus':                'bi-plus',
        'fa-plus-circle':         'bi-plus-circle',
        'fa-plus-square':         'bi-plus-square',
        'fa-plus-square-o':       'bi-plus-square',
        'fa-minus':               'bi-dash',
        'fa-minus-circle':        'bi-dash-circle',
        'fa-minus-square':        'bi-dash-square',
        'fa-minus-square-o':      'bi-dash-square',
        'fa-edit':                'bi-pencil-square',
        'fa-pencil':              'bi-pencil',
        'fa-pencil-square':       'bi-pencil-square',
        'fa-pencil-square-o':     'bi-pencil-square',
        'fa-trash':               'bi-trash',
        'fa-trash-o':             'bi-trash',
        'fa-search':              'bi-search',
        'fa-search-minus':        'bi-zoom-out',
        'fa-search-plus':         'bi-zoom-in',
        'fa-filter':              'bi-funnel',
        'fa-refresh':             'bi-arrow-clockwise',
        'fa-sync':                'bi-arrow-repeat',
        'fa-history':             'bi-clock-history',
        'fa-download':            'bi-download',
        'fa-upload':              'bi-upload',
        'fa-cloud-download':      'bi-cloud-download',
        'fa-cloud-upload':        'bi-cloud-upload',
        'fa-cart-arrow-down':     'bi-cart-check',
        'fa-cart-plus':           'bi-cart-plus',
        'fa-shopping-cart':       'bi-cart',

        // =============================================
        // SORT ICONS
        // =============================================
        'fa-sort':                'bi-arrow-down-up',
        'fa-sort-alpha-asc':      'bi-sort-alpha-down',
        'fa-sort-alpha-desc':     'bi-sort-alpha-up',
        'fa-sort-amount-asc':     'bi-sort-down',
        'fa-sort-amount-desc':    'bi-sort-up',
        'fa-sort-asc':            'bi-sort-up',
        'fa-sort-desc':           'bi-sort-down',
        'fa-sort-down':           'bi-sort-down',
        'fa-sort-numeric-asc':    'bi-sort-numeric-down',
        'fa-sort-numeric-desc':   'bi-sort-numeric-up',
        'fa-sort-up':             'bi-sort-up',
        'fa-unsorted':            'bi-arrow-down-up',

        // =============================================
        // MISC / GENERAL
        // =============================================
        'fa-anchor':              'bi-anchor',
        'fa-asterisk':            'bi-asterisk',
        'fa-at':                  'bi-at',
        'fa-beer':                'bi-cup-straw',
        'fa-binoculars':          'bi-binoculars',
        'fa-birthday-cake':       'bi-cake',
        'fa-bolt':                'bi-lightning',
        'fa-bomb':                'bi-exclamation-octagon',
        'fa-book':                'bi-book',
        'fa-bookmark':            'bi-bookmark',
        'fa-bookmark-o':          'bi-bookmark',
        'fa-briefcase':           'bi-briefcase',
        'fa-bug':                 'bi-bug',
        'fa-bullhorn':            'bi-megaphone',
        'fa-bullseye':            'bi-bullseye',
        'fa-calculator':          'bi-calculator',
        'fa-camera':              'bi-camera',
        'fa-camera-retro':        'bi-camera',
        'fa-certificate':         'bi-award',
        'fa-cloud':               'bi-cloud',
        'fa-code':                'bi-code-slash',
        'fa-code-fork':           'bi-git',
        'fa-coffee':              'bi-cup-hot',
        'fa-copyright':           'bi-c-circle',
        'fa-cutlery':             'bi-fork-knife',
        'fa-utensils':            'bi-fork-knife',
        'fa-spoon':               'bi-fork-knife',
        'fa-ellipsis-h':          'bi-three-dots',
        'fa-ellipsis-v':          'bi-three-dots-vertical',
        'fa-fire':                'bi-fire',
        'fa-fire-extinguisher':   'bi-fire',
        'fa-flag':                'bi-flag',
        'fa-flag-checkered':      'bi-flag',
        'fa-flag-o':              'bi-flag',
        'fa-flash':               'bi-lightning',
        'fa-flask':               'bi-flask',
        'fa-frown-o':             'bi-emoji-frown',
        'fa-futbol-o':            'bi-dribbble',
        'fa-soccer-ball-o':       'bi-dribbble',
        'fa-gamepad':             'bi-controller',
        'fa-gavel':               'bi-hammer',
        'fa-legal':               'bi-hammer',
        'fa-gift':                'bi-gift',
        'fa-glass':               'bi-cup-straw',
        'fa-heartbeat':           'bi-activity',
        'fa-heart':               'bi-heart',
        'fa-heart-o':             'bi-heart',
        'fa-image':               'bi-image',
        'fa-photo':               'bi-image',
        'fa-picture-o':           'bi-image',
        'fa-language':            'bi-translate',
        'fa-leaf':                'bi-leaf',
        'fa-lemon-o':             'bi-emoji-smile',
        'fa-lightbulb-o':         'bi-lightbulb',
        'fa-magnet':              'bi-magnet',
        'fa-meh-o':               'bi-emoji-neutral',
        'fa-moon-o':              'bi-moon',
        'fa-paint-brush':         'bi-brush',
        'fa-paw':                 'bi-heart',
        'fa-puzzle-piece':        'bi-puzzle',
        'fa-recycle':             'bi-recycle',
        'fa-smile-o':             'bi-emoji-smile',
        'fa-star':                'bi-star',
        'fa-star-half':           'bi-star-half',
        'fa-star-half-empty':     'bi-star-half',
        'fa-star-half-full':      'bi-star-half',
        'fa-star-half-o':         'bi-star-half',
        'fa-star-o':              'bi-star',
        'fa-suitcase':            'bi-luggage',
        'fa-sun-o':               'bi-sun',
        'fa-tag':                 'bi-tag',
        'fa-tags':                'bi-tags',
        'fa-terminal':            'bi-terminal',
        'fa-thumb-tack':          'bi-pin-angle',
        'fa-ticket':              'bi-ticket',
        'fa-tint':                'bi-droplet',
        'fa-tree':                'bi-tree',
        'fa-trophy':              'bi-trophy',
        'fa-umbrella':            'bi-umbrella',
        'fa-bar-chart':           'bi-bar-chart',
        'fa-bar-chart-o':         'bi-bar-chart',
        'fa-line-chart':          'bi-graph-up',
        'fa-area-chart':          'bi-graph-up-arrow',
        'fa-pie-chart':           'bi-pie-chart',
        'fa-quote-left':          'bi-quote',
        'fa-quote-right':         'bi-quote',
        'fa-recycle':             'bi-recycle',
        'fa-road':                'bi-sign-turn-right',
        'fa-server':              'bi-server',
        'fa-sitemap':             'bi-diagram-3',
        'fa-database':            'bi-database',
        'fa-plug':                'bi-plug',
        'fa-industry':            'bi-building',
        'fa-registered':          'bi-r-circle',
        'fa-trademark':           'bi-tm',
        'fa-copyright':           'bi-c-circle',
        'fa-cc':                  'bi-cc-circle',
        'fa-creative-commons':    'bi-cc-circle',
    };

    // =============================================
    // ICON FIX ENGINE
    // =============================================

    function forceFixIcons() {
        // Target icons inside sidebar, menu, DataTables buttons, AND modals
        const selectors = [
            '.sidebar-nav .fa[class*="fa-"]',
            '#menu .fa[class*="fa-"]',
            '.main-menu .fa[class*="fa-"]',
            '.sidebar .fa[class*="fa-"]',
            '.dt-buttons .fa[class*="fa-"]',           // DataTables export buttons
            '.dataTables_wrapper .fa[class*="fa-"]',   // All DataTables icons
            'button .fa[class*="fa-"]',                // All button icons
            '.modal .fa[class*="fa-"]',                // All modal icons
            '.modal-header .fa[class*="fa-"]',         // Modal header icons
            '.modal-body .fa[class*="fa-"]',           // Modal body icons
            '.modal-footer .fa[class*="fa-"]',         // Modal footer icons
        ];

        const faIcons = document.querySelectorAll(selectors.join(', '));
        let fixedCount = 0;
        let unmapped = [];

        faIcons.forEach((icon) => {
            const classes = Array.from(icon.classList);
            const faClass = classes.find(cls => cls.startsWith('fa-'));

            if (!faClass) return;

            if (iconMapping[faClass]) {
                icon.className = '';
                icon.classList.add('bi', iconMapping[faClass]);
                fixedCount++;
            } else {
                unmapped.push(faClass);
            }
        });

        if (fixedCount > 0) {
            console.log(`✅ Force Icon Fix: ${fixedCount} icons converted`);
        }
        if (unmapped.length > 0) {
            console.warn('⚠️ Unmapped icons (need mapping):', [...new Set(unmapped)]);
        }
    }

    // Run on DOMContentLoaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', forceFixIcons);
    } else {
        forceFixIcons();
    }

    // Run after short delay to catch dynamically rendered content
    setTimeout(forceFixIcons, 500);
    
    // Run after longer delay for DataTables initialization
    setTimeout(forceFixIcons, 1500);

    // Watch for dynamically added icons (e.g., DataTables buttons)
    const observer = new MutationObserver(function(mutations) {
        let shouldRerun = false;
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1) { // Element node
                    // Check if added node contains FA icons
                    if (node.classList && (node.classList.contains('fa') || node.querySelector && node.querySelector('.fa[class*="fa-"]'))) {
                        shouldRerun = true;
                    }
                }
            });
        });
        if (shouldRerun) {
            forceFixIcons();
        }
    });

    // Start observing
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });

    // Listen for Bootstrap modal show events
    document.addEventListener('show.bs.modal', function(event) {
        console.log('🔄 Modal opening, converting icons...');
        // Run conversion after modal is shown
        setTimeout(forceFixIcons, 100);
    });

    // Also listen for modal shown event (after animation)
    document.addEventListener('shown.bs.modal', function(event) {
        console.log('✅ Modal opened, converting icons...');
        forceFixIcons();
    });

    // Expose globally for manual trigger if needed
    window.forceFixIcons = forceFixIcons;

})();
