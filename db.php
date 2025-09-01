<?php
session_start();

const DEFAULT_PREFIXES = "T \nN \nX \nC \nM \n# \n## \n### ";

function get_prefixes() {
    if (!isset($_SESSION['special_prefixes']) || !is_array($_SESSION['special_prefixes'])) {
        $_SESSION['special_prefixes'] = explode("\n", DEFAULT_PREFIXES);
    }
    return $_SESSION['special_prefixes'];
}

function format_description($text) {
    $prefixes = get_prefixes();
    $lines = preg_split("/\r\n|\r|\n/", $text);
    foreach ($lines as &$line) {
        $leadingLen = strspn($line, " \t");
        $leading = substr($line, 0, $leadingLen);
        $rest = substr($line, $leadingLen);
        $prefix = '';
        foreach ($prefixes as $p) {
            if ($p !== '' && strpos($rest, $p) === 0) {
                $prefix = $p;
                $rest = substr($rest, strlen($p));
                break;
            }
        }
        $rest = preg_replace_callback('/^(\s*)(\S)/u', function($m){
            $char = function_exists('mb_strtoupper')
                ? mb_strtoupper($m[2], 'UTF-8')
                : strtoupper($m[2]);
            return $m[1] . $char;
        }, $rest, 1);
        $line = $leading . $prefix . $rest;
    }
    return implode("\n", $lines);
}

function get_db() {
    static $db = null;
    if ($db === null) {
        $db = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $db->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            location TEXT,
            default_priority INTEGER NOT NULL DEFAULT 0,
            special_prefixes TEXT
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS tasks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            description TEXT NOT NULL,
            due_date TEXT,
            details TEXT,
            priority INTEGER NOT NULL DEFAULT 2,
            done INTEGER NOT NULL DEFAULT 0,
            FOREIGN KEY(user_id) REFERENCES users(id)
        )");

        // Ensure new columns exist for older databases
        $columns = $db->query('PRAGMA table_info(tasks)')->fetchAll(PDO::FETCH_COLUMN, 1);
        if (!in_array('due_date', $columns, true)) {
            $db->exec('ALTER TABLE tasks ADD COLUMN due_date TEXT');
        }
        if (!in_array('details', $columns, true)) {
            $db->exec('ALTER TABLE tasks ADD COLUMN details TEXT');
        }
        if (!in_array('priority', $columns, true)) {
            $db->exec('ALTER TABLE tasks ADD COLUMN priority INTEGER NOT NULL DEFAULT 2');
        }

        // Ensure user columns exist for older databases
        $userColumns = $db->query('PRAGMA table_info(users)')->fetchAll(PDO::FETCH_COLUMN, 1);
        if (!in_array('location', $userColumns, true)) {
            $db->exec('ALTER TABLE users ADD COLUMN location TEXT');
        }
        if (!in_array('default_priority', $userColumns, true)) {
            $db->exec('ALTER TABLE users ADD COLUMN default_priority INTEGER NOT NULL DEFAULT 0');
        }
        if (!in_array('special_prefixes', $userColumns, true)) {
            $db->exec('ALTER TABLE users ADD COLUMN special_prefixes TEXT');
        }
    }
    return $db;
}
