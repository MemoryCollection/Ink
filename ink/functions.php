<?php
/**
 * 墨 Ink —— 主题函数
 *
 * @package 墨 Ink
 * @author Wu
 * @version 1.3.0
 */

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/** 主题版本，仅用于前台注释标记 */
define('INK_VERSION', '1.3.0');

/* ---------------------------------------------------------
 * 兼容层：Typecho 1.2 用下划线类名，1.3 起命名空间类才是本体
 * ------------------------------------------------------- */
function ink_form_element($type, array $args)
{
    $candidates = array(
        'Typecho\\Widget\\Helper\\Form\\Element\\' . $type,
        'Typecho_Widget_Helper_Form_Element_' . $type,
    );

    foreach ($candidates as $class) {
        if (class_exists($class)) {
            $ref = new ReflectionClass($class);
            return $ref->newInstanceArgs($args);
        }
    }

    return null;
}

/**
 * 静态资源地址：带上文件修改时间做指纹
 *
 * 主题的 css / js 是就地改文件名不变的，如果 URL 里写死 ?v=1.0.0，
 * 浏览器和 CDN 会一直用旧文件，改完样式在线上看不到效果。
 */
function ink_asset($path)
{
    $options = Typecho_Widget::widget('Widget_Options');
    $path = ltrim($path, '/');
    $file = __DIR__ . '/' . $path;
    $ver  = is_file($file) ? filemtime($file) : false;

    $url = rtrim($options->themeUrl, '/') . '/' . $path;

    return htmlspecialchars($ver ? $url . '?v=' . $ver : $url, ENT_QUOTES, 'UTF-8');
}

/* ---------------------------------------------------------
 * 后台主题设置
 * ------------------------------------------------------- */
function themeConfig($form)
{
    $fields = array(
        array('Text', 'heroTitle', '', _t('首页大标题'),
            _t('显示在首页目录上方。留空则使用站点描述；描述也为空时使用站点标题。支持用 &lt;em&gt; 包裹的字变成渐变强调色。')),
        array('Textarea', 'heroIntro', '', _t('站点描述'),
            _t('用于 HTML 的 description 标签（搜索结果摘要）。留空则使用后台的站点描述。')),
        array('Select', 'navCats', '1', _t('导航栏平铺分类'),
            _t('把全部分类平铺在顶部导航。分类较多时导航可横向滚动。'), array('1' => _t('显示'), '0' => _t('不显示'))),
        array('Select', 'palette', 'auto', _t('默认配色'),
            _t('前台导航右侧可以随时切换，这里定的是访客第一次打开时用哪套。「跟随访客」= 记住访客上次选的（存在 cookie 里），没有记录时用青瓷。'),
            array(
                'auto'     => _t('跟随访客（推荐）'),
                'celadon'  => _t('青瓷'),
                'cinnabar' => _t('朱砂'),
                'pine'     => _t('松烟'),
                'indigo'   => _t('靛蓝'),
                'plum'     => _t('绛紫'),
                'amber'    => _t('赭石'),
            )),
        array('Text', 'footerText', '', _t('页脚文字'),
            _t('显示在左下角。支持 HTML。留空则显示「站点标题」。')),
        array('Text', 'icp', '', _t('备案号'),
            _t('留空则不显示。例如：京ICP备00000000号。')),
    );

    /* 导航右侧图标（最多 5 个，用草莓图标库） */
    $iconHelp = _t('图标名填草莓图标库（czs-）的名字，例如 github、rss、paper-plane、search-l。'
        . '链接留空则这一项不显示；只填链接、图标名留空时会按网址自动猜一个图标。'
        . '完整列表见主题 assets/css/icons.css。');
    for ($i = 1; $i <= 5; $i++) {
        $fields[] = array('Text', 'icon' . $i, '', sprintf(_t('图标 %d'), $i), $iconHelp);
        $fields[] = array('Text', 'iconUrl' . $i, '', sprintf(_t('图标 %d 链接'), $i),
            _t('必填。留空则整项不渲染。'));
        $fields[] = array('Text', 'iconLabel' . $i, '', sprintf(_t('图标 %d 文字'), $i),
            _t('鼠标悬停与读屏使用的说明，例如 GitHub。留空时用图标名。'));
        $fields[] = array('Select', 'iconBlank' . $i, '0', sprintf(_t('图标 %d 新窗口'), $i),
            _t('在浏览器新标签页打开。'), array('1' => _t('是'), '0' => _t('否')));
    }

    foreach ($fields as $field) {
        $type = $field[0];
        $name = $field[1];
        $default = $field[2] === '' ? null : $field[2];
        $label = $field[3];
        $desc  = $field[4];
        $choices = isset($field[5]) ? $field[5] : null;

        $args = ($type === 'Select')
            ? array($name, $choices, $default, $label, $desc)
            : array($name, null, $field[2], $label, $desc);

        $element = ink_form_element($type, $args);

        if ($element) {
            $form->addInput($element);
        }
    }
}

/* ---------------------------------------------------------
 * 工具函数
 * ------------------------------------------------------- */

