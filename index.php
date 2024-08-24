<?php

require 'vendor/autoload.php';

use GuzzleHttp\Client;

/**
 * メモ
 *
 * スリープ無し、探索の深さ2で11秒程度
 * スリープ有り、探索の深さ2で150秒程度
 */
//：
define('MAX_DEPTH', 2); // 最大探索の深さを設定
define('IF_SLEEP', false); // スリープON/OFF
define('SLEEP_TIME', 1); // リクエスト間のスリープ時間（秒）

/**
 * Wikipediaのページを取得
 *
 * @param string $keyword
 * @return string|null
 */
function fetchWikipediaPage($keyword)
{
    $client = new Client();
    try {
        $response = $client->get('https://ja.wikipedia.org/w/api.php', [
            'query' => [
                'action' => 'parse',
                'page' => $keyword,
                'format' => 'json',
                'prop' => 'text',
                'section' => 0
            ]
        ]);

        $data = json_decode($response->getBody(), true);

        if (isset($data['error'])) {
            return null;
        }

        $html = $data['parse']['text']['*'];

        // デバッグ用：ページ内容を表示
        // echo "取得した内容:\n";
        // echo strip_tags($html) . "\n";

        return $html;
    } catch (Exception $e) {
        // エラー処理: 例外が発生した場合は null を返す
        return null;
    }
}

/**
 * 取得したページの概要欄からリンクを抽出
 *
 * @param string $content
 * @return array
 */
function extractLinks($content)
{
    if (empty($content)) {
        return [];
    }

    $links = [];
    $dom = new DOMDocument;

    // HTMLが不完全、あるいは構文の問題起因のエラー抑制のために @ を付加
    @$dom->loadHTML($content);

    $xpath = new DOMXPath($dom);

    // 指定されたタグの直下の<p>タグを対象にする
    $nodes = $xpath->query('//div[contains(@class, "mw-content-ltr") and contains(@class, "mw-parser-output")]/p//a[starts-with(@href, "/wiki/") and not(contains(@href, ":"))]');

    //リンク(キーワード)を取得
    foreach ($nodes as $node) {
        $link = urldecode(basename($node->getAttribute('href')));
        if (!in_array($link, $links)) {
            $links[] = $link;
        }
    }

    return $links;
}

/**
 * 再帰的なリンクの探索
 *
 * @param string $keyword
 * @param integer $depth
 * @param integer $maxDepth
 * @param array $visited
 * @return array
 */
function exploreLinks($keyword, $depth = 0, $maxDepth = 20, &$visited = [])
{
    // デバッグ用：探索中のキーワードと深さを表示
    // echo "探索中: $keyword (深さ: $depth)\n";

    // スリープを入れてリクエスト間隔を空ける
    if ($depth > 0 && IF_SLEEP) {
        sleep(SLEEP_TIME);
    }

    //キーワード重複検知
    if (in_array($keyword, $visited)) {
        return [['branch' => $keyword . '@', 'depth' => $depth]];
    }

    //探索済リスト
    $visited[] = $keyword;

    //深さ制限
    if ($depth >= $maxDepth) {
        return [['branch' => $keyword . '$', 'depth' => $depth]];
    }

    $result = [['branch' => $keyword, 'depth' => $depth]];

    //Wikipediaのページを取得
    $content = fetchWikipediaPage($keyword);

    //取得したページの概要欄からリンクを抽出
    $links = extractLinks($content);

    foreach ($links as $link) {
        if (mb_substr($link, -1) === '語' || mb_substr($link, -1) === '学') {
            $result[] = ['branch' => $link . '$', 'depth' => $depth + 1];
        } else {
            $result = array_merge($result, exploreLinks($link, $depth + 1, $maxDepth, $visited));
        }
    }

    return $result;
}

/**
 * ツリーの表示
 *
 * @param array $tree
 * @return void
 */
function displayTree($tree)
{
    foreach ($tree as $node) {
        echo str_repeat('    ', $node['depth']) . '- ' . $node['branch'] . "\n";
    }
}

/**
 * メイン処理
 *
 */
function main()
{
    echo "キーワードを入力してください: ";
    $keyword = trim(fgets(STDIN));
    $keyword = htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8');

    $visited = [];
    $startTime = microtime(true);
    $tree = exploreLinks($keyword, 0, MAX_DEPTH, $visited);
    $endTime = microtime(true);

    displayTree($tree);

    echo "全体の探索にかかった時間：" . number_format($endTime - $startTime, 2) . "秒\n";
}

main();
