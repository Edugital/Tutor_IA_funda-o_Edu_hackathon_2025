<?php

declare(strict_types=1);

define('MOODLE_INTERNAL', true);

class coding_exception extends Exception {}

require_once __DIR__ . '/../classes/local/decision_policy.php';

use assignfeedback_aitutoria\local\decision_policy;

$tests = [
    [
        'name' => 'manual without suggestion',
        'actual' => decision_policy::resolve('Human feedback', '', false),
        'expected' => ['text' => 'Human feedback', 'decision' => 'manual'],
    ],
    [
        'name' => 'explicit acceptance',
        'actual' => decision_policy::resolve('Draft', 'Reviewed suggestion', true),
        'expected' => ['text' => 'Reviewed suggestion', 'decision' => 'accepted_ai'],
    ],
    [
        'name' => 'human override',
        'actual' => decision_policy::resolve('Edited by teacher', 'AI suggestion', false),
        'expected' => ['text' => 'Edited by teacher', 'decision' => 'overridden_ai'],
    ],
];

foreach ($tests as $test) {
    if ($test['actual'] !== $test['expected']) {
        fwrite(STDERR, "FAIL: {$test['name']}\n");
        fwrite(STDERR, 'Expected: ' . json_encode($test['expected']) . "\n");
        fwrite(STDERR, 'Actual: ' . json_encode($test['actual']) . "\n");
        exit(1);
    }
}

try {
    decision_policy::resolve('Text', '', true);
    fwrite(STDERR, "FAIL: accepting an absent suggestion did not throw\n");
    exit(1);
} catch (coding_exception $exception) {
    // Expected.
}

echo "Decision policy behavioral tests passed.\n";