/** 读取主题选项，空值回落到默认值 */
function ink_opt($key, $default = '')
{
    $options = Typecho_Widget::widget('Widget_Options');
    $value = $options->{$key};
    return ($value === null || $value === false || $value === '') ? $default : $value;
}

/** 校验后台填写的强调色，非法返回空串 */
function ink_accent()
{
    $accent = ink_opt('accent', '');
    return preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $accent)
        ? $accent
        : '';
}

/** 可用的配色方案：键为 data-palette 值，值为前台显示名与两个色点 */
function ink_palettes()
{
    return array(
        'celadon'  => array(_t('青瓷'), '#1f7a74', '#3fa8a0'),
        'cinnabar' => array(_t('朱砂'), '#b23a2e', '#c9703a'),
        'pine'     => array(_t('松烟'), '#2f6b4f', '#58a05f'),
        'indigo'   => array(_t('靛蓝'), '#2f5b9c', '#3f9bb5'),
        'plum'     => array(_t('绛紫'), '#8b3a62', '#c2596f'),
        'amber'    => array(_t('赭石'), '#9d6a1e', '#c9922f'),
    );
}

/** 默认配色：空白页（无 cookie）时使用 */
function ink_default_palette()
{
    return 'celadon';
}

/** 后台设定的配色：auto 表示跟随访客 cookie */
function ink_palette()
{
    $all = ink_palettes();
    $name = ink_opt('palette', 'auto');

    if ($name === 'auto' || !isset($all[$name])) {
        return 'auto';
    }

    return $name;
}

/**
 * 访客当前配色
 *
 * 优先级：后台锁定的配色 > cookie 里访客上次的选择 > ink_default_palette()
 * cookie 名 ink_palette，由 ink.js 在切换时写入，有效期一年。
 */
function ink_current_palette()
{
    $all = ink_palettes();
    $fixed = ink_palette();

    if ($fixed !== 'auto') {
        return $fixed;
    }

    if (!empty($_COOKIE['ink_palette'])) {
        $picked = $_COOKIE['ink_palette'];
        if (isset($all[$picked])) {
            return $picked;
        }
    }

    return ink_default_palette();
}

/**
 * 导航右侧的自定义图标
 *
 * 后台最多填 5 组（图标名 / 链接 / 文字 / 是否新窗口）。
 * 链接为空即视为没设置，整项不渲染；图标名为空时按域名猜一个。
 * 图标名来自草莓图标库（Strawberry Icon），对应 <i class="czs-名字">。
 */
function ink_ivicons()
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $list = array();

    for ($i = 1; $i <= 5; $i++) {
        $url = trim((string) ink_opt('iconUrl' . $i, ''));
        $raw = trim((string) ink_opt('icon' . $i, ''));

        if ($url === '') {
            continue;
        }

        // 只放行 http(s) / mailto / 站内相对地址
        if (!preg_match('~^(https?://|mailto:|/)~i', $url)) {
            continue;
        }

        $name = ink_icon_name($raw, $url);

        if ($name === '') {
            continue;
        }

        $label = trim((string) ink_opt('iconLabel' . $i, ''));

        $list[] = array(
            'name'  => $name,
            'url'   => htmlspecialchars($url, ENT_QUOTES, 'UTF-8'),
            'label' => htmlspecialchars($label !== '' ? $label : $name, ENT_QUOTES, 'UTF-8'),
            'blank' => ink_opt('iconBlank' . $i, '0') === '1',
        );
    }

    $cache = $list;
    return $cache;
}

/** 把后台写的图标名规整成 czs- 后面的部分；空的话按链接猜 */
function ink_icon_name($raw, $url)
{
    $raw = strtolower(trim($raw));

    if ($raw !== '') {
        // 允许直接填 czs-github 或 e60c 之类，统一去掉前缀
        $raw = preg_replace('~^\.?czs-~', '', $raw);
        if (preg_match('~^[a-z0-9-]+$~', $raw)) {
            return $raw;
        }
        return '';
    }

    $guess = array(
        'github.com'   => 'github',
        'weibo.com'    => 'weibo',
        'weibo.cn'     => 'weibo',
        'zhihu.com'    => 'zhihu',
        'bilibili.com' => 'bilibili',
        'twitter.com'  => 'twitter',
        'x.com'        => 'twitter',
        'youtube.com'  => 'youtube',
        'telegram'     => 'telegram',
        't.me'         => 'telegram',
        'qq.com'       => 'qq',
        'weixin'       => 'weixin',
        'wechat'       => 'weixin',
        'douban'       => 'read',
        'rss'          => 'rss',
        'feed'         => 'rss',
        'mailto:'      => 'paper-plane',
        'mail'         => 'paper-plane',
        'steam'        => 'steam',
        'wordpress'    => 'wordpress',
        'instagram'    => 'camera',
        'dribbble'     => 'dribbble',
        'pinterest'    => 'pinterest',
        'paypal'       => 'paypal',
        'alipay'       => 'alipay',
    );

    $lower = strtolower($url);
    foreach ($guess as $needle => $name) {
        if (strpos($lower, $needle) !== false) {
            return $name;
        }
    }

    return 'link-l';
}

