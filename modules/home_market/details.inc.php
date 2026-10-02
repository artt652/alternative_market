<?php

$this->mode = 'details';
$plugin = gr('plugin');
$plugin_rec = SQLSelectOne("SELECT * FROM plugins WHERE MODULE_NAME LIKE '" . DBSafe($plugin) . "'");
if ($plugin == '') $this->redirect("?");
if (!isset($plugin_rec['ID'])) {
    // Модуль не установлен и нет записи в plugins — историю показываем по данным каталога.
    $plugin_rec = array('MODULE_NAME' => $plugin, 'TITLE' => $plugin);
}

$params = '?';
$params .= '&m[]=' . urlencode($plugin);

$plugin_data = array();

if ($params) {
    $result = $this->marketRequest($params);
    $data = json_decode($result, true);
    if (is_array($data) && isset($data['PLUGINS'][0])) {
        $plugin_data = $data['PLUGINS'][0];
    }
}

$out['URL'] = isset($plugin_data['URL']) ? $plugin_data['URL'] : '';
$out['COMMITS'] = array();
$out['MODULE_NAME_ENCODED'] = urlencode($plugin_rec['MODULE_NAME']);

if (isset($plugin_data['REPOSITORY_URL'])) {
    $plugin_data = $this->applyCustomRepositoryUrl($plugin_data);
    $plugin_data = $this->applyRepositoryVersionMetadata($plugin_data, true); // sha из GitHub-ленты для любого репозитория; не-GitHub URL остаётся как есть
    $out['REPOSITORY_URL_ENCODED'] = urlencode($plugin_data['REPOSITORY_URL']);
    $out['LATEST_VERSION_ENCODED'] = urlencode($plugin_data['LATEST_VERSION']);
    $out['MODULE_NAME_ENCODED'] = urlencode($plugin_data['MODULE_NAME']);

    $github_info = $this->getGithubRepositoryInfo($plugin_data['REPOSITORY_URL']); // история для любого GitHub-репо; не-GitHub URL → false
    if ($github_info) {
        $github_feed = getURL($github_info['feed_url'], 30 * 60);
        if ($github_feed != '') {
            $tmp = GetXMLTree($github_feed);
            if (is_array($tmp)) {
                $data = XMLTreeToArray($tmp);
                $items = isset($data['feed']['entry']) ? $data['feed']['entry'] : false;
            } else {
                $items = false;
            }
            if (is_array($items) && count($items)) {
                // одиночная <entry> приходит ассоциативным массивом, а не списком — нормализуем
                if (!isset($items[0])) {
                    $items = array($items);
                }
                foreach ($items as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    $link = isset($item['link']['href']) ? $item['link']['href'] : '';
                    $content = isset($item['content']['textvalue']) ? $item['content']['textvalue'] : (isset($item['title']['textvalue']) ? $item['title']['textvalue'] : '');
                    $updated = isset($item['updated']['textvalue']) ? $item['updated']['textvalue'] : '';
                    $out['COMMITS'][] = array('LINK' => $link, 'LINK_URL' => urlencode($link), 'CONTENT' => $content, 'UPDATED' => $updated);
                }
            }
        }
    }
}

$out['EXISTS'] = (is_dir(ROOT . 'modules/' . $plugin) || (isset($plugin_rec['IS_INSTALLED']) && $plugin_rec['IS_INSTALLED'])) ? '1' : '';
outHash($plugin_rec, $out);
