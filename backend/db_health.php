<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

ensure_messages_table();
ensure_personal_info_table();
ensure_projects_table();
ensure_skills_table();

$databaseName = getenv('DB_NAME') ?: DB_NAME;
$requiredTables = [
    'messages',
    'personal_info',
    'projects',
    'skills',
];

try {
    $placeholders = implode(',', array_fill(0, count($requiredTables), '?'));

    $statement = db_connection()->prepare(
        "SELECT table_name
         FROM information_schema.tables
         WHERE table_schema = ?
           AND table_name IN ({$placeholders})"
    );

    $params = array_merge([$databaseName], $requiredTables);
    $statement->execute($params);

    $foundTables = $statement->fetchAll(PDO::FETCH_COLUMN);
    $foundTables = array_values(array_unique(array_map('strval', $foundTables ?: [])));
    sort($foundTables);

    $missingTables = array_values(array_diff($requiredTables, $foundTables));
    sort($missingTables);

    json_response(true, 'تم فحص قاعدة البيانات بنجاح.', [
        'database' => $databaseName,
        'total_tables' => count($requiredTables),
        'found_tables' => $foundTables,
        'missing_tables' => $missingTables,
    ]);
} catch (Throwable $exception) {
    error_log('DB Health Error: ' . $exception->getMessage());
    json_response(false, 'تعذر التحقق من قاعدة البيانات.', [], 500);
}