/** 十六进制转 rgba() */function ink_hex_rgba($hex, $alpha = 0.08)
{
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    if (strlen($hex) < 6) {
        return 'rgba(178,58,46,' . $alpha . ')';
    }
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    return 'rgba(' . $r . ',' . $g . ',' . $b . ',' . $alpha . ')';
}

/** SEO 描述：文章取摘要，其它页取站点自述 */
function ink_meta_desc($archive)
{
    if ($archive->is('single')) {
        $text = ink_capture(function () use ($archive) {
            $archive->excerpt(150, ' ...');
        });
    } else {
        $text = ink_opt('heroIntro', '');
        if ($text === '') {
            $options = Typecho_Widget::widget('Widget_Options');
            $text = ink_capture(function () use ($options) {
                $options->description();
            });
        }
    }

    $text = strip_tags($text);
    $text = preg_replace('/\s+/u', ' ', $text);
    $text = trim($text);

    if (function_exists('mb_substr') && mb_strlen($text, 'UTF-8') > 160) {
        $text = mb_substr($text, 0, 160, 'UTF-8');
    }

    return $text;
}

/** 把元素的方法输出捕获成字符串（Typecho 的 permalink()/title() 都是直接 echo 的） */
function ink_capture($callback)
{
    ob_start();
    call_user_func($callback);
    return trim(ob_get_clean());
}

/**
 * 给正文中的 h2 / h3 补上稳定的 id，方便分享具体段落
 *
 * @return string
 */
function ink_build_toc($content)
{
    $index = 0;

    return preg_replace_callback(
        '#<h([23])([^>]*)>(.*?)</h\1>#is',
        function ($m) use (&$index) {
            $level = (int) $m[1];
            $attrs = $m[2];
            $inner = $m[3];
            $text  = trim(strip_tags($inner));

            if ($text === '') {
                return $m[0];
            }

            if (preg_match('#\bid\s*=\s*(["\'])([^"\']+)\1#i', $attrs, $idm)) {
                $id = $idm[2];
                $attrs = preg_replace('#\s*\bid\s*=\s*(["\'])[^"\']*\1#i', '', $attrs);
            } else {
                $index++;
                $id = 'sec-' . $index;
            }

            return '<h' . $level . ' id="' . $id . '"' . $attrs . '>' . $inner . '</h' . $level . '>';
        },
        $content
    );
}

/** 正文图片：补懒加载属性 */
function ink_lazy_images($content)
{
    return preg_replace_callback('#<img\b([^>]*?)/?>#i', function ($m) {
        $attrs = $m[1];
        if (stripos($attrs, 'loading=') === false) {
            $attrs .= ' loading="lazy"';
        }
        if (stripos($attrs, 'decoding=') === false) {
            $attrs .= ' decoding="async"';
        }
        return '<img' . $attrs . '>';
    }, $content);
}

/**
 * 渲染文章正文
 *
 * @return string
 */
function ink_post_body($archive)
{
    $raw = ink_capture(function () use ($archive) {
        $archive->content();
    });

    return ink_lazy_images(ink_build_toc($raw));
}

/**
 * 文章字数：中文按字计，连续的西文按一个词计
 *
 * 只数正文，不含标签与代码以外的空白。返回如「约 1,240 字」。
 */
function ink_word_count($archive)
{
    $html = ink_capture(function () use ($archive) {
        $archive->content();
    });

    $text = preg_replace('~<(script|style|pre|code)\b[^>]*>.*?</\1>~is', ' ', $html);
    $text = strip_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    $text = preg_replace('~\s+~u', ' ', $text);

    preg_match_all('~[\x{4e00}-\x{9fa5}]~u', $text, $han);
    preg_match_all('~[A-Za-z0-9]+(?:[\'’\-][A-Za-z0-9]+)*~', $text, $word);

    $n = count($han[0]) + count($word[0]);

    if ($n < 1) {
        return '';
    }

    return '约 ' . number_format($n) . ' 字';
}

/** 首页大标题：自定义 > 站点描述 > 站点标题 */function ink_hero_title($options)
{
    $custom = ink_opt('heroTitle');
    if ($custom !== '') {
        return $custom;
    }

    $desc = ink_capture(function () use ($options) {
        $options->description();
    });
    $desc = trim($desc);

    if ($desc !== '') {
        return htmlspecialchars($desc, ENT_QUOTES, 'UTF-8');
    }

    return htmlspecialchars($options->title, ENT_QUOTES, 'UTF-8');
}

/** 站点文章总数与最后更新时间（首页 hero 用） */
function ink_stats()
{
    $db = Typecho_Db::get();
    $prefix = $db->getPrefix();

    $count = 0;
    $latest = 0;

    try {
        $row = $db->fetchRow($db->select(array('COUNT(cid)' => 'num', 'MAX(created)' => 'last'))
            ->from('table.contents')
            ->where('type = ?', 'post')
            ->where('status = ?', 'publish')
            ->where('created < ?', time()));
        if ($row) {
            $count  = (int) $row['num'];
            $latest = (int) $row['last'];
        }
    } catch (Exception $e) {
        // 表结构异常时静默降级
    }

    return array('count' => $count, 'latest' => $latest);
}
