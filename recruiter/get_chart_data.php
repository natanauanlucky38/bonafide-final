<?php
include '../db.php';

// Validate the interval parameter
$interval = isset($_GET['interval']) ? $_GET['interval'] : '1 MONTH';
$interval = validateInterval($interval);

// Fetch the required data based on the interval
$totalJobs = getTotalJobs($conn, $interval);
$applicationMetrics = getApplicationMetrics($conn);
$referrals = getReferrals($conn);
$pipelineOverview = getPipelineOverview($conn);
$sourcingAnalytics = getSourcingAnalytics($conn);

// Return data as JSON
header('Content-Type: application/json');
echo json_encode([
    'totalJobs' => [$totalJobs],
    'applicationMetrics' => [
        $applicationMetrics['applied'] ?? 0,
        $applicationMetrics['offered'] ?? 0,
        $applicationMetrics['deployed'] ?? 0,
        $applicationMetrics['rejected'] ?? 0,
        $applicationMetrics['withdrawn'] ?? 0
    ],
    'referrals' => $referrals,
    'pipelineOverview' => [
        $pipelineOverview['screened'] ?? 0,
        $pipelineOverview['interviewed'] ?? 0,
        $pipelineOverview['offered'] ?? 0,
        $pipelineOverview['placed'] ?? 0,
        $pipelineOverview['rejected'] ?? 0
    ],
    'sourcingAnalytics' => $sourcingAnalytics
]);
