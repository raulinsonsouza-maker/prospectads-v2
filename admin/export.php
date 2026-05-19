<?php

declare(strict_types=1);

require dirname(__DIR__) . '/api/bootstrap.php';

require_admin();

$pdo = get_pdo();
ensure_schema($pdo);

$stmt = $pdo->query('SELECT * FROM leads ORDER BY created_at DESC');
$leads = $stmt->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
$exportDate = (new DateTimeImmutable('now', app_timezone()))->format('Y-m-d');
header('Content-Disposition: attachment; filename="leads-ecommerce-' . $exportDate . '.csv"');

$out = fopen('php://output', 'w');
fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
fputcsv($out, ['ID', 'Data', 'Nome', 'WhatsApp', 'Loja', 'Investimento', 'Status', 'Notas'], ';');

foreach ($leads as $lead) {
    fputcsv($out, [
        $lead['id'],
        format_lead_datetime_csv((string) $lead['created_at']),
        $lead['nome'],
        $lead['whatsapp'],
        $lead['loja'],
        INVESTIMENTO_OPTIONS[$lead['investimento']] ?? $lead['investimento'],
        status_label($lead['status']),
        $lead['notas'] ?? '',
    ], ';');
}

fclose($out);
exit;
