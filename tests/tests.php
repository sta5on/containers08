<?php

require_once __DIR__ . '/testframework.php';

function requireFirst(array $paths)
{
    foreach ($paths as $path) {
        if (is_file($path)) {
            require_once $path;
            return;
        }
    }

    throw new RuntimeException('Unable to load required file');
}

requireFirst([
    __DIR__ . '/../site/config.php',
    __DIR__ . '/../config.php',
]);
requireFirst([
    __DIR__ . '/../site/modules/database.php',
    __DIR__ . '/../modules/database.php',
]);
requireFirst([
    __DIR__ . '/../site/modules/page.php',
    __DIR__ . '/../modules/page.php',
]);

$tests = new TestFramework();
$tempFiles = [];

register_shutdown_function(function () use (&$tempFiles) {
    foreach ($tempFiles as $file) {
        if (is_file($file)) {
            @unlink($file);
        }
    }
});

function createSchemaDatabase()
{
    global $tempFiles;

    $file = tempnam(sys_get_temp_dir(), 'containers08_');
    if ($file === false) {
        throw new RuntimeException('Unable to create temporary database file');
    }

    $tempFiles[] = $file;

    $db = new Database($file);
    $db->Execute('CREATE TABLE page (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT,
        content TEXT
    )');
    $db->Execute("INSERT INTO page (title, content) VALUES ('Page 1', 'Content 1')");
    $db->Execute("INSERT INTO page (title, content) VALUES ('Page 2', 'Content 2')");
    $db->Execute("INSERT INTO page (title, content) VALUES ('Page 3', 'Content 3')");

    return $db;
}

function testDbConnection() {
    $db = createSchemaDatabase();
    return assertExpression($db instanceof Database, 'Database connection created', 'Database connection failed');
}

function testDbCount() {
    $db = createSchemaDatabase();
    return assertExpression($db->Count('page') === 3, 'Count is correct', 'Count is incorrect');
}

function testDbExecute() {
    $db = createSchemaDatabase();
    $db->Execute('CREATE TABLE log (id INTEGER PRIMARY KEY AUTOINCREMENT, message TEXT)');
    $db->Execute("INSERT INTO log (message) VALUES ('hello')");
    $rows = $db->Fetch('SELECT message FROM log');

    return assertExpression(count($rows) === 1 && $rows[0]['message'] === 'hello', 'Execute works', 'Execute failed');
}

function testDbCreate() {
    $db = createSchemaDatabase();
    $id = $db->Create('page', [
        'title' => 'Page 4',
        'content' => 'Content 4',
    ]);

    $row = $db->Read('page', $id);

    return assertExpression($id > 0 && $row['title'] === 'Page 4', 'Create works', 'Create failed');
}

function testDbRead() {
    $db = createSchemaDatabase();
    $row = $db->Read('page', 2);

    return assertExpression($row['title'] === 'Page 2' && $row['content'] === 'Content 2', 'Read works', 'Read failed');
}

function testDbUpdate() {
    $db = createSchemaDatabase();
    $db->Update('page', 1, [
        'title' => 'Updated title',
        'content' => 'Updated content',
    ]);

    $row = $db->Read('page', 1);

    return assertExpression($row['title'] === 'Updated title' && $row['content'] === 'Updated content', 'Update works', 'Update failed');
}

function testDbDelete() {
    $db = createSchemaDatabase();
    $db->Delete('page', 3);

    $row = $db->Read('page', 3);

    return assertExpression($db->Count('page') === 2 && $row === null, 'Delete works', 'Delete failed');
}

function testDbFetch() {
    $db = createSchemaDatabase();
    $rows = $db->Fetch('SELECT title FROM page ORDER BY id');

    return assertExpression(
        count($rows) === 3 && $rows[0]['title'] === 'Page 1' && $rows[2]['title'] === 'Page 3',
        'Fetch works',
        'Fetch failed'
    );
}

function testPageRender() {
    $template = tempnam(sys_get_temp_dir(), 'containers08_template_');
    if ($template === false) {
        throw new RuntimeException('Unable to create temporary template file');
    }

    file_put_contents($template, '<h1>{{title}}</h1><div>{{content}}</div>');
    global $tempFiles;
    $tempFiles[] = $template;

    $page = new Page($template);
    $html = $page->Render([
        'title' => 'Hello',
        'content' => 'World',
    ]);

    return assertExpression(strpos($html, '<h1>Hello</h1>') !== false && strpos($html, '<div>World</div>') !== false, 'Page render works', 'Page render failed');
}

$tests->add('Database connection', 'testDbConnection');
$tests->add('Database count', 'testDbCount');
$tests->add('Database execute', 'testDbExecute');
$tests->add('Database create', 'testDbCreate');
$tests->add('Database read', 'testDbRead');
$tests->add('Database update', 'testDbUpdate');
$tests->add('Database delete', 'testDbDelete');
$tests->add('Database fetch', 'testDbFetch');
$tests->add('Page render', 'testPageRender');

$tests->run();
echo $tests->getResult();
